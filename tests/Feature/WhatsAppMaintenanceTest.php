<?php

use App\Jobs\SendWhatsAppTemplate;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function whatsappMessage(array $attributes = []): WhatsAppMessage
{
    $createdAt = $attributes['created_at'] ?? null;
    unset($attributes['created_at']);

    $message = WhatsAppMessage::create(array_merge([
        'audience' => 'customer',
        'template' => 'welcome',
        'recipient_hash' => hash('sha256', '573001112233'),
        'recipient' => '573001112233',
        'parameters' => [],
        'fingerprint' => hash('sha256', uniqid('maintenance-', true)),
        'status' => WhatsAppMessage::STATUS_READ,
    ], $attributes));

    if ($createdAt) {
        DB::table('whatsapp_messages')
            ->where('id', $message->id)
            ->update(['created_at' => $createdAt]);
    }

    return $message->refresh();
}

test('whatsapp prune removes only old terminal messages', function () {
    $old = whatsappMessage(['created_at' => now()->subDays(40)]);
    $recent = whatsappMessage(['created_at' => now()->subDays(5)]);
    $pending = whatsappMessage([
        'status' => WhatsAppMessage::STATUS_QUEUED,
        'created_at' => now()->subDays(40),
    ]);

    $this->artisan('whatsapp:prune --days=30')->assertSuccessful();

    $this->assertDatabaseMissing('whatsapp_messages', ['id' => $old->id]);
    $this->assertDatabaseHas('whatsapp_messages', ['id' => $recent->id]);
    $this->assertDatabaseHas('whatsapp_messages', ['id' => $pending->id]);
});

test('whatsapp retry command queues failed messages only', function () {
    Queue::fake();
    $failed = whatsappMessage([
        'status' => WhatsAppMessage::STATUS_FAILED,
        'failed_at' => now(),
        'error' => 'Permanent failure',
    ]);
    whatsappMessage(['status' => WhatsAppMessage::STATUS_UNKNOWN]);

    $this->artisan('whatsapp:retry-failed')->assertSuccessful();

    expect($failed->refresh()->status)->toBe(WhatsAppMessage::STATUS_QUEUED)
        ->and($failed->failed_at)->toBeNull()
        ->and($failed->error)->toBeNull();
    Queue::assertPushed(SendWhatsAppTemplate::class, 1);
});

test('whatsapp recover stale retries without resending uncertain processing messages', function () {
    Queue::fake();
    $uncertain = whatsappMessage([
        'status' => WhatsAppMessage::STATUS_PROCESSING,
        'last_attempt_at' => now()->subMinutes(30),
    ]);
    $retry = whatsappMessage([
        'status' => WhatsAppMessage::STATUS_RETRYING,
        'last_attempt_at' => now()->subMinutes(30),
    ]);
    $active = whatsappMessage([
        'status' => WhatsAppMessage::STATUS_PROCESSING,
        'last_attempt_at' => now()->subMinute(),
    ]);

    $this->artisan('whatsapp:recover-stale --minutes=15')->assertSuccessful();

    expect($uncertain->refresh()->status)->toBe(WhatsAppMessage::STATUS_UNKNOWN)
        ->and($retry->refresh()->status)->toBe(WhatsAppMessage::STATUS_QUEUED)
        ->and($active->refresh()->status)->toBe(WhatsAppMessage::STATUS_PROCESSING);
    Queue::assertPushed(SendWhatsAppTemplate::class, 1);
});
