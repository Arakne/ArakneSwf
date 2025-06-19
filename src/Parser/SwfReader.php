<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser;

use Exception;
use RuntimeException;

use function assert;
use function gzuncompress;
use function ord;
use function strpos;
use function substr;
use function unpack;

/**
 * Low-level SWF primitives parser
 * This class is mutable and stateful, be careful when using it
 *
 * @todo handle overflows
 */
final class SwfReader
{
    public private(set) string $b; // Byte array (file contents)

    /**
     * @var non-negative-int
     */
    public int $offset = 0; // Byte position

    /**
     * The current bit offset when reading bits.
     *
     * @var int<0, 7>
     */
    private int $bitOffset = 0;

    /**
     * The current byte value, used for bit operations.
     * The value is -1 when no byte is currently read.
     *
     * Note: This value must be reset to -1 after all {@see SwfReader::$offset} changes
     *
     * @var int<-1, 255>
     */
    private int $currentByte = -1; // Current byte value (used for bit operations)

    public function __construct(string $binary)
    {
        $this->b = $binary;
    }

    public function doUncompress(int $len): void // @todo prefer return new instance. See how to do this during header parsing
    {
        $compressedMaxLen = $len - 8;
        $uncompress = gzuncompress(substr($this->b, 8), $compressedMaxLen) ?: throw new RuntimeException('Invalid compressed data');
        $this->b = substr($this->b, 0, 8) . substr($uncompress, 0, $compressedMaxLen);
    }

    /**
     * Read multiple bytes (chars) from the binary data.
     *
     * @param non-negative-int $num Number of bytes to read
     *
     * @return string
     */
    public function readBytes(int $num): string
    {
        assert($this->bitOffset === 0);

        $ret = substr($this->b, $this->offset, $num);
        $this->offset += $num;

        return $ret;
    }

    /**
     * Skip the specified number of bytes in the current byte stream.
     * Use this method instead of readBytes() when you want to skip bytes without using their value.
     *
     * @param non-negative-int $num
     */
    public function skipBytes(int $num): void
    {
        assert($this->bitOffset === 0);

        $this->offset += $num;
    }

    /**
     * Read a single byte (char) from the binary data.
     * This method is equivalent to `readBytes(1)`.
     */
    public function readChar(): string
    {
        assert($this->bitOffset === 0);

        return $this->b[$this->offset++];
    }

    /**
     * Read a null-terminated string from the current byte stream.
     * The string is read until the first null byte (`\0`) is encountered.
     * The null byte is not included in the returned string.
     *
     * @return string
     * @see SwfReader::readBytes() for reading a fixed length string
     */
    public function readNullTerminatedString(): string
    {
        assert($this->bitOffset === 0);

        $pos = $this->offset;
        $b = $this->b;
        $end = strpos($b, "\0", $pos);

        if ($end === false) {
            throw new Exception("String terminator not found");
        }

        $ret = substr($b, $pos, $end - $pos);
        $this->offset = $end + 1;

        return $ret;
    }

    /**
     * Reset the state of the bit reader.
     * This method must be called when bits methods are not used anymore.
     */
    public function alignByte(): void
    {
        if ($this->bitOffset !== 0) {
            ++$this->offset;
            $this->bitOffset = 0;
            $this->currentByte = -1;
        }
    }

    /**
     * Read a specified number of bits from the current byte stream and return it as an unsigned integer.
     *
     * @param non-negative-int $num Number of bits to read
     *
     * @return non-negative-int
     *
     * @see SwfReader::skipBits() for skipping bits without using their value
     * @see SwfReader::readBool() for reading a single bit as a boolean
     */
    public function readUB(int $num): int
    {
        if ($num === 0) {
            return 0;
        }

        $value = 0;
        $currentByte = $this->currentByte;
        $bitOffset = $this->bitOffset;
        $streamOffset = $this->offset;

        while ($num > 0) {
            if ($currentByte === -1) {
                $currentByte = ord($this->b[$streamOffset]);
            }

            $remainingBits = $bitsToRead = 8 - $bitOffset;

            if ($bitsToRead > $num) {
                $bitsToRead = $num;
            }

            $mask = (1 << $bitsToRead) - 1;
            $segment = ($currentByte >> ($remainingBits - $bitsToRead)) & $mask;
            $value |= $segment << ($num - $bitsToRead);

            $num -= $bitsToRead;
            $bitOffset += $bitsToRead;

            if ($bitOffset >= 8) {
                $bitOffset = 0;
                $streamOffset++;
                $currentByte = -1;
            }
        }

        assert($value >= 0);

        // Update the state
        $this->bitOffset = $bitOffset;
        $this->offset = $streamOffset;
        $this->currentByte = $currentByte;

        return $value;
    }

