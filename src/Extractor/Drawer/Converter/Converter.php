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
use Arakne\Swf\Extractor\Drawer\Svg\SvgBuilder;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Closure;
use Imagick;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

use function assert;
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

        /**
         * Enable subpixel stroke width (default: true)
         *
         * If true, the stroke width will be according to the actual SWF stroke width (can be a float value).
         * In this case, stroke below 1px will be rendered by the antialiasing of the renderer, so in a blurry and non-opaque way.
         * This rendering differs from flash, which always render stroke with a minimum of 1px width.
         *
         * If false, the minimum stroke width will be 1px, and `non-scaling-stroke` will be used to avoid stroke scaling
         * when the SVG is resized.
         * This allows to approximate the flash rendering at native size, but the relative stroke width will not be preserved,
         * so rescaling will not be accurate.
         *
         * @see SvgBuilder::$subpixelStrokeWidth
         */
        private bool $subpixelStrokeWidth = true,
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
        $svg = $drawable->draw(new SvgCanvas($drawable->bounds(), $this->subpixelStrokeWidth), $frame)->render();

        if (!$this->resizer) {
            return $svg;
        }

        $xml = new SimpleXMLElement($svg);
        $width = (float) ($xml['width'] ?? $drawable->bounds()->width() / 20);
        $height = (float) ($xml['height'] ?? $drawable->bounds()->height() / 20);

        [$newWidth, $newHeight] = $this->resizer->scale($width, $height);

        $xml['width'] = (string) $newWidth;
        $xml['height'] = (string) $newHeight;
        $xml['viewBox'] = sprintf('0 0 %h %h', $width, $height);

        $svg = $xml->asXML();
        assert($svg !== false);

        return $svg;
    }

    /**
     * Render the drawable to PNG format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     * @param array{
     *     compression?: scalar,
     *     format?: scalar,
     *     bit-depth?: scalar
     * } $options Options to apply to the PNG image.
     *
     * @return string The image blob in PNG format.
     */
    public function toPng(DrawableInterface $drawable, int $frame = 0, array $options = []): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('png');
        $this->applyPngOptions($img, $options);

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
     * @param array{
     *     loop?: scalar,
     *     optimize?: scalar
     * } $options Options to apply to the GIF image.
     *
     * @return string The image blob in GIF format.
     *
     * @see Converter::toAnimatedGif() to generate animated GIFs.
     */
    public function toGif(DrawableInterface $drawable, int $frame = 0, array $options = []): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('gif');
        $this->applyGifOptions($img, $options);

        return $img->getImageBlob();
    }

    /**
     * Render all frames of the drawable as an animated GIF image.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param positive-int $fps The frame rate of the animation
     * @param bool $recursive If true, will count the frames of all children recursively
     * @param array{
     *     loop?: scalar,
     *     optimize?: scalar
     * } $options
     *
     * @return string The image blob in GIF format.
     */
    public function toAnimatedGif(DrawableInterface $drawable, int $fps = 24, bool $recursive = false, array $options = []): string
    {
        $gif = new Imagick();
        $gif->setFormat('gif');

        return $this->renderAnimatedImage($gif, 'gif', $drawable, $fps, $recursive, fn (Imagick $img) => $this->applyGifOptions($img, $options));
    }

    /**
     * Render the drawable to WebP format.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     * @param array{
     *      lossless?: scalar,
     *      compression?: scalar,
     *      quality?: scalar
     *  } $options
     *
     * @return string The image blob in WebP format.
     *
     * @see Converter::toAnimatedWebp() to generate animated WebP images.
     */
    public function toWebp(DrawableInterface $drawable, int $frame = 0, array $options = []): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('webp');
        $this->applyWebpOptions($img, $options);

        return $img->getImageBlob();
    }

    /**
     * Render all frames of the drawable as an animated WebP image.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param positive-int $fps The frame rate of the animation
     * @param bool $recursive If true, will count the frames of all children recursively
     * @param array{
     *     lossless?: scalar,
     *     compression?: scalar,
     *     quality?: scalar
     * } $options
     *
     * @return string The image blob in WebP format.
     */
    public function toAnimatedWebp(DrawableInterface $drawable, int $fps = 24, bool $recursive = false, array $options = []): string
    {
        $anim = new Imagick();
        $anim->setFormat('webp');

        return $this->renderAnimatedImage($anim, 'webp', $drawable, $fps, $recursive, fn (Imagick $img) => $this->applyWebpOptions($img, $options));
    }

    /**
     * Render the drawable to JPEG format.
     * Because transparency is not supported in JPEG, you should define a non-transparent background color on constructor.
     *
     * @param DrawableInterface $drawable The drawable to render.
     * @param non-negative-int $frame The frame number to extract from the drawable. This value is 0-based.
     * @param array{
     *     quality?: scalar,
     *     sampling?: scalar,
     *     size?: scalar
     *  } $options Options to apply to the JPEG image.
     *
     * @return string The image blob in JPEG format.
     */
    public function toJpeg(DrawableInterface $drawable, int $frame = 0, array $options = []): string
    {
        $img = $this->toImagick($drawable, $frame);
        $img->setFormat('jpeg');
        $this->applyJpegOptions($img, $options);

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

    private function renderAnimatedImage(Imagick $target, string $format, DrawableInterface $drawable, int $fps, bool $recursive, ?Closure $optionsConfigurator = null): string
    {
        $count = $drawable->framesCount($recursive);
        $delay = (int) max(round(100 / $fps), 1);

        for ($frame = 0; $frame < $count; $frame++) {
            $img = $this->toImagick($drawable, $frame);
            $img->setImageFormat($format);
            $img->setImageDelay($delay);
            $img->setImageDispose(2);

            if ($optionsConfigurator) {
                $optionsConfigurator($img);
            }

            $target->addImage($img);
        }

        if ($optionsConfigurator) {
            $optionsConfigurator($target);
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

    /**
     * @param Imagick $img
     * @param array{
     *     compression?: scalar,
     *     format?: scalar,
     *     bit-depth?: scalar
     * } $options
     * @return void
     */
    private function applyPngOptions(Imagick $img, array $options): void
    {
        if (isset($options['compression'])) {
            $img->setOption('png:compression-level', (string) $options['compression']);
        }

        if (isset($options['format'])) {
            $img->setOption('png:format', (string) $options['format']);
        }

        if (isset($options['bit-depth'])) {
            $img->setOption('png:bit-depth', (string) $options['bit-depth']);
            $img->setImageDepth((int) $options['bit-depth']);
        }
    }

    /**
     * @param Imagick $img
     * @param array{
     *     lossless?: scalar,
     *     compression?: scalar,
     *     quality?: scalar
     * } $options
     * @return void
     */
    private function applyWebpOptions(Imagick $img, array $options): void
    {
        if (!empty($options['lossless'])) {
            $img->setOption('webp:lossless', 'true');
        }

        if (isset($options['quality'])) {
            $img->setImageCompressionQuality((int) $options['quality']);
        }

        if (isset($options['compression'])) {
            $img->setOption('webp:method', (string) $options['compression']);
        }
    }

    /**
     * @param Imagick $img
     * @param array{
     *     quality?: scalar,
     *     sampling?: scalar,
     *     size?: scalar
     * } $options
     * @return void
     */
    private function applyJpegOptions(Imagick $img, array $options): void
    {
        if (isset($options['quality'])) {
            $img->setImageCompressionQuality((int) $options['quality']);
        }

        if (isset($options['sampling'])) {
            match ($options['sampling']) {
                '444' => $img->setSamplingFactors(['1x1', '1x1', '1x1']),
                '422' => $img->setSamplingFactors(['2x1', '1x1', '1x1']),
                '420' => $img->setSamplingFactors(['2x2', '1x1', '1x1']),
                '411' => $img->setSamplingFactors(['4x1', '1x1', '1x1']),
                default => throw new InvalidArgumentException('Invalid sampling factor: ' . $options['sampling']),
            };
        }

        if (isset($options['size'])) {
            $img->setOption('jpeg:extent', (string) $options['size']);
        }
    }

    /**
     * @param Imagick $img
     * @param array{
     *     loop?: scalar,
     *     optimize?: scalar
     * } $options
     * @return void
     * @throws \ImagickException
     */
    private function applyGifOptions(Imagick $img, array $options): void
    {
        if (isset($options['loop'])) {
            $img->setImageIterations((int) $options['loop']);
        }

        if (isset($options['optimize'])) {
            $img->setOption('gif:optimize', (string) $options['optimize']);
        }
    }
}
