<?php

use App\Models\User;
use App\Models\ColombiaLocation;
use App\Services\CheckoutService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:make-admin {email : Email del usuario admin} {--name= : Nombre del usuario si no existe} {--password= : Password del usuario si no existe}', function () {
    $email = Str::lower(trim((string) $this->argument('email')));
    $name = trim((string) ($this->option('name') ?: 'Administrador'));
    $passwordOption = (string) ($this->option('password') ?: '');

    $user = User::where('email', $email)->first();
    $generatedPassword = null;

    if ($user) {
        $user->role = 'admin';

        if ($name !== '') {
            $user->name = $name;
        }

        if ($passwordOption !== '') {
            $user->password = Hash::make($passwordOption);
        }

        $user->save();

        $this->info("Usuario actualizado como admin: {$user->email}");

        if ($passwordOption !== '') {
            $this->comment('La contraseña fue actualizada.');
        }

        return self::SUCCESS;
    }

    if ($passwordOption === '') {
        $generatedPassword = Str::password(16);
    }

    $user = User::create([
        'name' => $name !== '' ? $name : 'Administrador',
        'email' => $email,
        'password' => $passwordOption !== '' ? Hash::make($passwordOption) : Hash::make($generatedPassword),
        'role' => 'admin',
    ]);

    $this->info("Admin creado: {$user->email}");
    $this->line("Nombre: {$user->name}");

    if ($generatedPassword !== null) {
        $this->warn("Password generado: {$generatedPassword}");
        $this->comment('Guardalo en un lugar seguro. Solo se muestra una vez.');
    }

    return self::SUCCESS;
})->purpose('Crea o asciende un usuario admin sin sembrarlo automaticamente');

Artisan::command('payments:expire-pending', function () {
    $expired = app(CheckoutService::class)->expirePendingMercadoPagoOrders();

    $this->info("Pedidos Mercado Pago vencidos: {$expired}");

    return self::SUCCESS;
})->purpose('Cancela pedidos pendientes de Mercado Pago vencidos y libera stock');

Artisan::command('locations:import-colombia {--source= : URL JSON de Datos Abiertos/DIVIPOLA}', function () {
    if (! ColombiaLocation::supportsTable()) {
        $this->error('Primero ejecuta php artisan migrate.');

        return self::FAILURE;
    }

    $source = trim((string) $this->option('source'));
    $source = $source !== '' ? $source : 'https://www.datos.gov.co/resource/xdk5-pm3f.json?$limit=5000';

    $fetchJson = function (string $url): ?array {
        try {
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        return $response->successful() && is_array($response->json())
            ? $response->json()
            : null;
    };

    $socrataRows = function (array $records) {
        return collect($records)->map(function (array $row) {
            $departmentCode = $row['cod_depto']
                ?? $row['codigo_departamento']
                ?? $row['c_digo_departamento']
                ?? $row['dpto_ccdgo']
                ?? $row['departamento_codigo']
                ?? null;
            $departmentName = $row['departamento']
                ?? $row['nom_depto']
                ?? $row['nombre_departamento']
                ?? $row['dpto_cnmbr']
                ?? null;
            $cityCode = $row['cod_mpio']
                ?? $row['codigo_municipio']
                ?? $row['c_digo_municipio']
                ?? $row['mpio_ccdgo']
                ?? $row['municipio_codigo']
                ?? null;
            $cityName = $row['municipio']
                ?? $row['nom_mpio']
                ?? $row['nombre_municipio']
                ?? $row['mpio_cnmbr']
                ?? null;

            $departmentName = trim((string) $departmentName);
            $cityName = trim((string) $cityName);
            $cityCode = preg_replace('/\D+/', '', (string) $cityCode) ?: null;
            $departmentCode = preg_replace('/\D+/', '', (string) $departmentCode) ?: substr((string) $cityCode, 0, 2);

            if (! $departmentCode || ! $departmentName || ! $cityCode || ! $cityName) {
                return null;
            }

            return [
                'department_code' => ColombiaLocation::departmentCodeFor($departmentCode, $departmentName),
                'department_name' => ColombiaLocation::departmentDisplayName($departmentName),
                'city_code' => str_pad($cityCode, 5, '0', STR_PAD_LEFT),
                'city_name' => Str::title(Str::lower($cityName)),
                'normalized_city_name' => ColombiaLocation::normalizeName($cityName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })
        ->filter()
        ->unique('city_code')
        ->values();
    };

    $apiColombiaRows = function () use ($fetchJson) {
        $departments = collect($fetchJson('https://api-colombia.com/api/v1/Department') ?? []);
        $cities = collect($fetchJson('https://api-colombia.com/api/v1/City') ?? []);

        if ($departments->isEmpty() || $cities->isEmpty()) {
            return collect();
        }

        $departmentMap = $departments->mapWithKeys(fn (array $department) => [
            (string) ($department['id'] ?? '') => trim((string) ($department['name'] ?? '')),
        ]);

        return $cities->map(function (array $city) use ($departmentMap) {
            $cityName = trim((string) ($city['name'] ?? ''));
            $departmentId = (string) ($city['departmentId'] ?? data_get($city, 'department.id') ?? '');
            $departmentName = trim((string) (data_get($city, 'department.name') ?: $departmentMap->get($departmentId)));
            $rawCode = (string) ($city['daneCode'] ?? $city['postalCode'] ?? $city['id'] ?? '');
            $cityCode = preg_replace('/\D+/', '', $rawCode) ?: null;

            if ($cityName === '' || $departmentName === '' || ! $cityCode) {
                return null;
            }

            $departmentCode = strlen($cityCode) >= 5 ? substr($cityCode, 0, 2) : $departmentId;

            return [
                'department_code' => ColombiaLocation::departmentCodeFor($departmentCode, $departmentName),
                'department_name' => ColombiaLocation::departmentDisplayName($departmentName),
                'city_code' => str_pad((string) $cityCode, 5, '0', STR_PAD_LEFT),
                'city_name' => Str::title(Str::lower($cityName)),
                'normalized_city_name' => ColombiaLocation::normalizeName($cityName),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })
            ->filter()
            ->unique('city_code')
            ->values();
    };

    $json = $fetchJson($source);
    $rows = $json ? $socrataRows($json) : collect();

    if ($rows->isEmpty()) {
        $this->warn('No se pudo usar Datos Abiertos/DIVIPOLA. Intentando fuente alternativa API-Colombia...');
        $rows = $apiColombiaRows();
    }

    if ($rows->isEmpty()) {
        $this->error('No se pudo descargar el catalogo de ciudades desde las fuentes disponibles.');

        return self::FAILURE;
    }

    ColombiaLocation::query()->delete();
    $rows->chunk(500)->each(fn ($chunk) => ColombiaLocation::insert($chunk->all()));

    $this->info('Ubicaciones importadas: ' . $rows->count());

    return self::SUCCESS;
})->purpose('Importa departamentos y municipios de Colombia desde Datos Abiertos/DIVIPOLA');
