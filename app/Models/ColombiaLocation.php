<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ColombiaLocation extends Model
{
    private static ?bool $supportsTable = null;

    protected $fillable = [
        'department_code',
        'department_name',
        'city_code',
        'city_name',
        'normalized_city_name',
    ];

    public static function supportsTable(): bool
    {
        return self::$supportsTable ??= Schema::hasTable('colombia_locations');
    }

    public static function normalizeName(?string $value): string
    {
        $value = Str::ascii(Str::lower(trim((string) $value)));
        $value = preg_replace('/[^a-z0-9\s-]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    public static function departmentDisplayName(?string $value): string
    {
        $name = trim((string) $value);
        $normalized = self::normalizeName($name);

        return match ($normalized) {
            'bogota d c', 'bogota dc', 'bogota' => 'Bogota D.C.',
            'archipielago de san andres providencia y santa catalina',
            'san andres providencia y santa catalina',
            'san andres y providencia' => 'San Andres, Providencia Y Santa Catalina',
            'valle' => 'Valle Del Cauca',
            default => Str::title(Str::lower($name)),
        };
    }

    public static function departmentCodeFor(?string $code, ?string $name): string
    {
        $code = preg_replace('/\D+/', '', (string) $code) ?: null;
        $normalized = self::normalizeName($name);

        $knownCodes = [
            'amazonas' => '91',
            'antioquia' => '05',
            'arauca' => '81',
            'atlantico' => '08',
            'bogota d c' => '11',
            'bogota dc' => '11',
            'bogota' => '11',
            'bolivar' => '13',
            'boyaca' => '15',
            'caldas' => '17',
            'caqueta' => '18',
            'casanare' => '85',
            'cauca' => '19',
            'cesar' => '20',
            'choco' => '27',
            'cordoba' => '23',
            'cundinamarca' => '25',
            'guainia' => '94',
            'guaviare' => '95',
            'huila' => '41',
            'la guajira' => '44',
            'magdalena' => '47',
            'meta' => '50',
            'narino' => '52',
            'norte de santander' => '54',
            'putumayo' => '86',
            'quindio' => '63',
            'risaralda' => '66',
            'san andres providencia y santa catalina' => '88',
            'san andres y providencia' => '88',
            'santander' => '68',
            'sucre' => '70',
            'tolima' => '73',
            'valle' => '76',
            'valle del cauca' => '76',
            'vaupes' => '97',
            'vichada' => '99',
        ];

        return $knownCodes[$normalized] ?? str_pad((string) ($code ?: '00'), 2, '0', STR_PAD_LEFT);
    }

    public static function hasCatalog(): bool
    {
        return self::supportsTable() && self::query()->exists();
    }

    public static function departmentsForSelect()
    {
        if (! self::hasCatalog()) {
            return collect();
        }

        return self::query()
            ->select('department_code', 'department_name')
            ->orderBy('department_name')
            ->get()
            ->unique(fn (self $location) => self::normalizeName($location->department_name))
            ->values();
    }

    public static function citiesForSelect()
    {
        if (! self::hasCatalog()) {
            return collect();
        }

        return self::query()
            ->orderBy('department_name')
            ->orderBy('city_name')
            ->get();
    }
}
