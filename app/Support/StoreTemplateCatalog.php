<?php

namespace App\Support;

class StoreTemplateCatalog
{
    public const TECHNOLOGY = 'technology';
    public const FASHION = 'fashion';

    public static function all(): array
    {
        return [
            self::TECHNOLOGY => [
                'key' => self::TECHNOLOGY,
                'name' => 'Tecnologia',
                'business_type' => 'technology',
                'subtitle' => 'Plantilla minimalista para catalogos de tecnologia.',
                'description' => 'Incluye portada amplia, categorias horizontales, tarjetas limpias, producto detallado, checkout y carrito lateral.',
                'features' => ['Portada visual', 'Catalogo moderno', 'Carrito lateral', 'Checkout optimizado'],
            ],
            self::FASHION => [
                'key' => self::FASHION,
                'name' => 'Ropa',
                'business_type' => 'fashion',
                'subtitle' => 'Plantilla editorial para moda, ropa y accesorios.',
                'description' => 'Incluye portada inmersiva, grilla editorial de novedades y bloques promocionales para colecciones.',
                'features' => ['Hero editorial', 'Nuevas llegadas', 'Colecciones destacadas', 'Promos visuales'],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
