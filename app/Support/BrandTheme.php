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
        $color = self::normalizeColor($rawColor, $fallback);

        return new self(
            color: $color,
            contrast: self::contrastFor($color),
        );
    }

    public static function normalizeColor(?string $rawColor, string $fallback = '#111111'): string
    {
        $color = trim((string) $rawColor);

        if (! preg_match('/^#?(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $fallback;
        }

        return '#' . self::expandHex(strtolower($color));
    }

    public static function contrastFor(?string $rawColor, string $fallback = '#111111'): string
    {
        $normalized = self::expandHex(self::normalizeColor($rawColor, $fallback));
        $red = hexdec(substr($normalized, 0, 2));
        $green = hexdec(substr($normalized, 2, 2));
        $blue = hexdec(substr($normalized, 4, 2));
        $luminance = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return $luminance < 0.55 ? '#ffffff' : '#111111';
    }

    public static function mixWithWhite(?string $rawColor, float $colorPercent, string $fallback = '#111111'): string
    {
        $normalized = self::expandHex(self::normalizeColor($rawColor, $fallback));
        $weight = max(0, min(1, $colorPercent));
        $red = (int) round((hexdec(substr($normalized, 0, 2)) * $weight) + (255 * (1 - $weight)));
        $green = (int) round((hexdec(substr($normalized, 2, 2)) * $weight) + (255 * (1 - $weight)));
        $blue = (int) round((hexdec(substr($normalized, 4, 2)) * $weight) + (255 * (1 - $weight)));

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    private static function expandHex(string $color): string
    {
        $normalized = ltrim($color, '#');

        if (strlen($normalized) !== 3) {
            return $normalized;
        }

        return $normalized[0] . $normalized[0]
            . $normalized[1] . $normalized[1]
            . $normalized[2] . $normalized[2];
    }
}
