<?php

namespace App\Services;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppChatMessage;
use App\Models\WhatsAppStatusEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsAppStatusService
{
    public function record(string $providerMessageId, string $status, ?string $error = null): void
    {
        if (! $this->isSupported($status)) {
            return;
        }

        try {
            DB::transaction(function () use ($providerMessageId, $status, $error) {
                $message = WhatsAppMessage::where('provider_message_id', $providerMessageId)
                    ->lockForUpdate()
                    ->first();

                if ($message) {
                    $this->apply($message, $status, $error);

                    return;
                }

                $chatMessage = WhatsAppChatMessage::where('provider_message_id', $providerMessageId)
                    ->lockForUpdate()
                    ->first();

                if ($chatMessage) {
                    $this->applyChatStatus($chatMessage, $status, $error);

                    return;
                }

                $event = WhatsAppStatusEvent::where('provider_message_id', $providerMessageId)
                    ->lockForUpdate()
                    ->first();

                if ($event && ! $this->canAdvance($event->status, $status)) {
                    return;
                }

                ($event ?: new WhatsAppStatusEvent(['provider_message_id' => $providerMessageId]))
                    ->fill([
                        'status' => $status,
                        'error' => $error ? Str::limit($error, 500) : null,
                        'occurred_at' => now(),
                    ])
                    ->save();
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $this->updateExistingEvent($providerMessageId, $status, $error);
        }

        $message = WhatsAppMessage::where('provider_message_id', $providerMessageId)->first();

        if ($message) {
            $this->reconcile($message);
        }

        $chatMessage = WhatsAppChatMessage::where('provider_message_id', $providerMessageId)->first();

        if ($chatMessage) {
            $this->reconcileChat($chatMessage);
        }
    }

    public function reconcile(WhatsAppMessage $message): void
    {
        if (! $message->provider_message_id) {
            return;
        }

        DB::transaction(function () use ($message) {
            $lockedMessage = WhatsAppMessage::whereKey($message->id)->lockForUpdate()->first();
            $event = WhatsAppStatusEvent::where('provider_message_id', $message->provider_message_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedMessage || ! $event) {
                return;
            }

            $this->apply($lockedMessage, $event->status, $event->error);
            $event->delete();
        }, 3);
    }

    public function reconcileChat(WhatsAppChatMessage $message): void
    {
        if (! $message->provider_message_id) {
            return;
        }

        DB::transaction(function () use ($message) {
            $lockedMessage = WhatsAppChatMessage::whereKey($message->id)->lockForUpdate()->first();
            $event = WhatsAppStatusEvent::where('provider_message_id', $message->provider_message_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedMessage || ! $event) {
                return;
            }

            $this->applyChatStatus($lockedMessage, $event->status, $event->error);
            $event->delete();
        }, 3);
    }

    private function updateExistingEvent(string $providerMessageId, string $status, ?string $error): void
    {
        DB::transaction(function () use ($providerMessageId, $status, $error) {
            $event = WhatsAppStatusEvent::where('provider_message_id', $providerMessageId)
                ->lockForUpdate()
                ->first();

            if (! $event || ! $this->canAdvance($event->status, $status)) {
                return;
            }

            $event->update([
                'status' => $status,
                'error' => $error ? Str::limit($error, 500) : null,
                'occurred_at' => now(),
            ]);
        }, 3);
    }

    private function apply(WhatsAppMessage $message, string $status, ?string $error): void
    {
        if (! $this->canAdvance($message->status, $status)) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === WhatsAppMessage::STATUS_DELIVERED) {
            $updates['delivered_at'] = now();
        } elseif ($status === WhatsAppMessage::STATUS_READ) {
            $updates['read_at'] = now();
        } elseif ($status === WhatsAppMessage::STATUS_FAILED) {
            $updates['failed_at'] = now();
            $updates['error'] = Str::limit($error ?: 'Meta reporto el envio como fallido.', 500);
        }

        $message->update($updates);
    }

    private function applyChatStatus(WhatsAppChatMessage $message, string $status, ?string $error): void
    {
        if (! $this->canAdvance($message->status, $status)) {
            return;
        }

        $updates = ['status' => $status];

        if ($status === WhatsAppMessage::STATUS_DELIVERED) {
            $updates['delivered_at'] = now();
        } elseif ($status === WhatsAppMessage::STATUS_READ) {
            $updates['read_at'] = now();
        } elseif ($status === WhatsAppMessage::STATUS_FAILED) {
            $updates['failed_at'] = now();
            $updates['error'] = Str::limit($error ?: 'Meta reporto el envio como fallido.', 500);
        }

        $message->update($updates);
    }

    private function isSupported(string $status): bool
    {
        return in_array($status, [
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
            WhatsAppMessage::STATUS_FAILED,
        ], true);
    }

    private function canAdvance(string $current, string $next): bool
    {
        if ($next === WhatsAppMessage::STATUS_FAILED) {
            return ! in_array($current, [
                WhatsAppMessage::STATUS_DELIVERED,
                WhatsAppMessage::STATUS_READ,
            ], true);
        }

        $rank = [
            WhatsAppMessage::STATUS_QUEUED => 0,
            WhatsAppMessage::STATUS_PROCESSING => 0,
            WhatsAppMessage::STATUS_RETRYING => 0,
            WhatsAppMessage::STATUS_UNKNOWN => 0,
            WhatsAppMessage::STATUS_FAILED => 0,
            WhatsAppMessage::STATUS_SENT => 1,
            WhatsAppMessage::STATUS_DELIVERED => 2,
            WhatsAppMessage::STATUS_READ => 3,
        ];

        return ($rank[$next] ?? 0) >= ($rank[$current] ?? 0);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true);
    }
}
