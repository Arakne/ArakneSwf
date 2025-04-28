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
 * Copyright (C) 2024 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

declare(strict_types=1);

namespace Arakne\Swf\Extractor\Image\Util;

use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use BadMethodCallException;
use GdImage;
use InvalidArgumentException;

use function assert;
use function error_get_last;
use function extension_loaded;
use function fclose;
use function fopen;
use function imagealphablending;
use function imagecolorallocate;
use function imagecolorallocatealpha;
use function imagecolorat;
use function imagecolorstotal;
use function imagecreate;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagejpeg;
use function imagepalettetotruecolor;
use function imagepng;
use function imagesavealpha;
use function imagesetpixel;
use function imagesx;
use function imagesy;
use function is_int;
use function min;
use function ord;
use function rewind;
use function stream_get_contents;
use function strlen;
use function strpos;
use function substr;
use function var_dump;

/**
 * Wrapper for GD functions.
 */
final class GD
{
    private int $transparentColor;

    private function __construct(
        public readonly GdImage $image,
        public readonly int $width,
        public readonly int $height,
    ) {}

    /**
     * Disable alpha blending mode.
     * This method should be called to render PNG images with transparency.
     */
    public function disableAlphaBlending(): void
    {
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
    }

    /**
     * Change a pixel color, with alpha channel.
     */
    public function setPixelAlpha(int $x, int $y, int $red, int $green, int $blue, int $alpha): void
    {
        $gd = $this->image;

        if ($alpha === 0) {
            imagesetpixel($gd, $x, $y, ($this->transparentColor ??= imagecolorallocatealpha($gd, 0, 0, 0, 127)));
            return;
        }

        // Colors are premultiplied by alpha
        // So we need to reverse this operation
        $factor = 255 / $alpha;

        $red = (int) ($red * $factor);
        $green = (int) ($green * $factor);
        $blue = (int) ($blue * $factor);

        $newColor = imagecolorallocatealpha(
            $gd,
            min($red, 255),
            min($green, 255),
            min($blue, 255),
            127 - ($alpha >> 1) // GD uses 0-127, and represents transparency, so we divide alpha by 2 and invert it
        );

        imagesetpixel($gd, $x, $y, $newColor);
    }

    /**
     * Change a pixel color, without alpha channel.
     */
    public function setPixel(int $x, int $y, int $red, int $green, int $blue): void
    {
        $gd = $this->image;
        $newColor = imagecolorallocate(
            $gd,
            $red,
            $green,
            $blue,
        );

        imagesetpixel($gd, $x, $y, $newColor);
    }

    /**
     * Set the pixel at the given coordinates as fully transparent.
     */
    public function setTransparent(int $x, int $y): void
    {
        $gd = $this->image;

        imagesetpixel($gd, $x, $y, ($this->transparentColor ??= imagecolorallocatealpha($gd, 0, 0, 0, 127)));
    }

    /**
     * Get the color at the given coordinates.
     */
    public function color(int $x, int $y): int
    {
        return imagecolorat($this->image, $x, $y);
    }