    /**
     * Skip the specified number of bits in the current byte stream.
     * Use this method instead of collectBits() when you want to skip bits without using their value.
     *
     * @param positive-int $num
     */
    public function skipBits(int $num): void
    {
        $newOffset = $this->bitOffset + $num;

        if ($newOffset < 8) {
            $this->bitOffset = $newOffset;
        } else {
            $this->offset += $newOffset >> 3;
            $this->bitOffset = $newOffset & 7;
            $this->currentByte = -1;
        }
    }

    /**
     * Read a signed fixed point number with a specified number of bits.
     *
     * The read value is a fixed 16.16 number, meaning that the first 16 bits represent the integer part
     * and the last 16 bits represent the fractional part.
     *
     * The two lower bytes are used for the decimal part, and the two higher for the integer part.
     * The first bit of the higher byte is the sign bit.
     *
     * If the number of bits is less than 17 (16 for fractional part + 1 for sign),
     * the value will be fully fractional (i.e. without the integer part).
     *
     * @param non-negative-int $num Number of bits to read (must be between 0 and 32)
     * @return float
     */
    public function readFB(int $num): float
    {
        if ($num === 0) {
            return 0.0;
        }

        assert($num <= 32);

        $raw = $this->readUB($num);
        $positive = ($raw & (1 << ($num - 1))) === 0;

        if ($positive) {
            $hi = ($raw >> 16) & 0xffff;
            $lo = $raw & 0xffff;

            return $hi + ($lo / 65536.0);
        }

        $raw = (1 << $num) - $raw;
        $hi = ($raw >> 16) & 0xffff;
        $lo = $raw & 0xffff;

        return -($hi + $lo / 65536.0);
    }

    /**
     * Read a single bit and return true if it is 1, false if it is 0.
     * This method is equivalent to `readUB(1) === 1`.
     */
    public function readBool(): bool
    {
        $offset = $this->bitOffset;

        if ($this->currentByte === -1) {
            $this->currentByte = ord($this->b[$this->offset]);
        }

        $mask = 1 << (7 - $offset);
        $ret = ($this->currentByte & $mask) === $mask;
        ++$offset;

        if ($offset < 8) {
            $this->bitOffset = $offset;
        } else {
            ++$this->offset;
            $this->bitOffset = 0;
            $this->currentByte = -1;
        }

        return $ret;
    }

    /**
     * Read the specified number of bits as a signed integer.
     * It performs a two's complement conversion if the sign bit is set (i.e. the highest bit is 1).
     *
     * @param non-negative-int $num
     *
     * @return int
     */
    public function readSB(int $num): int
    {
        if ($num === 0) {
            return 0;
        }

        $val = $this->readUB($num);
        $positive = ($val & (1 << ($num - 1))) === 0;

        if ($positive) {
            return $val;
        }

        return $val - (1 << $num);
    }

    /**
     * Read a fixed 8.8 number from the current byte stream.
     * The first byte is the fractional part, and the second byte is the integer part.
     *
     * @return float
     */
    public function readFixed8(): float
    {
        return $this->readSI16() / 256.0;
    }

    /**
     * Read a fixed 16.16 number from the current byte stream.
     * The first two bytes are the fractional part, and the next two bytes are the integer part.
     *
     * @return float
     */
    public function readFixed(): float
    {
        return $this->readSI32() / 65536.0;
    }

