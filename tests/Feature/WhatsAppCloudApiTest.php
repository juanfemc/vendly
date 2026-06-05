<?php

use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppStatusService;
use App\Exceptions\WhatsAppRetryableException;
use App\Jobs\SendWhatsAppTemplate;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

test('whatsapp cloud api sends approved templates with normalized phone', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
        'services.whatsapp.graph_version' => 'v24.0',
        'services.whatsapp.template_language' => 'es_CO',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
    ]);

    app(WhatsAppCloudApiService::class)->sendTemplate(
        '+57 300 111 2233',
        'vendly_bienvenida_cliente',
        ['Cliente', 'Tienda', 7, 'https://vendlysuite.com/dashboard'],
    );

    Http::assertSent(fn ($request) =>
        $request->url() === 'https://graph.facebook.com/v24.0/123456/messages'
        && $request['to'] === '573001112233'
        && $request['type'] === 'template'
        && $request['template']['name'] === 'vendly_bienvenida_cliente'
        && count($request['template']['components'][0]['parameters']) === 4
    );

    expect(app(WhatsAppCloudApiService::class)->normalizePhone('300 111 2233'))
        ->toBe('573001112233');
});

test('whatsapp cloud api rejects invalid phones before calling meta', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
    ]);

    Http::fake();

    expect(fn () => app(WhatsAppCloudApiService::class)->sendTemplate('invalid', 'template', []))
        ->toThrow(\RuntimeException::class);

    Http::assertNothingSent();
});

test('whatsapp job records the provider message id without exposing recipient in the queue job', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.saved']]], 200),
    ]);

    $message = WhatsAppMessage::create([
        'audience' => 'customer',
        'template' => 'welcome',
        'recipient_hash' => hash('sha256', '573001112233'),
        'recipient' => '573001112233',
        'parameters' => ['Cliente'],
        'fingerprint' => hash('sha256', 'job-test'),
        'status' => WhatsAppMessage::STATUS_QUEUED,
    ]);

    $rawMessage = DB::table('whatsapp_messages')->where('id', $message->id)->first();

    expect($rawMessage->recipient)->not->toContain('573001112233')
        ->and($rawMessage->parameters)->not->toContain('Cliente');

    (new SendWhatsAppTemplate($message->id))->handle(app(WhatsAppCloudApiService::class), app(WhatsAppStatusService::class));

    $message->refresh();

    expect($message->status)->toBe(WhatsAppMessage::STATUS_SENT)
        ->and($message->provider_message_id)->toBe('wamid.saved')
        ->and((new SendWhatsAppTemplate($message->id))->messageId)->toBe($message->id);
});

test('whatsapp job marks a successful response without message id as unknown', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
    ]);
    Http::fake(['graph.facebook.com/*' => Http::response([], 200)]);

    $message = WhatsAppMessage::create([
        'audience' => 'customer',
        'template' => 'welcome',
        'recipient_hash' => hash('sha256', '573001112233'),
        'recipient' => '573001112233',
        'parameters' => [],
        'fingerprint' => hash('sha256', 'unknown-test'),
        'status' => WhatsAppMessage::STATUS_QUEUED,
    ]);

    (new SendWhatsAppTemplate($message->id))->handle(app(WhatsAppCloudApiService::class), app(WhatsAppStatusService::class));

    expect($message->refresh()->status)->toBe(WhatsAppMessage::STATUS_UNKNOWN)
        ->and($message->provider_message_id)->toBeNull();
});

test('whatsapp job retries temporary meta failures', function () {
    config([
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => '123456',
    ]);
    Http::fakeSequence()
        ->push(['error' => ['message' => 'Temporary failure']], 500)
        ->push(['messages' => [['id' => 'wamid.retry']]], 200);

    $message = WhatsAppMessage::create([
        'audience' => 'customer',
        'template' => 'welcome',
        'recipient_hash' => hash('sha256', '573001112233'),
        'recipient' => '573001112233',
        'parameters' => [],
        'fingerprint' => hash('sha256', 'retry-test'),
        'status' => WhatsAppMessage::STATUS_QUEUED,
    ]);
    $job = new SendWhatsAppTemplate($message->id);

    expect(fn () => $job->handle(app(WhatsAppCloudApiService::class), app(WhatsAppStatusService::class)))
        ->toThrow(WhatsAppRetryableException::class);
    expect($message->refresh()->status)->toBe(WhatsAppMessage::STATUS_RETRYING);

    $job->handle(app(WhatsAppCloudApiService::class), app(WhatsAppStatusService::class));

    expect($message->refresh()->status)->toBe(WhatsAppMessage::STATUS_SENT)
        ->and($message->attempts)->toBe(2)
        ->and($message->provider_message_id)->toBe('wamid.retry');
});
