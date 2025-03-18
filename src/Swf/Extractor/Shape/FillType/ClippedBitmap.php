<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use BadMethodCallException;
use Override;

use function crc32;

final readonly class ClippedBitmap implements FillTypeInterface
{
    private string $hash;

    public function __construct(
        public ImageCharacterInterface $bitmap,
        public Matrix $matrix,
    ) {
        $this->hash = 'CB'.$this->bitmap->characterId.'-'.crc32($this->matrix->toSvgTransformation());
    }

    #[Override]
    public function hash(): string
    {
        return $this->hash;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        throw new BadMethodCallException('Not implemented yet');
    }
}
