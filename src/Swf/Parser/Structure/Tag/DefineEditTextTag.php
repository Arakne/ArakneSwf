<?php

namespace Arakne\Swf\Parser\Structure\Tag;

final readonly class DefineEditTextTag
{
    public function __construct(
        public int $characterId,
        public array $bounds,
        public bool $wordWrap,
        public bool $multiline,
        public bool $password,
        public bool $readOnly,
        public bool $autoSize,
        public bool $noSelect,
        public bool $border,
        public bool $wasStatic,
        public bool $html,
        public bool $useOutlines,
        public ?int $fontId,
        public ?string $fontClass,
        public ?int $fontHeight,
        public ?array $textColor,
        public ?int $maxLength,
        public ?array $layout,
        public string $variableName,
        public ?string $initialText,
    ) {
    }
}
