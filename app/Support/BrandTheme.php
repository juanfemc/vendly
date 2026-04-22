<?php

namespace App\Support;

class BrandTheme
{
    public function __construct(
        public string $color,
        public string $contrast,
    ) {
    }

    public static function from(?string $rawColor, string $fallback = '#111111'): self
    {
        $color = preg_match('/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $rawColor)
            ? '#' . ltrim((string) $rawColor, '#')
            : $fallback;

        $normalized = ltrim($color, '#');

        if (strlen($normalized) === 3) {
            $normalized = collect(str_split($normalized))
                ->map(fn ($character) => $character . $character)
                ->implode('');
        }

        $red = hexdec(substr($normalized, 0, 2));
        $green = hexdec(substr($normalized, 2, 2));
        $blue = hexdec(substr($normalized, 4, 2));
        $luminance = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return new self(
            color: $color,
            contrast: $luminance < 0.55 ? '#ffffff' : '#111111',
        );
    }
}
