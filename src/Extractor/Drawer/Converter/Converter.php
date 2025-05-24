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
use Arakne\Swf\Extractor\Drawer\Converter\Renderer\ImagickSvgRendererInterface;
use Arakne\Swf\Extractor\Drawer\Converter\Renderer\ImagickSvgRendererResolver;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Imagick;
use RuntimeException;
use SimpleXMLElement;

use function assert;
use function ceil;
use function class_exists;
use function fopen;
use function max;
use function rewind;
use function round;
use function sprintf;

/**
 * Utility class to convert generated SVG to other formats.
 * Imagick is required to be installed and enabled in the PHP environment to use this class.
 */
final readonly class Converter
{
    public function __construct(
        /**
         * The size to apply to the image.
         * If null, the original size will be used.
         */
        private ?ImageResizerInterface $resizer = null,

        /**
         * The background color to use for the image.
         */
        private string $backgroundColor = 'transparent',

        /**
         * The svg renderer to use, when raster image is requested.
         * If null, the best available renderer will be used.
         */
        private ?ImagickSvgRendererInterface $svgRenderer = null,
    ) {}

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
     *
     * @see Converter::toAnimatedGif() to generate animated GIFs.
     */
    public function toGif(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('gif');

        return $img->getImageBlob();
    }

    /**
     * Render all frames of the drawable as an animated GIF image.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param positive-int $fps The frame rate of the animation
     * @param bool $recursive If true, will count the frames of all children recursively
     *
     * @return string The image blob in GIF format.
     */
    public function toAnimatedGif(DrawableInterface $drawable, int $fps, bool $recursive): string
    {
        $gif = new Imagick();
        $gif->setFormat('gif');

        return $this->renderAnimatedImage($gif, 'gif', $drawable, $fps, $recursive);
    }

    /**
     * Render the drawable to WebP format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     *
     * @return string The image blob in WebP format.
     *
     * @see Converter::toAnimatedWebp() to generate animated WebP images.
     */
    // @todo allow to pass options to webp and other formats
    public function toWebp(DrawableInterface $drawable, int $frame = 0): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setOption('webp:lossless', 'true'); // @todo add options
        $img->setFormat('webp');

        return $img->getImageBlob();
    }

    /**
     * Render all frames of the drawable as an animated WebP image.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param positive-int $fps The frame rate of the animation
     * @param bool $recursive If true, will count the frames of all children recursively
     *
     * @return string The image blob in WebP format.
     */
    public function toAnimatedWebp(DrawableInterface $drawable, int $fps, bool $recursive): string
    {
        $anim = new Imagick();
        $anim->setFormat('webp');
        $anim->setOption('webp:lossless', 'true');

        return $this->renderAnimatedImage($anim, 'webp', $drawable, $fps, $recursive);
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

        $svg = $this->toSvg($drawable, $frame);

        return ($this->svgRenderer ?? ImagickSvgRendererResolver::get())->open($svg, $this->backgroundColor);
    }

    private function renderAnimatedImage(Imagick $target, string $format, DrawableInterface $drawable, int $fps, bool $recursive): string
    {
        $count = $drawable->framesCount($recursive);
        $delay = (int) max(round(100 / $fps), 1);

        for ($frame = 0; $frame < $count; $frame++) {
            $img = $this->toImagick($drawable, $frame);
            $img->setImageFormat($format);
            $img->setImageDelay($delay);
            $img->setImageDispose(2);

            $target->addImage($img);
        }

        $out = fopen('php://memory', 'w+');
        assert($out !== false);

        try {
            $target->writeImagesFile($out);

            rewind($out);
            $content = stream_get_contents($out);
            assert(!empty($content));
        } finally {
            fclose($out);
        }

        return $content;
    }
}
