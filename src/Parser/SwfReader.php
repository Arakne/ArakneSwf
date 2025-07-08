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

use Arakne\Swf\Error\Errors;
use Arakne\Swf\Parser\Error\ParserExtraDataException;
use Arakne\Swf\Parser\Error\ParserInvalidDataException;
use Arakne\Swf\Parser\Error\ParserOutOfBoundException;

use function assert;
use function gzuncompress;
use function inflate_add;
use function inflate_get_status;
use function inflate_init;
use function is_array;
use function max;
use function min;
use function ord;
use function sprintf;
use function str_repeat;
use function strlen;
use function strpos;
use function substr;
use function unpack;

/**
 * Low-level SWF primitives parser
 * This class is mutable and stateful, be careful when using it
 */
final class SwfReader
{
    /**
     * Binary data of the SWF file.
     */
    public readonly string $data;

    /**
     * The end offset of the binary data (exclusive).
     * No data can be read once this offset is reached.
     *
     * @var non-negative-int
     */
    public readonly int $end;

    /**
     * Flags for error reporting.
     * If, for a given error, the corresponding bit is set, an exception will be thrown when the error occurs.
     * If not, the error will be silently ignored, and a fallback value will be returned instead.
     *
     * @see Errors constants
     */
    public readonly int $errors;

    /**
     * Current byte offset in the binary data.
     *
     * @var non-negative-int
     */
    public private(set) int $offset = 0;

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

    /**
     * @param string $binary The raw binary data of the SWF file.
     * @param non-negative-int|null $end The end offset of the binary data (exclusive). If null, the end of the binary data will be used.
     * @param int $errors Flags for error reporting.
     */
    public function __construct(string $binary, ?int $end = null, int $errors = Errors::ALL)
    {
        assert($end === null || $end <= strlen($binary));

        $this->data = $binary;
        $this->end = $end ?? strlen($binary);
        $this->errors = $errors;
    }

    /**
     * Uncompress the remaining data of the SWF file using ZLib compression.
     * A new read instance will be returned with the uncompressed data.
     *
     * @param non-negative-int|null $len The maximum length of the uncompressed data including data already read. If the uncompressed data is longer than this value,
     *                                   it will be truncated to this length.
     *                                   If null, the uncompressed data will be read until the end of the stream.
     * @return self
     *
     * @throws ParserInvalidDataException If the uncompressed data exceeds the specified length or if the compressed data is invalid.
     */
    public function uncompress(?int $len = null): self
    {
        $offset = $this->offset;
        $end = $this->end;
        $data = substr($this->data, 0, $offset);

        $context = inflate_init(ZLIB_ENCODING_DEFLATE);
        assert($context !== false);

        while ($offset < $end && inflate_get_status($context) === ZLIB_OK) {
            $chunk = substr($this->data, $offset, 4096);
            $data .= @inflate_add($context, $chunk, ZLIB_NO_FLUSH);

            if ($len !== null && strlen($data) > $len) {
                break;
            }

            $offset += 4096;
        }

        if ($len !== null && strlen($data) > $len) {
            if ($this->errors & Errors::EXTRA_DATA) {
                throw new ParserExtraDataException(
                    sprintf('Uncompressed data exceeds the maximum length of %d bytes (actual %d bytes)', $len, strlen($data)),
                    $offset,
                    $len
                );
            }

            $data = substr($data, 0, $len);
        }

        if (($status = inflate_get_status($context)) !== ZLIB_STREAM_END && ($this->errors & Errors::INVALID_DATA)) {
            $message = match ($status) {
                ZLIB_OK => 'Truncated compressed data',
                ZLIB_DATA_ERROR => 'Invalid compressed data: data error',
                default => sprintf('Invalid compressed data (errno %d)', $status),
            };

            throw new ParserInvalidDataException($message, $offset);
        }

        $self = new self($data, errors: $this->errors);
        $self->offset = $this->offset;

        return $self;
    }