    /**
     * Read a 16-bit half-precision floating point number from the current byte stream.
     * The SWF format uses:
     * - 1 bit for the sign
     * - 5 bits for the exponent with a bias of 16 (which is different from the IEEE 754 standard)
     * - 10 bits for the mantissa
     *
     * @return float
     */
    public function readFloat16(): float
    {
        $raw = $this->readUI8() | ($this->readUI8() << 8);

        $sign = ($raw >> 15) & 0x0001; // 1 bit
        $exponent = ($raw >> 10) & 0x001f; // 5 bits
        $mantissa = $raw & 0x03ff; // 10 bits

        if ($exponent === 0 && $mantissa === 0) {
            return $sign === 0 ? 0.0 : -0.0;
        }

        if ($exponent === 0) {
            // Denormalized number
            return  ($sign === 0 ? 1.0 : -1.0) * (2**-15) * $mantissa / 1024.0;
        }

        if ($exponent === 0x1f) {
            return $mantissa !== 0 ? NAN : ($sign === 0 ? INF : -INF);
        }

        $ret = $sign === 0 ? 1.0 : -1.0;

        if ($exponent > 16) {
            $ret *= 1 << ($exponent - 16);
        } else {
            $ret /= 1 << (16 - $exponent);
        }

        return $ret * (1.0 + $mantissa / 1024.0);
    }

    /**
     * Read a 32-bit floating point number from the current byte stream.
     */
    public function readFloat(): float
    {
        return (float) $this->readUnpack('g', 4);
    }

    /**
     * Read a 64-bit double-precision floating point number from the current byte stream.
     */
    public function readDouble(): float
    {
        $low = $this->readBytes(4);
        $high = $this->readBytes(4);

        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        return (float) unpack('e', $high . $low)[1];
    }

    /**
     * Read a single byte as a signed integer.
     *
     * @return int<0, 255>
     */
    public function readUI8(): int
    {
        return ord($this->readChar());
    }

    /**
     * Read two bytes as a signed integer.
     */
    public function readSI16(): int
    {
        $ret = ord($this->readChar()) | (ord($this->readChar()) << 8);

        if ($ret >= 32768) {
            $ret -= 65536;
        }

        return $ret;
    }

    /**
     * Read two bytes as an unsigned integer.
     *
     * @return int<0, 65535>
     */
    public function readUI16(): int
    {
        // @phpstan-ignore return.type
        return ord($this->readChar()) | (ord($this->readChar()) << 8);
    }

    /**
     * Read two bytes as an unsigned integer, without moving the read cursor.
     * Calling multiple times this method will return the same value until the read cursor is moved.
     *
     * @return int<0, 65535>
     */
    public function peekUI16(): int
    {
        $offset = $this->offset;

        // @phpstan-ignore return.type
        return ord($this->b[$offset]) | (ord($this->b[$offset + 1]) << 8);
    }

    /**
     * Read int32 value
     */
    public function readSI32(): int
    {
        // PHP doesn't handle unpack of 32bits little-endian signed integers
        // So we have to convert unsigned to signed
        $v = $this->readUI32();

        if ($v >= 2147483648) {
            $v -= 4294967296;
        }

        return $v;
    }

    /**
     * Read 32-bit unsigned integer
     *
     * @return non-negative-int
     */
    public function readUI32(): int
    {
        // Parse int32 as little-endian
        // @phpstan-ignore return.type
        return (int) $this->readUnpack('V', 4);
    }

    /**
     * Read int 64
     */
    public function readSI64(): int
    {
        // Parse int64 as little-endian
        return (int) $this->readUnpack('P', 8);
    }

    /**
     * Variable-length unsigned integer
     *
     * The value can take between 1 and 5 bytes.
     * The result is a 4 bytes unsigned integer.
     *
     * @return non-negative-int
     */
    public function readEncodedU32(): int
    {
        $value = 0;
        $offset = 0;

        for (;;) {
            $b = $this->readUI8();
            $value |= ($b & 0x7f) << $offset;
            $offset += 7;

            if (($b & 0x80) === 0) {
                break;
            }
        }

        assert($value >= 0);

        return $value;
    }

    /**
     * Perform an unpack operation on the current byte stream.
     *
     * @param string $f The format string for unpacking.
     * @param positive-int $size The size of the data to read in bytes.
     *
     * @return scalar
     */
    private function readUnpack(string $f, int $size): mixed
    {
        assert($this->bitOffset === 0);

        // @todo handle error
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $value = unpack($f, $this->b, $this->offset)[1];
        $this->offset += $size;

        return $value;
    }
}
