<?php

namespace App\Services;

use App\Exceptions\WhatsAppDeliveryUnknownException;
use App\Exceptions\WhatsAppRetryableException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppCloudApiService
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.access_token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    public function sendTemplate(string $phone, string $template, array $parameters): Response
    {
        return $this->sendTemplatePayload($phone, $template, $this->bodyComponents($parameters));
    }

    public function sendText(string $phone, string $body): Response
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('WhatsApp Cloud API no esta configurado.');
        }

        $phone = $this->normalizePhone($phone);
        $body = trim($body);

        if (! preg_match('/^\d{8,15}$/', $phone)) {
            throw new RuntimeException('El numero de WhatsApp no es valido.');
        }

        if ($body === '' || mb_strlen($body) > 4096) {
            throw new RuntimeException('El mensaje debe tener entre 1 y 4096 caracteres.');
        }

        $response = Http::withToken((string) config('services.whatsapp.access_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->messagesUrl(), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if ($response->failed()) {
            $message = (string) ($response->json('error.message') ?: 'Meta rechazo el mensaje de WhatsApp.');

            if ($response->status() === 429 || $response->serverError()) {
                throw new WhatsAppRetryableException($message);
            }

            throw new RuntimeException($message);
        }

        if (! is_string($response->json('messages.0.id')) || trim((string) $response->json('messages.0.id')) === '') {
            throw new WhatsAppDeliveryUnknownException('Meta respondio sin identificador del mensaje.');
        }

        return $response;
    }

    public function sendAuthenticationCodeTemplate(string $phone, string $template, string $code): Response
    {
        $code = trim($code);

        if (! preg_match('/^\d{4,15}$/', $code)) {
            throw new RuntimeException('El codigo de autenticacion de WhatsApp no es valido.');
        }

        return $this->sendTemplatePayload($phone, $template, [
            ...$this->bodyComponents([$code]),
            [
                'type' => 'button',
                'sub_type' => (string) config('services.whatsapp.authentication_button_sub_type', 'url'),
                'index' => '0',
                'parameters' => [[
                    'type' => 'text',
                    'text' => $code,
                ]],
            ],
        ]);
    }

    private function sendTemplatePayload(string $phone, string $template, array $components): Response
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('WhatsApp Cloud API no esta configurado.');
        }

        $phone = $this->normalizePhone($phone);

        if (! preg_match('/^\d{8,15}$/', $phone)) {
            throw new RuntimeException('El numero de WhatsApp no es valido.');
        }

        $response = Http::withToken((string) config('services.whatsapp.access_token'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->messagesUrl(), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => [
                        'code' => (string) config('services.whatsapp.template_language', 'es_CO'),
                    ],
                    'components' => $components,
                ],
            ]);

        if ($response->failed()) {
            $message = (string) ($response->json('error.message') ?: 'Meta rechazo el mensaje de WhatsApp.');

            if ($response->status() === 429 || $response->serverError()) {
                throw new WhatsAppRetryableException($message);
            }

            throw new RuntimeException($message);
        }

        if (! is_string($response->json('messages.0.id')) || trim((string) $response->json('messages.0.id')) === '') {
            throw new WhatsAppDeliveryUnknownException('Meta respondio sin identificador del mensaje.');
        }

        return $response;
    }

    private function bodyComponents(array $parameters): array
    {
        return [[
            'type' => 'body',
            'parameters' => collect($parameters)
                ->map(fn ($value) => [
                    'type' => 'text',
                    'text' => trim((string) $value) ?: '-',
                ])
                ->values()
                ->all(),
        ]];
    }

    public function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            return '57'.$phone;
        }

        return $phone;
    }

    private function messagesUrl(): string
    {
        $version = trim((string) config('services.whatsapp.graph_version', 'v24.0'), '/');
        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }
}