    /**
     * Create a new instance of the reader with a chunk of the binary data.
     *
     * @param non-negative-int $offset The offset to start reading from
     * @param non-negative-int $end The end offset to read to (exclusive).
     *
     * @return self
     * @throws ParserOutOfBoundException If the end offset is greater than the end of the binary data
     */
    public function chunk(int $offset, int $end): self
    {
        assert($end >= $offset);

        if ($end > $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadAfterEnd($end, $this->end);
            }

            $end = $this->end;
        }

        $self = new self($this->data, $end, $this->errors);
        $self->offset = $offset;

        return $self;
    }

    /**
     * Read multiple bytes (chars) from the binary data.
     *
     * @param non-negative-int $num Number of bytes to read
     *
     * @return string
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readBytes(int $num): string
    {
        assert($this->bitOffset === 0);

        if ($this->offset + $num > $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadTooManyBytes($this->offset, $this->end, $num);
            }

            $len = max($this->end - $this->offset, 0);
            $ret = substr($this->data, $this->offset, $len) . str_repeat("\0", min($num - $len, 128));
            $this->offset = $this->end;

            return $ret;
        }

        $ret = substr($this->data, $this->offset, $num);
        $this->offset += $num;

        return $ret;
    }

    /**
     * Read bytes (chars) from the binary data until the specified offset.
     *
     * @param non-negative-int $offset The target offset to read bytes to (exclusive).
     * @return string
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readBytesTo(int $offset): string
    {
        assert($this->bitOffset === 0);

        $currentOffset = $this->offset;

        if ($currentOffset === $offset) {
            return '';
        }

        if ($offset < $currentOffset) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw new ParserOutOfBoundException(
                    sprintf('Cannot read bytes to an offset before the current offset: %s < %s', $offset, $currentOffset),
                    $offset
                );
            }

            return '';
        }

        if ($offset > $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadAfterEnd($currentOffset, $this->end);
            }

            $offset = $this->end;
        }

        $ret = substr($this->data, $currentOffset, $offset - $currentOffset);
        $this->offset = $offset;

        return $ret;
    }

    /**
     * Read ZLib compressed bytes from the binary data until the specified offset, and uncompress them.
     *
     * @param non-negative-int $offset The target offset to read bytes to (exclusive).
     * @return string
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     * @throws ParserInvalidDataException If the compressed data is invalid or cannot be uncompressed
     */
    public function readZLibTo(int $offset): string
    {
        $compressed = $this->readBytesTo($offset);

        if ($compressed === '') {
            return '';
        }

        $uncompressed = @gzuncompress($compressed);

        if ($uncompressed === false) {
            if ($this->errors & Errors::INVALID_DATA) {
                throw ParserInvalidDataException::createInvalidCompressedData($this->offset);
            }

            return '';
        }

        return $uncompressed;
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
     * Skip to the specified offset in the current byte stream.
     *
     * @param non-negative-int $offset The offset to skip to. Must be greater than or equal to the current offset.
     * @return void
     */
    public function skipTo(int $offset): void
    {
        assert($this->bitOffset === 0);
        assert($offset >= $this->offset);

        $this->offset = $offset;
    }

    /**
     * Read a single byte (char) from the binary data.
     * This method is equivalent to `readBytes(1)`.
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readChar(): string
    {
        assert($this->bitOffset === 0);

        if ($this->offset >= $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadAfterEnd($this->offset, $this->end);
            }

            return "\0";
        }

        return $this->data[$this->offset++];
    }

    /**
     * Read a null-terminated string from the current byte stream.
     * The string is read until the first null byte (`\0`) is encountered.
     * The null byte is not included in the returned string.
     *
     * @return string
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     * @throws ParserInvalidDataException If the null terminator is not found
     *
     * @see SwfReader::readBytes() for reading a fixed length string
     */
    public function readNullTerminatedString(): string
    {
        assert($this->bitOffset === 0);

        $pos = $this->offset;
        $b = $this->data;
        $end = strpos($b, "\0", $pos);

        if ($end === false) {
            if ($this->errors & Errors::INVALID_DATA) {
                throw new ParserInvalidDataException('String terminator not found', $pos);
            }

            $ret = substr($b, $pos, max($this->end - $pos, 0));
            $this->offset = $this->end;

            return $ret;
        }

        if ($end >= $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadAfterEnd($pos, $this->end);
            }

            $ret = substr($b, $pos, max($this->end - $pos, 0));
            $this->offset = $this->end;

            return $ret;
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
     * @param int<0, 32> $num Number of bits to read
     *
     * @return non-negative-int
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
                if ($streamOffset >= $this->end) {
                    if ($this->errors & Errors::OUT_OF_BOUNDS) {
                        throw ParserOutOfBoundException::createReadAfterEnd($streamOffset, $this->end);
                    }

                    $currentByte = 0;
                } else {
                    $currentByte = ord($this->data[$streamOffset]);
                }
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
                ++$streamOffset;
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
     *
     * @return float
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readBool(): bool
    {
        $offset = $this->bitOffset;

        if ($this->currentByte === -1) {
            $byteOffset = $this->offset;

            if ($byteOffset >= $this->end) {
                if ($this->errors & Errors::OUT_OF_BOUNDS) {
                    throw ParserOutOfBoundException::createReadAfterEnd($byteOffset, $this->end);
                }

                $this->currentByte = 0;
            } else {
                $this->currentByte = ord($this->data[$byteOffset]);
            }
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
     * @param int<0, 32> $num
     *
     * @return int
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
            return  ($sign === 0 ? 1.0 : -1.0) * (2 ** -15) * $mantissa / 1024.0;
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
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readFloat(): float
    {
        return (float) $this->readUnpack('g', 4);
    }

    /**
     * Read a 64-bit double-precision floating point number from the current byte stream.
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readUI8(): int
    {
        return ord($this->readChar());
    }

    /**
     * Read two bytes as a signed integer.
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function peekUI16(): int
    {
        $offset = $this->offset;

        if ($offset + 1 >= $this->end) {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadAfterEnd($offset, $this->end);
            }

            // Not consistent with readUI16() behavior, but it should do the job
            return 0;
        }

        // @phpstan-ignore return.type
        return ord($this->data[$offset]) | (ord($this->data[$offset + 1]) << 8);
    }

    /**
     * Read int32 value
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readUI32(): int
    {
        // Parse int32 as little-endian
        $v = (int) $this->readUnpack('V', 4);
        assert($v >= 0);

        return $v;
    }

    /**
     * Read int 64
     *
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
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
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    public function readEncodedU32(): int
    {
        $result = $this->readUI8();
        if (($result & 0x00000080) === 0) {
            assert($result >= 0);
            return $result;
        }

        $result = ($result & 0x0000007f) | $this->readUI8() << 7;
        if (($result & 0x00004000) === 0) {
            assert($result >= 0);
            return $result;
        }

        $result = ($result & 0x00003fff) | $this->readUI8() << 14;
        if (($result & 0x00200000) === 0) {
            assert($result >= 0);
            return $result;
        }

        $result = ($result & 0x001fffff) | $this->readUI8() << 21;
        if (($result & 0x10000000) === 0) {
            assert($result >= 0);
            return $result;
        }

        $result = ($result & 0x0fffffff) | $this->readUI8() << 28;
        assert($result >= 0);

        return $result;
    }

    /**
     * Perform an unpack operation on the current byte stream.
     *
     * @param string $f The format string for unpacking.
     * @param positive-int $size The size of the data to read in bytes.
     *
     * @return scalar
     * @throws ParserOutOfBoundException If the read operation exceeds the end of the binary data
     */
    private function readUnpack(string $f, int $size): mixed
    {
        assert($this->bitOffset === 0);

        if ($this->offset + $size <= $this->end) {
            $value = unpack($f, $this->data, $this->offset);
            $this->offset += $size;
        } else {
            if ($this->errors & Errors::OUT_OF_BOUNDS) {
                throw ParserOutOfBoundException::createReadTooManyBytes($this->offset, $this->end, $size);
            }

            $value = unpack($f, $this->readBytes($size));
            $this->offset = $this->end;
        }

        assert(is_array($value) && isset($value[1]));

        return $value[1];
    }
}
