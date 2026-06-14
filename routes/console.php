<?php

use App\Jobs\SendWhatsAppTemplate;
use App\Models\ColombiaLocation;
use App\Models\CustomerFollowup;
use App\Models\Store;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppStatusEvent;
use App\Services\CheckoutService;
use App\Services\CustomerFollowupScheduler;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
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
    $expired = app(CheckoutService::class)->expirePendingOnlinePaymentOrders();

    $this->info("Pedidos de pago en linea vencidos: {$expired}");

    return self::SUCCESS;
})->purpose('Cancela pedidos pendientes de pago en linea vencidos y libera stock');

Artisan::command('stores:expire-subscriptions', function () {
    $expired = Store::expirePastSubscriptions();

    $this->info("Suscripciones de tienda vencidas: {$expired}");

    return self::SUCCESS;
})->purpose('Marca como vencidas las pruebas o suscripciones de tienda que ya finalizaron');

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

Artisan::command('whatsapp:retry-failed {--limit=100 : Cantidad maxima de mensajes para reintentar}', function () {
    $limit = max(1, min(1000, (int) $this->option('limit')));
    $messages = WhatsAppMessage::query()
        ->where('status', WhatsAppMessage::STATUS_FAILED)
        ->oldest('failed_at')
        ->limit($limit)
        ->get();
    $queued = 0;

    foreach ($messages as $message) {
        $message->update([
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'error' => null,
            'failed_at' => null,
            'cancelled_at' => null,
        ]);

        CustomerFollowup::query()
            ->where('whatsapp_message_id', $message->id)
            ->whereIn('status', [
                CustomerFollowup::STATUS_FAILED,
                CustomerFollowup::STATUS_CANCELLED,
            ])
            ->update([
                'status' => CustomerFollowup::STATUS_QUEUED,
                'failed_at' => null,
                'skipped_at' => null,
                'cancelled_at' => null,
                'error' => null,
            ]);

        try {
            SendWhatsAppTemplate::dispatch($message->id);
            $queued++;
        } catch (\Throwable $exception) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 500),
                'failed_at' => now(),
            ]);

            CustomerFollowup::query()
                ->where('whatsapp_message_id', $message->id)
                ->where('status', CustomerFollowup::STATUS_QUEUED)
                ->update([
                    'status' => CustomerFollowup::STATUS_FAILED,
                    'error' => Str::limit($exception->getMessage(), 500),
                    'failed_at' => now(),
                ]);
        }
    }

    $this->info("Mensajes programados para reintento: {$queued}");

    return self::SUCCESS;
})->purpose('Programa nuevamente mensajes de WhatsApp marcados como fallidos');

Artisan::command('whatsapp:prune {--days= : Dias de retencion}', function () {
    $days = max(1, (int) ($this->option('days') ?: config('services.whatsapp.retention_days', 365)));
    $deleted = WhatsAppMessage::query()
        ->where('created_at', '<', now()->subDays($days))
        ->whereIn('status', [
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
            WhatsAppMessage::STATUS_FAILED,
            WhatsAppMessage::STATUS_UNKNOWN,
            WhatsAppMessage::STATUS_CANCELLED,
        ])
        ->delete();
    $eventsDeleted = WhatsAppStatusEvent::query()
        ->where('created_at', '<', now()->subDays($days))
        ->delete();

    $this->info("Mensajes antiguos eliminados: {$deleted}. Eventos huerfanos eliminados: {$eventsDeleted}");

    return self::SUCCESS;
})->purpose('Elimina trazas antiguas y finalizadas de WhatsApp');

