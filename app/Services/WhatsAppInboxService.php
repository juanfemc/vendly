<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use App\Models\WhatsAppChatMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class WhatsAppInboxService
{
    public function __construct(
        private WhatsAppCloudApiService $whatsApp,
        private WhatsAppStatusService $statuses,
    )
    {
    }

    public function recordIncomingEntries(array $entries): void
    {
        foreach ($entries as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);
                $contacts = collect(data_get($value, 'contacts', []))->keyBy('wa_id');

                foreach (data_get($value, 'messages', []) as $message) {
                    $this->recordIncomingMessage($message, $contacts->get(data_get($message, 'from')), $value, true);
                }
            }
        }
    }

    public function sendReply(WhatsAppConversation $conversation, User $sender, string $body): WhatsAppChatMessage
    {
        if (! $conversation->canSendFreeText()) {
            throw new \RuntimeException('La ventana de 24 horas ya cerro. Usa una plantilla aprobada para volver a escribir.');
        }

        $body = trim($body);

        if ($body === '') {
            throw new \RuntimeException('Escribe un mensaje antes de enviarlo.');
        }

        $message = WhatsAppChatMessage::create([
            'conversation_id' => $conversation->id,
            'store_id' => $conversation->store_id,
            'sent_by_user_id' => $sender->id,
            'direction' => WhatsAppChatMessage::DIRECTION_OUTGOING,
            'message_type' => 'text',
            'body' => $body,
            'status' => 'processing',
        ]);

        try {
            $response = $this->whatsApp->sendText($conversation->contact_phone, $body);

            $message->update([
                'provider_message_id' => $response->json('messages.0.id'),
                'status' => WhatsAppChatMessage::STATUS_SENT,
                'sent_at' => now(),
                'error' => null,
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => 0,
            ]);

            $this->statuses->reconcileChat($message->refresh());
        } catch (Throwable $exception) {
            $message->update([
                'status' => WhatsAppChatMessage::STATUS_FAILED,
                'error' => Str::limit($exception->getMessage(), 500),
                'failed_at' => now(),
            ]);

            throw $exception;
        }

        return $message;
    }

    public function syncRecentTemplateMessages(?array $storeIds = null, int $limit = 150): void
    {
        if (! $this->canSyncTemplateMessages()) {
            return;
        }

        WhatsAppMessage::query()
            ->with(['store:id,user_id,name,whatsapp', 'user:id,name'])
            ->when($storeIds !== null, fn ($query) => $query->whereIn('store_id', $storeIds))
            ->whereNotNull('recipient')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (WhatsAppMessage $message) => $this->syncTemplateMessage($message));
    }

    public function syncTemplateMessage(WhatsAppMessage $message): ?WhatsAppChatMessage
    {
        if (! $this->canSyncTemplateMessages()) {
            return null;
        }

        $phone = $this->whatsApp->normalizePhone((string) $message->recipient);

        if ($phone === '') {
            return null;
        }

        $message->loadMissing(['store:id,user_id,name,whatsapp', 'user:id,name']);
        $store = $message->store;
        $occurredAt = $message->sent_at
            ?? $message->failed_at
            ?? $message->last_attempt_at
            ?? $message->created_at
            ?? now();

        return DB::transaction(function () use ($message, $phone, $store, $occurredAt) {
            $conversation = WhatsAppConversation::firstOrCreate(
                ['conversation_key' => $this->conversationKey($store?->id, $phone)],
                [
                    'store_id' => $store?->id,
                    'user_id' => $message->user_id ?: $store?->user_id,
                    'contact_phone_hash' => hash('sha256', $phone),
                    'contact_phone' => $phone,
                    'contact_name' => $message->user?->name,
                    'status' => 'open',
                    'last_message_at' => $occurredAt,
                ],
            );

            $chatMessage = WhatsAppChatMessage::query()
                ->where(function ($query) use ($message) {
                    $query->where('whatsapp_message_id', $message->id);

                    if (filled($message->provider_message_id)) {
                        $query->orWhere('provider_message_id', $message->provider_message_id);
                    }
                })
                ->lockForUpdate()
                ->first();

            $chatMessage ??= new WhatsAppChatMessage([
                'whatsapp_message_id' => $message->id,
            ]);

            $chatMessage->fill([
                'conversation_id' => $conversation->id,
                'store_id' => $store?->id,
                'sent_by_user_id' => null,
                'whatsapp_message_id' => $message->id,
                'direction' => WhatsAppChatMessage::DIRECTION_OUTGOING,
                'message_type' => 'template',
                'body' => $this->templateMessageBody($message),
                'provider_message_id' => $message->provider_message_id,
                'status' => $message->status,
                'error' => $message->error,
                'payload' => [
                    'audience' => $message->audience,
                    'template' => $message->template,
                    'parameters' => $message->parameters ?: [],
                ],
                'sent_at' => $message->sent_at,
                'delivered_at' => $message->delivered_at,
                'read_at' => $message->read_at,
                'failed_at' => $message->failed_at,
            ]);

            if (! $chatMessage->exists) {
                $chatMessage->created_at = $occurredAt;
            }

            $chatMessage->save();

            if (! $conversation->last_message_at || $conversation->last_message_at->lt($occurredAt)) {
                $conversation->update(['last_message_at' => $occurredAt]);
            }

            return $chatMessage;
        }, 3);
    }

    public function markConversationRead(WhatsAppConversation $conversation): void
    {
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }
    }

    private function recordIncomingMessage(array $message, ?array $contact, array $value, bool $canRetry = false): void
    {
        $providerMessageId = trim((string) data_get($message, 'id'));
        $phone = $this->whatsApp->normalizePhone((string) data_get($message, 'from'));

        if ($providerMessageId === '' || $phone === '') {
            return;
        }

        $sentContext = $this->sentContextFromIncomingMessage($message, $phone);
        $store = $sentContext['store'] ?? $this->storeFromBusinessPhone(data_get($value, 'metadata.display_phone_number'));
        $userId = $sentContext['user_id'] ?? $store?->user_id;
        $contactName = data_get($contact, 'profile.name');
        $receivedAt = $this->messageTimestamp($message);

        try {
            DB::transaction(function () use ($message, $providerMessageId, $phone, $store, $userId, $contactName, $receivedAt) {
                $conversation = WhatsAppConversation::firstOrCreate(
                    [
                        'conversation_key' => $this->conversationKey($store?->id, $phone),
                    ],
                    [
                        'store_id' => $store?->id,
                        'user_id' => $userId,
                        'contact_phone_hash' => hash('sha256', $phone),
                        'contact_phone' => $phone,
                        'contact_name' => $contactName,
                        'status' => 'open',
                    ],
                );

                $chatMessage = WhatsAppChatMessage::firstOrCreate(
                    ['provider_message_id' => $providerMessageId],
                    [
                        'conversation_id' => $conversation->id,
                        'store_id' => $store?->id,
                        'direction' => WhatsAppChatMessage::DIRECTION_INCOMING,
                        'message_type' => (string) data_get($message, 'type', 'unknown'),
                        'body' => $this->messageBody($message),
                        'media_id' => $this->mediaId($message),
                        'status' => WhatsAppChatMessage::STATUS_RECEIVED,
                        'payload' => Arr::only($message, ['id', 'from', 'timestamp', 'type', 'context', 'text', 'image', 'audio', 'document', 'button', 'interactive']),
                        'created_at' => $receivedAt,
                        'updated_at' => now(),
                    ],
                );

                if (! $chatMessage->wasRecentlyCreated) {
                    return;
                }

                $conversation->fill([
                    'user_id' => $conversation->user_id ?: $userId,
                    'contact_name' => $contactName ?: $conversation->contact_name,
                    'last_customer_message_at' => $receivedAt,
                    'last_message_at' => $receivedAt,
                    'unread_count' => $conversation->unread_count + 1,
                ])->save();
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            if ($canRetry && ! WhatsAppChatMessage::where('provider_message_id', $providerMessageId)->exists()) {
                $this->recordIncomingMessage($message, $contact, $value, false);
            }
        }
    }

    private function conversationKey(?int $storeId, string $phone): string
    {
        return hash('sha256', ($storeId ?: 'unassigned').'|'.$phone);
    }

    private function canSyncTemplateMessages(): bool
    {
        return Schema::hasColumn('whatsapp_chat_messages', 'whatsapp_message_id');
    }

    private function templateMessageBody(WhatsAppMessage $message): string
    {
        $parameters = collect($message->parameters ?: [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value))
            ->take(6)
            ->implode(' / ');

        $body = 'Plantilla enviada: '.$message->template;

        if ($parameters !== '') {
            $body .= "\n".$parameters;
        }

        if ($message->status === WhatsAppMessage::STATUS_CANCELLED && $message->error) {
            $body .= "\nCancelado: ".$message->error;
        } elseif ($message->status === WhatsAppMessage::STATUS_FAILED && $message->error) {
            $body .= "\nError: ".$message->error;
        }

        return $body;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true);
    }

    private function sentContextFromIncomingMessage(array $message, string $phone): array
    {
        $contextMessageId = trim((string) data_get($message, 'context.id'));

        if ($contextMessageId !== '') {
            $message = WhatsAppMessage::query()
                ->with(['store:id,user_id,name,whatsapp'])
                ->where('provider_message_id', $contextMessageId)
                ->first();

            if ($message) {
                return [
                    'store' => $message->store,
                    'user_id' => $message->user_id,
                ];
            }

            $chatMessage = WhatsAppChatMessage::query()
                ->with(['conversation.store:id,user_id,name,whatsapp'])
                ->where('provider_message_id', $contextMessageId)
                ->first();

            if ($chatMessage?->conversation) {
                return [
                    'store' => $chatMessage->conversation->store,
                    'user_id' => $chatMessage->conversation->user_id,
                ];
            }
        }

        $message = $this->lastSentContext($phone);

        if (! $message) {
            return [];
        }

        return [
            'store' => $message->store,
            'user_id' => $message->user_id,
        ];
    }

    private function lastSentContext(string $phone): ?WhatsAppMessage
    {
        return WhatsAppMessage::query()
            ->with(['store:id,user_id,name,whatsapp'])
            ->where('recipient_hash', hash('sha256', $phone))
            ->latest()
            ->first();
    }

    private function storeFromBusinessPhone(mixed $phone): ?Store
    {
        $normalized = $this->whatsApp->normalizePhone((string) $phone);

        if ($normalized === '') {
            return null;
        }

        return Store::query()
            ->get(['id', 'user_id', 'whatsapp'])
            ->first(fn (Store $store) => $this->whatsApp->normalizePhone($store->whatsapp) === $normalized);
    }

    private function messageBody(array $message): ?string
    {
        $type = (string) data_get($message, 'type', 'unknown');

        return match ($type) {
            'text' => data_get($message, 'text.body'),
            'button' => data_get($message, 'button.text'),
            'interactive' => data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.list_reply.title'),
            default => '['.$type.']',
        };
    }

    private function mediaId(array $message): ?string
    {
        $type = (string) data_get($message, 'type', '');

        return data_get($message, $type.'.id');
    }

    private function messageTimestamp(array $message): Carbon
    {
        $timestamp = (int) data_get($message, 'timestamp', 0);

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : now();
    }
}
