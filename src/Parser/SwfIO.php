<?php

/*
 * This file is part of Arakne-Swf.
 *
 * Arakne-Swf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * Arakne-Swf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with Arakne-Swf.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * SWF.php: Macromedia Flash (SWF) file parser
 * Copyright (C) 2012 Thanos Efraimidis (4real.gr)
 *
 * Arakne-Swf: derived from SWF.php
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Parser;

use Exception;

use RuntimeException;

use function assert;
use function bcadd;
use function bcdiv;
use function bcmod;
use function bcmul;
use function bcpow;
use function bcscale;
use function gzuncompress;
use function ord;
use function strpos;
use function substr;
use function unpack;

/**
 * Low-level SWF primitives parser
 * This class is mutable and stateful, be careful when using it
 */
class SwfIO
{
    public private(set) string $b; // Byte array (file contents)
    public int $bytePos; // Byte position
    public int $bitPos; // Bit position

    public function __construct(string $binary)
    {
        $this->b = $binary;
        $this->bytePos = 0;
        $this->bitPos = 0;
    }

    public function doUncompress(int $len): void
    {
        $compressedMaxLen = $len - 8;
        $uncompress = gzuncompress(substr($this->b, 8), $compressedMaxLen) ?: throw new RuntimeException('Invalid compressed data');
        $this->b = substr($this->b, 0, 8) . substr($uncompress, 0, $compressedMaxLen);
    }

    public function byteAlign(): void
    {
        if ($this->bitPos != 0) {
            $this->bytePos++;
            $this->bitPos = 0;
        }
    }

    public function collectBytes(int $num): string
    {
        $ret = substr($this->b, $this->bytePos, $num);
        $this->bytePos += $num;
        return $ret;
    }

    public function collectByte(): string
    {
        return $this->b[$this->bytePos++];
    }

    public function collectBits(int $num): int
    {
        $value = 0;

        while ($num > 0) {
            $nextbits = ord($this->b[$this->bytePos]);
            $bitsFromHere = 8 - $this->bitPos;

            if ($bitsFromHere > $num) {
                $bitsFromHere = $num;
            }

            $value |= (($nextbits >> (8 - $this->bitPos - $bitsFromHere)) &
                    (0xff >> (8 - $bitsFromHere))) << ($num - $bitsFromHere);
            $num -= $bitsFromHere;
            $this->bitPos += $bitsFromHere;

            if ($this->bitPos >= 8) {
                $this->bitPos = 0;
                $this->bytePos++;
            }
        }

        return $value;
    }

    public function collectString(): string
    {
        $pos = $this->bytePos;
        $b = $this->b;
        $end = strpos($b, "\0", $pos);

        if ($end === false) {
            throw new Exception("String terminator not found");
        }

        $ret = substr($b, $pos, $end - $pos);
        $this->bytePos = $end + 1;

        return $ret;
    }

    // Bit values
    //XXX NOT SURE - NEEDS FIX
    public function collectFB(int $num): float
    {
        $ret = $this->collectBits($num);
        if (($ret & (1 << ($num - 1))) == 0) {
            // Positive
            $hi = ($ret >> 16) & 0xffff;
            $lo = $ret & 0xffff;
            $ret = $hi + $lo / 65536.0;
        } else {
            // Negative
            $ret = (1 << $num) - $ret;
            $hi = ($ret >> 16) & 0xffff;
            $lo = $ret & 0xffff;
            $ret = -($hi + $lo / 65536.0);
        }
        // echo sprintf("collectFB, num is %d, will return [0x%04x]\n", $num, $ret);
        return $ret;
    }

    public function collectUB(int $num): int
    {
        return $this->collectBits($num);
    }

    public function collectSB(int $num): int
    {
        $val = $this->collectBits($num);

        if ($val >= (1 << ($num - 1))) { // If high bit is set
            $val -= 1 << $num; // Negate
        }

        return $val;
    }