Artisan::command('whatsapp:recover-stale {--minutes=15 : Antiguedad minima del intento}', function () {
    $minutes = max(5, (int) $this->option('minutes'));
    $messages = WhatsAppMessage::query()
        ->where('status', WhatsAppMessage::STATUS_RETRYING)
        ->where('last_attempt_at', '<', now()->subMinutes($minutes))
        ->get();
    $uncertain = WhatsAppMessage::query()
        ->where('status', WhatsAppMessage::STATUS_PROCESSING)
        ->where('last_attempt_at', '<', now()->subMinutes($minutes))
        ->update([
            'status' => WhatsAppMessage::STATUS_UNKNOWN,
            'error' => encrypt('El worker se interrumpio durante el envio; no se reenvia para evitar duplicados.'),
        ]);
    $queued = 0;

    foreach ($messages as $message) {
        $claimed = WhatsAppMessage::query()
            ->whereKey($message->id)
            ->where('status', WhatsAppMessage::STATUS_RETRYING)
            ->where('last_attempt_at', '<', now()->subMinutes($minutes))
            ->update(['status' => WhatsAppMessage::STATUS_QUEUED]);

        if ($claimed !== 1) {
            continue;
        }

        try {
            SendWhatsAppTemplate::dispatch($message->id);
            $queued++;
        } catch (\Throwable $exception) {
            $message->refresh()->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 500),
                'failed_at' => now(),
            ]);
        }
    }

    $this->info("Reintentos recuperados: {$queued}. Envios inciertos enviados a revision: {$uncertain}");

    return self::SUCCESS;
})->purpose('Recupera mensajes atascados por interrupciones del worker');

