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

namespace Arakne\Swf\Extractor\Drawer\Converter;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Imagick;
use ImagickPixel;
use RuntimeException;
use SimpleXMLElement;

use function assert;
use function ceil;
use function class_exists;
use function sprintf;

/**
 * Utility class to convert generated SVG to other formats.
 * Imagick is required to be installed and enabled in the PHP environment to use this class.
 */
final readonly class Converter
{
    private ImagickPixel $backgroundColor;

    public function __construct(
        /**
         * The size to apply to the image.
         * If null, the original size will be used.
         */
        private ?ImageResizerInterface $resizer = null,

        /**
         * The background color to use for the image.
         */
        string $backgroundColor = 'transparent',
    ) {
        $this->backgroundColor = new ImagickPixel($backgroundColor); // @todo do not use ImagickPixel: svg do not use Imagick
    }

    /**
     * Convert the object to SVG, and apply the resizer if needed.
     *
     * @param DrawableInterface $drawable The drawable to convert.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The rendered SVG.
     */
    public function toSvg(DrawableInterface $drawable, int $frame = 0): string
    {
        $svg = $drawable->draw(new SvgCanvas($drawable->bounds()), $frame)->render();

        if (!$this->resizer) {
            return $svg;
        }

        $xml = new SimpleXMLElement($svg);
        $width = (float) ($xml['width'] ?? $drawable->bounds()->width() / 20);
        $height = (float) ($xml['height'] ?? $drawable->bounds()->height() / 20);

        [$newWidth, $newHeight] = $this->resizer->scale($width, $height);

        $scaleX = $newWidth / $width;
        $scaleY = $newHeight / $height;

        $xml['width'] = (string) $newWidth; // @phpstan-ignore offsetAssign.valueType
        $xml['height'] = (string) $newHeight;  // @phpstan-ignore offsetAssign.valueType
        $xml['viewBox'] = sprintf('0 0 %d %d', ceil($newWidth), ceil($newHeight));  // @phpstan-ignore offsetAssign.valueType

        foreach ($xml->g as $g) {
            if (isset($g['transform'])) {
                $g['transform'] = sprintf('scale(%f, %f) %s', $scaleX, $scaleY, $g['transform']);
            } else {
                $g['transform'] = sprintf('scale(%f, %f)', $scaleX, $scaleY);  // @phpstan-ignore offsetAssign.valueType
            }
        }

        $svg = $xml->asXML();
        assert($svg !== false);

        return $svg;
    }

    /**
     * Render the drawable to PNG format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in PNG format.
     */
    public function toPng(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('png');

        return $img->getImageBlob();
    }

    /**
     * Render the drawable to GIF format.
     * Because partial transparency is not supported in GIF, it's advised to use define a non-transparent background color on constructor.
     *
     * Note: This method DOES NOT generate animated GIFs. Only the requested frame is rendered.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in GIF format.
     */
    public function toGif(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('gif');

        return $img->getImageBlob();
    }

    /**
     * Render the drawable to WebP format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in WebP format.
     */
    // @todo allow to pass options to webp and other formats
    public function toWebp(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        //$img->setOption('webp:lossless', 'true'); // @todo add options
        $img->setFormat('webp');

        return $img->getImageBlob();
    }

    /**
     * Render the drawable to JPEG format.
     * Because transparency is not supported in JPEG, you should define a non-transparent background color on constructor.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in JPEG format.
     */
    public function toJpeg(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('jpeg');

        return $img->getImageBlob();
    }

    /**
     * @param DrawableInterface $drawable
     * @param non-negative-int $frame
     * @return Imagick
     */
    private function toImagick(DrawableInterface $drawable, int $frame = 0): Imagick
    {
        if (!class_exists(Imagick::class)) {
            throw new RuntimeException('Imagick is not installed');
        }

        $svg = $drawable->draw(new SvgCanvas($drawable->bounds()), $frame)->render();

        $img = new Imagick();
        $img->setBackgroundColor($this->backgroundColor);
        $img->readImageBlob($svg);

        if ($this->resizer) {
            $img = $this->resizer->apply($img, $svg);
        }

        return $img;
    }
}
