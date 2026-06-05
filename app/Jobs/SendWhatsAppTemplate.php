<?php

namespace App\Jobs;

use App\Exceptions\WhatsAppDeliveryUnknownException;
use App\Exceptions\WhatsAppRetryableException;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppStatusService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SendWhatsAppTemplate implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $messageId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(WhatsAppCloudApiService $whatsApp, WhatsAppStatusService $statuses): void
    {
        $claimed = WhatsAppMessage::whereKey($this->messageId)
            ->whereIn('status', [
                WhatsAppMessage::STATUS_QUEUED,
                WhatsAppMessage::STATUS_RETRYING,
            ])
            ->update([
                'status' => WhatsAppMessage::STATUS_PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $message = WhatsAppMessage::findOrFail($this->messageId);

        try {
            $response = $whatsApp->sendTemplate(
                $message->recipient,
                $message->template,
                $message->parameters,
            );

            $message->update([
                'status' => WhatsAppMessage::STATUS_SENT,
                'provider_message_id' => $response->json('messages.0.id'),
                'sent_at' => now(),
                'failed_at' => null,
                'error' => null,
            ]);
        } catch (ConnectionException|WhatsAppDeliveryUnknownException $exception) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_UNKNOWN,
                'error' => Str::limit($exception->getMessage(), 500),
            ]);
        } catch (WhatsAppRetryableException $exception) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_RETRYING,
                'error' => Str::limit($exception->getMessage(), 500),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 500),
                'failed_at' => now(),
            ]);

            return;
        }

        try {
            $statuses->reconcile($message->refresh());
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        WhatsAppMessage::whereKey($this->messageId)
            ->where('status', WhatsAppMessage::STATUS_RETRYING)
            ->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => encrypt(Str::limit($exception->getMessage(), 500)),
                'failed_at' => now(),
            ]);
    }
}