Artisan::command('followups:send {--limit=50 : Cantidad maxima de seguimientos a programar}', function () {
    $limit = max(1, min(500, (int) $this->option('limit')));
    $followups = CustomerFollowup::query()
        ->with(['store', 'user', 'whatsappMessage'])
        ->whereIn('status', [
            CustomerFollowup::STATUS_PENDING,
            CustomerFollowup::STATUS_QUEUED,
        ])
        ->where('scheduled_for', '<=', now())
        ->oldest('scheduled_for')
        ->limit($limit)
        ->get();

    $queued = 0;
    $skipped = 0;
    $alreadyQueued = 0;

    foreach ($followups as $followup) {
        $message = $followup->whatsappMessage;
        $store = $followup->store;
        $user = $followup->user ?: $store?->user;
        $isSubscriptionReminder = in_array($followup->type, [
            CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED,
        ], true);
        $isExpiredReminder = $followup->type === CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED;
        $userCanReceiveSubscriptionReminder = $isExpiredReminder
            ? (bool) $user?->is_active
            : (bool) $user?->isActive();
        $subscriptionReminderIsEligible = $isSubscriptionReminder
            && $store
            && $user
            && $store->is_active
            && $userCanReceiveSubscriptionReminder
            && $store->subscription_ends_at
            && $store->whatsappNumber() !== ''
            && ($isExpiredReminder
                ? in_array($store->subscriptionStatus(), [Store::SUBSCRIPTION_ACTIVE, Store::SUBSCRIPTION_EXPIRED], true)
                : $store->subscriptionStatus() === Store::SUBSCRIPTION_ACTIVE);
        $trialFollowupIsEligible = ! $isSubscriptionReminder
            && $store
            && $user
            && $store->is_active
            && $user->isActive()
            && $store->isTrialing()
            && $store->whatsappNumber() !== '';

        if ($message && in_array($message->status, [
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
        ], true)) {
            $followup->update([
                'status' => CustomerFollowup::STATUS_SENT,
                'sent_at' => $message->sent_at ?: now(),
                'failed_at' => null,
                'skipped_at' => null,
                'cancelled_at' => null,
                'error' => null,
            ]);

            continue;
        }

        if ($message && $message->status === WhatsAppMessage::STATUS_PROCESSING) {
            $alreadyQueued++;

            continue;
        }

        if (($isSubscriptionReminder && ! $subscriptionReminderIsEligible)
            || (! $isSubscriptionReminder && ! $trialFollowupIsEligible)) {
            if ($message && in_array($message->status, [
                WhatsAppMessage::STATUS_QUEUED,
                WhatsAppMessage::STATUS_RETRYING,
            ], true)) {
                $message->update([
                    'status' => WhatsAppMessage::STATUS_CANCELLED,
                    'error' => 'La tienda ya no es elegible para este seguimiento.',
                    'cancelled_at' => now(),
                ]);
            }

            $followup->update([
                'status' => CustomerFollowup::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'error' => 'La tienda ya no es elegible para este seguimiento.',
            ]);
            $skipped++;

            continue;
        }

        if ($message && in_array($message->status, [
            WhatsAppMessage::STATUS_QUEUED,
        ], true)) {
            $alreadyQueued++;

            continue;
        }

        if ($message && $message->status === WhatsAppMessage::STATUS_RETRYING) {
            try {
                SendWhatsAppTemplate::dispatch($message->id);
                $alreadyQueued++;
            } catch (\Throwable $exception) {
                $followup->update([
                    'status' => CustomerFollowup::STATUS_FAILED,
                    'error' => Str::limit($exception->getMessage(), 500),
                    'failed_at' => now(),
                ]);
            }

            continue;
        }

        if ($message && in_array($message->status, [
            WhatsAppMessage::STATUS_FAILED,
            WhatsAppMessage::STATUS_UNKNOWN,
            WhatsAppMessage::STATUS_CANCELLED,
        ], true)) {
            $followup->update([
                'status' => $message->status === WhatsAppMessage::STATUS_CANCELLED
                    ? CustomerFollowup::STATUS_CANCELLED
                    : CustomerFollowup::STATUS_FAILED,
                'error' => $message->error ?: 'El mensaje de WhatsApp asociado fallo.',
                'failed_at' => $message->status === WhatsAppMessage::STATUS_CANCELLED ? null : now(),
                'cancelled_at' => $message->status === WhatsAppMessage::STATUS_CANCELLED ? now() : null,
            ]);

            continue;
        }

        if (in_array($followup->type, [
            CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED,
        ], true) && (
            ! $store->subscription_ends_at
            || $followup->context_key !== 'subscription:'.$store->subscription_ends_at->toDateString()
            || ($followup->type === CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED
                ? ! in_array($store->subscriptionStatus(), [Store::SUBSCRIPTION_ACTIVE, Store::SUBSCRIPTION_EXPIRED], true)
                : $store->subscriptionStatus() !== Store::SUBSCRIPTION_ACTIVE)
            || ($followup->type === CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED
                ? $followup->scheduled_for->copy()->startOfDay()->lt($store->subscription_ends_at->copy()->startOfDay())
                : $followup->scheduled_for->copy()->startOfDay()->ne($store->subscription_ends_at->copy()->subDays(match ($followup->type) {
                    CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE => 3,
                    CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE => 1,
                    default => 0,
                })->startOfDay()))
        )) {
            $followup->update([
                'status' => CustomerFollowup::STATUS_SKIPPED,
                'skipped_at' => now(),
                'failed_at' => null,
                'cancelled_at' => null,
                'error' => 'El vencimiento del plan cambio o ya no aplica.',
            ]);
            $skipped++;

            continue;
        }

        $phone = $store->whatsappNumber();
        $fingerprintParts = [
            'followup',
            $followup->id,
            $store->id,
            $followup->type,
            $followup->context_key,
            $followup->template,
        ];
        $fingerprint = hash('sha256', implode('|', $fingerprintParts));

        $existingMessage = WhatsAppMessage::where('fingerprint', $fingerprint)->first();

        if (! $followup->whatsapp_message_id
            && $existingMessage
            && in_array($existingMessage->status, [
                WhatsAppMessage::STATUS_FAILED,
                WhatsAppMessage::STATUS_UNKNOWN,
                WhatsAppMessage::STATUS_CANCELLED,
            ], true)) {
            $fingerprint = hash('sha256', implode('|', [
                ...$fingerprintParts,
                'revived',
                $followup->updated_at?->timestamp ?? now()->timestamp,
            ]));
        }

        try {
            $message = null;
            $message = WhatsAppMessage::firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                    'audience' => 'customer',
                    'template' => $followup->template,
                    'recipient_hash' => hash('sha256', $phone),
                    'recipient' => $phone,
                    'parameters' => $followup->parameters,
                    'status' => WhatsAppMessage::STATUS_QUEUED,
                ],
            );

            if (! $message->wasRecentlyCreated && in_array($message->status, [
                WhatsAppMessage::STATUS_FAILED,
                WhatsAppMessage::STATUS_UNKNOWN,
                WhatsAppMessage::STATUS_CANCELLED,
            ], true)) {
                $followup->update([
                    'whatsapp_message_id' => $message->id,
                    'status' => $message->status === WhatsAppMessage::STATUS_CANCELLED
                        ? CustomerFollowup::STATUS_CANCELLED
                        : CustomerFollowup::STATUS_FAILED,
                    'error' => $message->error ?: 'El mensaje de WhatsApp asociado fallo.',
                    'failed_at' => $message->status === WhatsAppMessage::STATUS_CANCELLED ? null : now(),
                    'cancelled_at' => $message->status === WhatsAppMessage::STATUS_CANCELLED ? now() : null,
                ]);

                continue;
            }

            $followup->update([
                'whatsapp_message_id' => $message->id,
                'status' => CustomerFollowup::STATUS_QUEUED,
                'error' => null,
                'failed_at' => null,
                'skipped_at' => null,
                'cancelled_at' => null,
            ]);

            SendWhatsAppTemplate::dispatch($message->id);
            $queued++;
        } catch (\Throwable $exception) {
            if (isset($message) && $message) {
                $message->update([
                    'status' => WhatsAppMessage::STATUS_FAILED,
                    'error' => Str::limit($exception->getMessage(), 500),
                    'failed_at' => now(),
                ]);
            }

            $followup->update([
                'status' => CustomerFollowup::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 500),
                'failed_at' => now(),
            ]);
        }
    }

    $this->info("Seguimientos programados: {$queued}. Ya en cola: {$alreadyQueued}. Omitidos: {$skipped}.");

    return self::SUCCESS;
})->purpose('Programa seguimientos comerciales de prueba gratis por WhatsApp');