    // Fixed point numbers
    public function collectFixed8(): float
    {
        $lo0 = $lo = $this->collectUI8();
        $hi0 = $hi = $this->collectUI8();

        if ($hi < 128) {
            $ret = $hi + $lo / 256.0;
        } else {
            $full = 65536 - (256 * $hi + $lo);
            $hi = $full >> 8;
            $lo = $full & 0xff;
            $ret = -($hi + $lo / 256.0);
        }

        // echo sprintf("collectFixed8 hi=[0x%X], lo=[0x%X], return [%s]\n", $hi0, $lo0, $ret);
        return $ret;
    }

    public function collectFixed(): float
    {
        $lo = $this->collectUI16();
        $hi = $this->collectUI16();
        $ret = $hi + $lo / 65536.0;
        // echo sprintf("collectFixed hi=[0x%X], lo=[0x%X], return [%s]\n", $hi, $lo, $ret);
        return $ret;
    }

    // Floating point numbers
    public function collectFloat16(): float
    {
        $w = $this->collectUI8() << 8;
        $w |= $this->collectUI8();
        $sign = ($w >> 15) & 0x0001; // 1 bit
        $exponent = ($w >> 10) & 0x001f; // 5 bits
        $mantissa = $w & 0x03ff; // 10 bits

        if ($exponent === 0x00) {
            return $sign == 0 ? 0.0 : -0.0;
        }

        if ($exponent === 0x1f) {
            return $mantissa != 0 ? NAN : ($sign == 0 ? INF : -INF);
        }

        $ret = $sign === 0 ? 1.0 : -1.0;

        if ($exponent > 16) {
            $ret *= 1 << ($exponent - 16);
        } elseif ($exponent < 16) {
            $ret /= 1 << (16 - $exponent);
        }

        $ret *= (1.0 + $mantissa / 1024.0);
        // echo sprintf("float16: w=[0x%X], sign=[%d], exponent=[%d], mantissa=[%d], return=[%s]\n",
        // $w, $sign, $exponent, $mantissa, $ret);
        return $ret;
    }

    public function collectFloat(): float
    {
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        return (float) unpack('g', $this->collectBytes(4))[1];
    }

    public function collectDouble(): float
    {
        $low = $this->collectBytes(4);
        $high = $this->collectBytes(4);

        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        return (float) unpack('e', $high . $low)[1];
    }

    /**
     * @return int<0, 255>
     */
    public function collectUI8(): int
    {
        return ord($this->collectByte());
    }

    public function collectSI16(): int
    {
        $ret = 0;
        $ret += ord($this->collectByte());
        $ret += ord($this->collectByte()) << 8;

        if ($ret >= 32768) {
            $ret -= 65536;
        }

        return $ret;
    }

    /**
     * @return int<0, 65535>
     */
    public function collectUI16(): int
    {
        return ord($this->collectByte()) + (ord($this->collectByte()) << 8);
    }

    public function collectSI32(): int
    {
        // PHP doesn't handle unpack of 32bits little-endian signed integers
        // So we have to convert unsigned to signed
        $v = $this->collectUI32();

        if ($v >= 2147483648) {
            $v -= 4294967296;
        }

        return $v;
    }

    /**
     * @return non-negative-int
     */
    public function collectUI32(): int
    {
        // Parse int32 as little-endian
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, return.type
        return (int) unpack('V', $this->collectBytes(4))[1];
    }

    public function collectSI64(): int
    {
        // @todo test if this is correct
        // Parse int64 as little-endian
        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        return (int) unpack('P', $this->collectBytes(8))[1];
    }

    /**
     * @return non-negative-int
     */
    public function collectEncodedU32(): int
    {
        // @todo optimize
        // @todo tester (item 1/139.swf ?)
        bcscale(0);
        $ret = '0';
        $multiplier = '1';
        for (;;) {
            $b = $this->collectUI8();
            $ret = bcadd($ret, bcmul((string) ($b & 0x7f), $multiplier));
            $multiplier = bcmul($multiplier, '128');
            if (($b & 0x80) == 0) {
                $ret = (int) $ret;

                assert($ret >= 0);

                return $ret;
            }
        }
    }

    // For debugging
    public function dumpPosition(int $len): void
    {
        echo sprintf(" %04d.%1d:", $this->bytePos, $this->bitPos);
        for ($i = 0; $i < $len; $i++) {
            echo sprintf(" 0x%02x", ord($this->b[$this->bytePos + $i]));
        }
        echo sprintf("\n");
    }
}