    /**
     * Apply the color transform matrix to each pixel of the image.
     */
    public function transformColors(ColorTransform $matrix): void
    {
        $height = $this->height;
        $width = $this->width;
        $img = $this->image;

        // Ensure that the image is in true color mode
        // to allow usage of bitwise operations on colors
        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $redMult = $matrix->redMult / 256;
        $redAdd = $matrix->redAdd;
        $greenMult = $matrix->greenMult / 256;
        $greenAdd = $matrix->greenAdd;
        $blueMult = $matrix->blueMult / 256;
        $blueAdd = $matrix->blueAdd;
        $alphaMult = $matrix->alphaMult / 256;
        $alphaAdd = $matrix->alphaAdd;

        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $color = imagecolorat($img, $x, $y);

                assert(is_int($color));

                $a = 127 - (($color >> 24) & 0x7F);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $r = (int) ($r * $redMult + $redAdd);
                $g = (int) ($g * $greenMult + $greenAdd);
                $b = (int) ($b * $blueMult + $blueAdd);
                $a = (int) ($a * $alphaMult + $alphaAdd);

                // Do not use min and max for performance reasons
                $r = $r > 255 ? 255 : $r;
                $r = $r < 0 ? 0 : $r;
                $g = $g > 255 ? 255 : $g;
                $g = $g < 0 ? 0 : $g;
                $b = $b > 255 ? 255 : $b;
                $b = $b < 0 ? 0 : $b;
                $a = $a > 127 ? 127 : $a;
                $a = $a < 0 ? 0 : $a;
                $a = 127 - $a;

                $newColor = ($a << 24) | ($r << 16) | ($g << 8) | $b;

                imagesetpixel($img, $x, $y, $newColor);
            }
        }
    }

    /**
     * Render the image as a PNG.
     *
     * @param int $compression Compression level from 0 to 9, -1 for default.
     */
    public function toPng(int $compression = -1): string
    {
        $stream = fopen('php://memory','r+');
        imagepng($this->image, $stream, $compression);
        rewind($stream);

        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    /**
     * Export the image to JPEG format.
     *
     * @param int $quality The image quality from 0 (worst quality) to 100 (best quality). If -1 is passed, the default quality will be used.
     * @return string
     */
    public function toJpeg(int $quality = -1): string
    {
        $stream = fopen('php://memory','r+');
        imagejpeg($this->image, $stream, $quality);
        rewind($stream);

        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }

    /**
     * Create the GD wrapper from JPEG data.
     * This method will automatically fix the JPEG data.
     *
     * @param string $jpegData The JPEG data.
     *
     * @return self The GD wrapper.
     */
    public static function fromJpeg(string $jpegData): self
    {
        if (!extension_loaded('gd')) {
            throw new BadMethodCallException('GD extension is required');
        }

        $gd = @imagecreatefromstring(self::fixJpegData($jpegData));

        if (!$gd) {
            throw new InvalidArgumentException('Invalid JPEG data:' . error_get_last()['message']);
        }

        $width = imagesx($gd);
        $height = imagesy($gd);

        return new self($gd, $width, $height);
    }

    /**
     * Create the GD wrapper from PNG data.
     *
     * @param string $imageData The PNG data.
     *
     * @return self The GD wrapper.
     */
    public static function fromPng(string $imageData): self
    {
        if (!extension_loaded('gd')) {
            throw new BadMethodCallException('GD extension is required');
        }

        $gd = @imagecreatefromstring($imageData);

        if (!$gd) {
            throw new InvalidArgumentException('Invalid PNG data:' . error_get_last()['message']);
        }

        $width = imagesx($gd);
        $height = imagesy($gd);

        return new self($gd, $width, $height);
    }

    /**
     * Create a new GD image with a color pallet.
     */
    public static function createWithColorPallet(int $width, int $height): self
    {
        return new self(imagecreate($width, $height), $width, $height);
    }

    /**
     * Create a new GD image with true color.
     */
    public static function create(int $width, int $height): self
    {
        return new self(imagecreatetruecolor($width, $height), $width, $height);
    }

    /**
     * SWF may add multiple SOI, EOI, or has invalid headers
     * This method removes them.
     *
     * @param string $imageData The image data to fix.
     * @return string The fixed image data.
     */
    public static function fixJpegData(string $imageData): string
    {
        $len = strlen($imageData);
        $fixed = '';
        $pos = 0;

        // JPEG markers always start with 0xff, then a byte indicating the marker
        // So find the next marker, and process it until the end of the data
        while (($next = strpos($imageData, "\xff", $pos)) !== false && $next < $len - 1) {
            $marker = ord($imageData[$next + 1]);

            // Ignore SIO and EOI markers
            if ($marker === 0xd8 || $marker === 0xd9) {
                $fixed .= substr($imageData, $pos, $next - $pos);
                $pos = $next + 2;
                continue;
            }

            // Marker with length: do not change and skip the length
            if (
                $marker !== 0
                && ($marker < 0xd0 || $marker > 0xd7)
                && $next + 3 < $len
            ) {
                $length = (ord($imageData[$next + 2]) << 8) + ord($imageData[$next + 3]);
                $fixed .= substr($imageData, $pos, $next - $pos + $length + 2);
                $pos = $next + $length + 2;
            } else {
                // Marker without length: simply copy it and continue
                $fixed .= substr($imageData, $pos, $next - $pos + 2);
                $pos = $next + 2;
            }
        }

        // Add the valid header and footer
        return "\xff\xd8" . $fixed . "\xff\xd9";
    }
}