Artisan::command('followups:schedule-subscriptions {--limit=500 : Cantidad maxima de tiendas activas a revisar}', function () {
    $limit = max(1, min(5000, (int) $this->option('limit')));
    $scheduler = app(CustomerFollowupScheduler::class);
    $reviewedStores = 0;
    $availableReminders = 0;
    $today = now()->toDateString();

    Store::query()
        ->with('user')
        ->where('is_active', true)
        ->whereIn('subscription_status', [Store::SUBSCRIPTION_ACTIVE, Store::SUBSCRIPTION_EXPIRED])
        ->whereNotNull('subscription_ends_at')
        ->whereHas('user', function ($query) use ($today) {
            $query
                ->where('is_active', true)
                ->where(function ($query) use ($today) {
                    $query
                        ->whereNull('active_starts_at')
                        ->orWhereDate('active_starts_at', '<=', $today);
                });
        })
        ->orderBy('subscription_ends_at')
        ->limit($limit)
        ->get()
        ->each(function (Store $store) use ($scheduler, &$reviewedStores, &$availableReminders) {
            $reviewedStores++;
            $availableReminders += $scheduler->scheduleSubscriptionReminders($store)->count();
        });

    $this->info("Tiendas revisadas: {$reviewedStores}. Recordatorios disponibles: {$availableReminders}.");

    return self::SUCCESS;
})->purpose('Programa recordatorios de planes activos y vencidos');

Schedule::command('whatsapp:prune')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('whatsapp:recover-stale')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('stores:expire-subscriptions')
    ->dailyAt('00:20')
    ->withoutOverlapping();

Schedule::command('followups:send')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('followups:schedule-subscriptions')
    ->dailyAt('08:10')
    ->withoutOverlapping();
