<?php

namespace App\Support;

class StoreTemplateCatalog
{
    public const TECHNOLOGY = 'technology';

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
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
