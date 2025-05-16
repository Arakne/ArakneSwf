<?php

namespace Arakne\Swf\Extractor\Timeline;

enum BlendMode: int
{
    case Normal = 1;
    case Layer = 2;
    case Multiply = 3;
    case Screen = 4;
    case Lighten = 5;
    case Darken = 6;
    case Difference = 7;
    case Add = 8;
    case Subtract = 9;
    case Invert = 10;
    case Alpha = 11;
    case Erase = 12;
    case Overlay = 13;
    case Hardlight = 14;

    /**
     * Get the value of the CSS "mix-blend-mode" property corresponding to this blending mode
     *
     * @return string|null The css property value, or null if not supported, or if it's the default value
     */
    public function toCssValue(): ?string
    {
        return match ($this) {
            self::Multiply => 'multiply',
            self::Screen => 'screen',
            self::Lighten, self::Add => 'lighten',
            self::Darken, self::Subtract => 'darken',
            self::Difference => 'difference',
            self::Overlay => 'overlay',
            self::Hardlight => 'hard-light',
            default => null,
        };
    }
}
