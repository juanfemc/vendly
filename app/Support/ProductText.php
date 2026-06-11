<?php

namespace App\Support;

class ProductText
{
    public static function normalize(?string $value): string
    {
        $text = (string) $value;

        if (trim($text) === '') {
            return '';
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/&(?:amp;)?t?nbsp;?/i', ' ', $text) ?? $text;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+\n/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function plain(?string $value): string
    {
        $text = str_replace(['</li>', '</p>', '<br>', '<br/>', '<br />'], "\n", (string) $value);
        $text = self::removeDangerousBlocks(self::normalize($text));

        return self::normalize(strip_tags($text));
    }

    public static function rich(?string $value): string
    {
        $text = self::removeDangerousBlocks(self::normalize($value));

        if ($text === '') {
            return '';
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4>';

        return preg_replace('/<([a-z0-9]+)(?:\s[^>]*)?>/i', '<$1>', strip_tags($text, $allowedTags)) ?? '';
    }

    public static function featureLines(?string $value): string
    {
        return collect(preg_split('/[\r\n;]+/', self::plain($value)) ?: [])
            ->map(fn ($feature) => trim(preg_replace('/\s+/u', ' ', $feature) ?? $feature))
            ->filter()
            ->implode("\n");
    }

    private static function removeDangerousBlocks(string $text): string
    {
        return preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $text) ?? $text;
    }
}
