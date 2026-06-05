<?php

namespace Tests\Feature;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppStatusEvent;
use App\Services\WhatsAppStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_can_verify_the_webhook(): void
    {
        config(['services.whatsapp.verify_token' => 'test-token']);

        $response = $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-token&hub.challenge=123456');

        $response->assertOk()->assertSeeText('123456');
    }

    public function test_webhook_verification_rejects_an_invalid_token(): void
    {
        config(['services.whatsapp.verify_token' => 'test-token']);

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123456')
            ->assertForbidden();
    }

    public function test_webhook_accepts_whatsapp_events_without_a_configured_app_secret(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $this->postJson('/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'example']],
        ])->assertOk()->assertSeeText('EVENT_RECEIVED');
    }

    public function test_webhook_accepts_a_valid_meta_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'app-secret']);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'example']],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'app-secret');

        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload)->assertOk()->assertSeeText('EVENT_RECEIVED');
    }

    public function test_webhook_rejects_an_invalid_meta_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'app-secret']);

        $this->postJson('/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
        ], ['X-Hub-Signature-256' => 'sha256=invalid'])->assertUnauthorized();
    }

    public function test_production_webhook_rejects_requests_when_app_secret_is_missing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.whatsapp.app_secret' => null]);

        $this->postJson('/webhooks/whatsapp', [
            'object' => 'whatsapp_business_account',
        ])->assertUnauthorized();
    }

    public function test_webhook_updates_provider_message_delivery_status(): void
    {
        config(['services.whatsapp.app_secret' => 'app-secret']);

        $message = WhatsAppMessage::create([
            'audience' => 'customer',
            'template' => 'welcome',
            'recipient_hash' => hash('sha256', '573001112233'),
            'recipient' => '573001112233',
            'parameters' => [],
            'fingerprint' => hash('sha256', 'delivery-test'),
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'wamid.delivery',
        ]);

        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.delivery',
                            'status' => 'delivered',
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, 'app-secret');

        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload)->assertOk();

        $message->refresh();

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_late_failed_webhook_does_not_downgrade_a_read_message(): void
    {
        config(['services.whatsapp.app_secret' => 'app-secret']);

        $message = WhatsAppMessage::create([
            'audience' => 'customer',
            'template' => 'welcome',
            'recipient_hash' => hash('sha256', '573001112233'),
            'recipient' => '573001112233',
            'parameters' => [],
            'fingerprint' => hash('sha256', 'late-failed-test'),
            'status' => WhatsAppMessage::STATUS_READ,
            'provider_message_id' => 'wamid.read',
            'read_at' => now(),
        ]);
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => [['value' => ['statuses' => [[
                'id' => 'wamid.read',
                'status' => 'failed',
                'errors' => [['title' => 'Late failure']],
            ]]]]]]],
        ], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'app-secret');

        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload)->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_READ, $message->refresh()->status);
        $this->assertNull($message->failed_at);
    }

    public function test_early_webhook_is_reconciled_after_provider_id_is_saved(): void
    {
        config(['services.whatsapp.app_secret' => 'app-secret']);
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => [['value' => ['statuses' => [[
                'id' => 'wamid.early',
                'status' => 'delivered',
            ]]]]]]],
        ], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'app-secret');

        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $payload)->assertOk();

        $this->assertDatabaseHas('whatsapp_status_events', [
            'provider_message_id' => 'wamid.early',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $message = WhatsAppMessage::create([
            'audience' => 'customer',
            'template' => 'welcome',
            'recipient_hash' => hash('sha256', '573001112233'),
            'recipient' => '573001112233',
            'parameters' => [],
            'fingerprint' => hash('sha256', 'early-event-test'),
            'status' => WhatsAppMessage::STATUS_SENT,
            'provider_message_id' => 'wamid.early',
        ]);

        app(WhatsAppStatusService::class)->reconcile($message);

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->refresh()->status);
        $this->assertDatabaseMissing('whatsapp_status_events', ['provider_message_id' => 'wamid.early']);
    }

    public function test_early_webhooks_do_not_downgrade_before_reconciliation(): void
    {
        $statuses = app(WhatsAppStatusService::class);

        $statuses->record('wamid.out-of-order', WhatsAppMessage::STATUS_READ);
        $statuses->record('wamid.out-of-order', WhatsAppMessage::STATUS_SENT);
        $statuses->record('wamid.out-of-order', WhatsAppMessage::STATUS_FAILED, 'Late failure');

        $event = WhatsAppStatusEvent::where('provider_message_id', 'wamid.out-of-order')->firstOrFail();
        $this->assertSame(WhatsAppMessage::STATUS_READ, $event->status);
    }
}
