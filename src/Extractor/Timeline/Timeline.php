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

namespace Arakne\Swf\Extractor\Timeline;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Drawer\Svg\SvgCanvas;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Extractor\Modifier\GotoAndStop;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Override;

use function array_map;
use function assert;
use function count;
use function min;

/**
 * Movie timeline for a sprite or a swf file
 */
final readonly class Timeline implements DrawableInterface
{
    /**
     * @var non-empty-list<Frame>
     */
    public array $frames;

    /**
     * @param Frame ...$frames
     * @no-named-arguments
     */
    public function __construct(
        /**
         * The display rectangle of the timeline
         * All frames should have the same rectangle
         */
        public Rectangle $bounds,
        Frame ...$frames,
    ) {
        assert(count($frames) > 0);
        $this->frames = $frames;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        return $this->bounds;
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        $count = count($this->frames);

        if (!$recursive) {
            return $count;
        }

        foreach ($this->frames as $index => $frame) {
            $frameCount = $frame->framesCount(true) + $index;

            if ($frameCount > $count) {
                $count = $frameCount;
            }
        }

        return $count;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $frames = $this->frames;
        $currentFrame = min($frame, count($frames) - 1);

        return $frames[$currentFrame]->draw($drawer, $frame);
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): self
    {
        $frames = [];

        foreach ($this->frames as $object) {
            $frames[] = $object->transformColors($colorTransform);
        }

        return new self($this->bounds, ...$frames);
    }

    #[Override]
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): self
    {
        $self = $this;

        if ($maxDepth !== 0) {
            $frames = [];
            $isModified = false;

            foreach ($this->frames as $frame) {
                $modifiedFrame = $frame->modify($modifier, $maxDepth - 1);
                $frames[] = $modifiedFrame;
                $isModified = $isModified || ($modifiedFrame !== $frame);
            }

            if ($isModified) {
                $self = self::create(...$frames);
            }
        }

        return $modifier->applyOnTimeline($self);
    }

    /**
     * Find a frame by its label
     * If the label is not found, null is returned
     *
     * @param string $label The label to search for
     * @return Frame|null The frame with the given label, or null if not found
     */
    public function frameByLabel(string $label): ?Frame
    {
        foreach ($this->frames as $frame) {
            if ($frame->label === $label) {
                return $frame;
            }
        }

        return null;
    }

    /**
     * Attach a new object to the timeline at the specified depth and name, and return a new timeline
     * This is equivalent to the "Attach Movie" action in SWF files
     *
     * @param DrawableInterface $attachment The object to attach
     * @param int $depth The depth at which to attach the object
     * @param string|null $name The name of the attached object (will be set on {@see FrameObject::$name})
     *
     * @return self
     */
    public function withAttachment(DrawableInterface $attachment, int $depth, ?string $name): self
    {
        $bounds = $attachment->bounds();
        $frames = array_map(
            static fn (Frame $frame) => $frame->addObject(new FrameObject(
                depth: $depth,
                object: $attachment,
                bounds: $bounds,
                matrix: new Matrix(
                    translateX: $bounds->xmin,
                    translateY: $bounds->ymin,
                ),
                name: $name,
            )),
            $this->frames,
        );

        return self::create(...$frames);
    }

    /**
     * Keep only the frame with the given label, and return a new timeline
     * If the label is not found, the first frame will be kept
     *
     * Note: this method will not modify child timelines, use {@see GotoAndStop} modifier for that.
     *
     * @param string $label
     * @return self
     */
    public function keepFrameByLabel(string $label): self
    {
        if (count($this->frames) === 1) {
            return $this;
        }

        return new self(
            $this->bounds,
            $this->frameByLabel($label) ?? $this->frames[0]
        );
    }

    /**
     * Keep only the frame at the given position.
     * If this number is higher than the number of frames, the last frame will be used.
     *
     *  Note: this method will not modify child timelines, use {@see GotoAndStop} modifier for that.
     *
     * @param int $number The frame number to keep (starts at 1)
     * @return self
     */
    public function keepFrameByNumber(int $number): self
    {
        $count = count($this->frames);

        if ($count === 1) {
            return $this;
        }

        if ($number < 1) {
            $number = 1;
        } elseif ($number > $count) {
            $number = $count;
        }

        return new self(
            $this->bounds,
            $this->frames[$number - 1]
        );
    }

    /**
     * Modify the display bounds of the timeline and frames, and return a new timeline
     *
     * @param Rectangle $newBounds
     * @return self The new timeline with the new bounds
     */
    public function withBounds(Rectangle $newBounds): self
    {
        $frames = [];

        foreach ($this->frames as $frame) {
            $frames[] = $frame->withBounds($newBounds);
        }

        return new self($newBounds, ...$frames);
    }

    /**
     * Render a single frame to SVG
     *
     * @param non-negative-int $frame Frame number to render. If greater than the number of frames, the last frame will be rendered.
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width.
     *                                  If false, the minimum stroke width will be 1px to approximate Flash rendering.
     */
    public function toSvg(int $frame = 0, bool $subpixelStrokeWidth = true): string
    {
        $maxFrame = count($this->frames) - 1;
        $toRender = $this->frames[min($frame, $maxFrame)];

        return $toRender->draw(new SvgCanvas($toRender->bounds, $subpixelStrokeWidth), $frame)->render();
    }

    /**
     * Render all frames to SVG
     *
     * @param bool $subpixelStrokeWidth Enable subpixel stroke width.
     *                                  If false, the minimum stroke width will be 1px to approximate Flash rendering.
     *
     * @return iterable<int, string> Renderer frames, with the frame number as key
     */
    public function toSvgAll(bool $subpixelStrokeWidth = true): iterable
    {
        foreach ($this->frames as $f => $frame) {
            $drawer = new SvgCanvas($frame->bounds, $subpixelStrokeWidth);
            $frame->draw($drawer, $f);

            yield $f => $drawer->render();
        }
    }

    /**
     * Create an empty timeline with no frames and size of 0x0
     * This value is used as fallback value when an error occurs during the timeline parsing
     */
    public static function empty(): self
    {
        return new Timeline(new Rectangle(0, 0, 0, 0), new Frame(new Rectangle(0, 0, 0, 0), [], [], null));
    }

    /**
     * Create a new timeline from the given frames, with fixed bounds on all frames
     *
     * @param Frame ...$frames
     * @return self
     * @no-named-arguments
     */
    public static function create(Frame ...$frames): self
    {
        $bounds = Rectangle::merge(
            array_map(
                static fn (Frame $frame) => $frame->bounds,
                $frames
            )
        );

        return new self(
            $bounds,
            ...array_map(static fn (Frame $frame) => $frame->withBounds($bounds), $frames)
        );
    }
}
