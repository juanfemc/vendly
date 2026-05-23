<?php

namespace App\Support;

class MinimalShopIcons
{
    public static function icon(string $icon): string
    {
        return match ($icon) {
            'close' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"></path></svg>',
            'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>',
            'grid' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="14" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect></svg>',
            'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.5 12 5l8 6.5"></path><path d="M6.5 10.5V20h11v-9.5"></path><path d="M10 20v-5h4v5"></path></svg>',
            'music' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V6l10-2v12"></path><circle cx="7" cy="18" r="2"></circle><circle cx="17" cy="16" r="2"></circle></svg>',
            'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2"></rect><path d="M11 18h2"></path></svg>',
            'storage' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5 12 4l7 4.5v7L12 20l-7-4.5v-7Z"></path><path d="m5 8.5 7 4.5 7-4.5M12 13v7"></path></svg>',
            'spark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.5 5.2L19 7l-4 3.8 4 3.8-5.5-1.2L12 19l-1.5-5.6L5 14.6l4-3.8L5 7l5.5 1.2L12 3Z"></path></svg>',
            'award' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="m9.5 12-1.5 8 4-2 4 2-1.5-8"></path></svg>',
            'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 12.5 12.5 20a2 2 0 0 1-2.8 0L4 14.3V4h10.3L20 9.7a2 2 0 0 1 0 2.8Z"></path><circle cx="8.5" cy="8.5" r="1.2"></circle></svg>',
            'bag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8Z"></path><path d="M9 8a3 3 0 0 1 6 0"></path></svg>',
            'heart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 8.8c0 5.2-8.5 10.2-8.5 10.2S3.5 14 3.5 8.8A4.3 4.3 0 0 1 12 7.4a4.3 4.3 0 0 1 8.5 1.4Z"></path></svg>',
            'truck' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"></path><circle cx="7" cy="18" r="2"></circle><circle cx="17" cy="18" r="2"></circle></svg>',
            'settings' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19 12a7.3 7.3 0 0 0-.1-1l2-1.5-2-3.4-2.4 1a7 7 0 0 0-1.8-1L14.4 3h-4l-.4 3a7 7 0 0 0-1.8 1l-2.4-1-2 3.4 2 1.5a7.3 7.3 0 0 0 0 2l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 1.8 1l.4 3h4l.4-3a7 7 0 0 0 1.8-1l2.4 1 2-3.4-2-1.5c.1-.3.1-.7.1-1Z"></path></svg>',
            'help' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M9.8 9a2.4 2.4 0 0 1 4.5 1.2c0 1.8-2.3 2-2.3 3.6"></path><path d="M12 17h.01"></path></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"></circle><path d="M3.5 19a5.5 5.5 0 0 1 11 0"></path><circle cx="17" cy="9" r="2.5"></circle><path d="M15.5 14.5A5 5 0 0 1 20.5 19"></path></svg>',
            'phone-call' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4 5 6c-.6.6-.8 1.6-.4 2.4 2 4.8 6.2 9 11 11 .8.4 1.8.2 2.4-.4l2-2-4-4-2 2c-2.1-1-3.8-2.7-4.8-4.8l2-2L7 4Z"></path></svg>',
            'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M12 4h7v16h-7"></path></svg>',
            'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"></path><path d="M10 11v6M14 11v6"></path><path d="M6 7l1 14h10l1-14"></path><path d="M9 7V4h6v3"></path></svg>',
            'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>',
            default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>',
        };
    }

    public static function categoryIcon(string $categoryName): string
    {
        return self::icon(self::categoryIconKey($categoryName));
    }

    public static function categoryIconKey(string $categoryName): string
    {
        $name = strtolower($categoryName);

        if (str_contains($name, 'home') || str_contains($name, 'hogar') || str_contains($name, 'smart')) {
            return 'home';
        }

        if (str_contains($name, 'music') || str_contains($name, 'musica') || str_contains($name, 'audio')) {
            return 'music';
        }

        if (str_contains($name, 'phone') || str_contains($name, 'telefono')) {
            return 'phone';
        }

        if (str_contains($name, 'stor') || str_contains($name, 'almacen') || str_contains($name, 'gaming')) {
            return 'storage';
        }

        return 'grid';
    }
}
