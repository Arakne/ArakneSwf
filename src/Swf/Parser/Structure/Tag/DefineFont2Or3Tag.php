<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFont2Or3Tag
{
    public function __construct(
        public int $version,
        public int $fontId,
        public bool $fontFlagsHasLayout,
        public bool $fontFlagsShiftJIS,
        public bool $fontFlagsSmallText,
        public bool $fontFlagsANSI,
        public bool $fontFlagsWideOffsets,
        public bool $fontFlagsWideCodes,
        public bool $fontFlagsItalic,
        public bool $fontFlagsBold,
        public int $languageCode,
        public string $fontName,
        public int $numGlyphs,
        public array $offsetTable,
        public array $glyphShapeTable,
        public array $codeTable,
        public ?array $layout,
    ) {
    }
}
