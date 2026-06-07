<?php

namespace App\Services;

use App\Jobs\SendWhatsAppTemplate;
use App\Models\Store;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppRegistrationNotifier
{
    public function __construct(private WhatsAppCloudApiService $whatsApp)
    {
    }

    public function notify(User $user, Store $store): void
    {
        if (! $this->whatsApp->isConfigured()) {
            return;
        }

        $messages = [
            [
                'audience' => 'admin',
                'phone' => config('services.whatsapp.admin_phone'),
                'template' => config('services.whatsapp.admin_registration_template'),
                'parameters' => [
                    $user->name,
                    $store->name,
                    $store->whatsapp,
                    $user->email,
                ],
            ],
            [
                'audience' => 'customer',
                'phone' => $store->whatsapp,
                'template' => config('services.whatsapp.customer_welcome_template'),
                'parameters' => [
                    $user->name,
                    $store->name,
                ],
            ],
        ];

        foreach ($messages as $message) {
            try {
                $this->queueMessage(
                    $user,
                    $store,
                    $message['audience'],
                    $message['phone'],
                    $message['template'],
                    $message['parameters'],
                );
            } catch (Throwable $exception) {
                Log::warning('No se pudo programar un mensaje de registro por WhatsApp.', [
                    'store_id' => $store->id,
                    'audience' => $message['audience'],
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    private function queueMessage(
        User $user,
        Store $store,
        string $audience,
        mixed $phone,
        mixed $template,
        array $parameters,
    ): void {
        $phone = $this->whatsApp->normalizePhone((string) $phone);
        $template = trim((string) $template);

        if ($phone === '' || $template === '') {
            return;
        }

        $fingerprint = hash('sha256', implode('|', [
            'registration',
            $store->id,
            $audience,
            $template,
        ]));

        $message = WhatsAppMessage::firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'audience' => $audience,
                'template' => $template,
                'recipient_hash' => hash('sha256', $phone),
                'recipient' => $phone,
                'parameters' => $parameters,
                'status' => WhatsAppMessage::STATUS_QUEUED,
            ],
        );

        if (! $message->wasRecentlyCreated) {
            return;
        }

        try {
            SendWhatsAppTemplate::dispatch($message->id);
        } catch (Throwable $exception) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => 'No se pudo guardar el trabajo en la cola.',
                'failed_at' => now(),
            ]);

            Log::warning('No se pudo guardar un mensaje de WhatsApp en la cola.', [
                'message_id' => $message->id,
                'audience' => $audience,
                'exception' => $exception::class,
            ]);
        }
    }
}
