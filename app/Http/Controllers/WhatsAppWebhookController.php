<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private WhatsAppStatusService $statuses)
    {
    }

    public function verify(Request $request): Response
    {
        $mode = $this->queryValue($request, 'hub.mode');
        $receivedToken = $this->queryValue($request, 'hub.verify_token');
        $challenge = $this->queryValue($request, 'hub.challenge');
        $verifyToken = (string) config('services.whatsapp.verify_token');

        if (
            $mode === 'subscribe'
            && $verifyToken !== ''
            && is_string($receivedToken)
            && hash_equals($verifyToken, $receivedToken)
        ) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Invalid verification token', 403);
    }

    public function receive(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            return response('Invalid signature', 401);
        }

        if ($request->input('object') !== 'whatsapp_business_account') {
            return response('Ignored', 200);
        }

        Log::info('WhatsApp webhook received', [
            'entries' => count($request->input('entry', [])),
        ]);

        $this->updateMessageStatuses($request->input('entry', []));

        return response('EVENT_RECEIVED', 200);
    }

    private function queryValue(Request $request, string $key): mixed
    {
        return $request->query($key, $request->query(str_replace('.', '_', $key)));
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');

        if ($appSecret === '') {
            return ! app()->environment('production');
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! is_string($signature) || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    private function updateMessageStatuses(array $entries): void
    {
        foreach ($entries as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    $providerMessageId = (string) data_get($status, 'id');

                    if ($providerMessageId === '') {
                        continue;
                    }

                    $providerStatus = (string) data_get($status, 'status');
                    $this->statuses->record(
                        $providerMessageId,
                        $providerStatus,
                        data_get($status, 'errors.0.title'),
                    );
                }
            }
        }
    }
}
