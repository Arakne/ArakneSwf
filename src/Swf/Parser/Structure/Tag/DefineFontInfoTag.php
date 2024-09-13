<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineFontInfoTag
{
    public function __construct(
        public int $version,
        public int $fontId,
        public string $fontName,
        public bool $fontFlagsSmallText,
        public bool $fontFlagsShiftJIS,
        public bool $fontFlagsANSI,
        public bool $fontFlagsItalic,
        public bool $fontFlagsBold,
        public bool $fontFlagsWideCodes,
        public array $codeTable,
        public ?int $languageCode = null,
    ) {
    }
}
