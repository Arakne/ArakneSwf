<?php

namespace Arakne\Swf\Extractor\Shape\FillType;

use Arakne\Swf\Extractor\Image\ImageCharacterInterface;
use Arakne\Swf\Extractor\Image\TransformedImage;
use Arakne\Swf\Parser\Structure\Record\ColorTransform;
use Arakne\Swf\Parser\Structure\Record\Matrix;
use Override;

use function crc32;

final readonly class Bitmap implements FillTypeInterface
{
    private string $hash;

    public function __construct(
        public ImageCharacterInterface $bitmap,
        public Matrix $matrix,
        public bool $smoothed = true,
        public bool $repeat = false,
    ) {
        $this->hash = self::computeHash($bitmap, $matrix, $smoothed, $repeat);
    }

    #[Override]
    public function hash(): string
    {
        return $this->hash;
    }

    #[Override]
    public function transformColors(ColorTransform $colorTransform): static
    {
        return new self(
            $this->bitmap->transformColors($colorTransform),
            $this->matrix,
        );
    }

    private static function computeHash(ImageCharacterInterface $bitmap, Matrix $matrix, bool $smoothed, bool $repeat): string
    {
        $imgHash = $bitmap->characterId;

        // When a color transform is applied, make sure that the hash is different
        if ($bitmap instanceof TransformedImage) {
            $imgHash .= '-' . crc32($bitmap->toPng());
        }

        $prefix = ($repeat ? 'R' : 'C') .'B';

        if (!$smoothed) {
            $prefix .= 'N';
        }

        return $prefix.$imgHash.'-'.crc32($matrix->toSvgTransformation());
    }
}
