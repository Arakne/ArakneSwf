<?php

namespace Arakne\Swf\Extractor\MorphShape;

use Arakne\Swf\Extractor\DrawableInterface;
use Arakne\Swf\Extractor\Drawer\DrawerInterface;
use Arakne\Swf\Extractor\Modifier\CharacterModifierInterface;
use Arakne\Swf\Extractor\RatioDrawableInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Rectangle;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShape2Tag;
use Arakne\Swf\Parser\Structure\Tag\DefineMorphShapeTag;
use Override;

/**
 * Define a morph shape character
 *
 * To change the morph "frame", use the withRatio() method,
 * the frame parameter of the draw() method is ignored.
 *
 * @see DefineMorphShapeTag
 * @see DefineMorphShape2Tag
 */
final class MorphShapeDefinition implements RatioDrawableInterface
{
    private ?MorphShape $morphShape = null;

    /**
     * The morph ratio.
     * 0 means the start shape, 65535 means the end shape.
     *
     * @var int<0, 65535>
     */
    private int $ratio = 0;

    public function __construct(
        public readonly int $id,
        public readonly DefineMorphShapeTag|DefineMorphShape2Tag $tag,
        private MorphShapeProcessor $processor,
    ) {}

    public function morphShape(): MorphShape
    {
        if ($this->morphShape === null) {
            $this->morphShape = $this->processor->process($this->tag);
            unset($this->processor); // Free memory
        }

        return $this->morphShape;
    }

    #[Override]
    public function withRatio(int $ratio): DrawableInterface
    {
        $self = clone $this;
        $self->ratio = $ratio;

        return $self;
    }

    #[Override]
    public function bounds(): Rectangle
    {
        $start = $this->tag->startBounds;
        $end = $this->tag->endBounds;

        return new Rectangle(
            $this->interpolateInt($start->xmin, $end->xmin, $this->ratio),
            $this->interpolateInt($start->xmax, $end->xmax, $this->ratio),
            $this->interpolateInt($start->ymin, $end->ymin, $this->ratio),
            $this->interpolateInt($start->ymax, $end->ymax, $this->ratio),
        );
    }

    private function interpolateInt(int $start, int $end, int $ratio): int
    {
        return (int) (($start * (self::MAX_RATIO - $ratio) + $end * $ratio) / self::MAX_RATIO);
    }

    #[Override]
    public function framesCount(bool $recursive = false): int
    {
        return 1;
    }

    #[Override]
    public function draw(DrawerInterface $drawer, int $frame = 0): DrawerInterface
    {
        $drawer->shape($this->morphShape()->interpolate($this->ratio));

        return $drawer;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): DrawableInterface
    {
        // @todo Implement transformColors() method.
        return $this;
    }

    #[Override]
    public function modify(CharacterModifierInterface $modifier, int $maxDepth = -1): DrawableInterface
    {
        // @todo Implement modify() method.
        return $this;
    }
}
