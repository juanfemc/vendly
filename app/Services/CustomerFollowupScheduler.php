<?php

namespace App\Services;

use App\Models\CustomerFollowup;
use App\Models\Store;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Collection;

class CustomerFollowupScheduler
{
    public function scheduleForStore(Store $store): Collection
    {
        if (! $store->isTrialing()) {
            return collect();
        }

        $user = $store->user;

        if (! $store->is_active || ! $user?->isActive() || $store->whatsappNumber() === '') {
            return collect();
        }

        $startsAt = $store->trial_starts_at ?: $store->created_at ?: now();

        return collect([
            $this->schedule(
                $store,
                CustomerFollowup::TYPE_DAY_1_SETUP,
                (string) config('services.whatsapp.followup_day_1_template', 'seguimiento_dia_1_tienda'),
                [
                    $user->name,
                    $store->name,
                    route('admin.store.onboarding'),
                ],
                $startsAt->copy()->addDay(),
            ),
            $this->schedule(
                $store,
                CustomerFollowup::TYPE_DAY_3_PRODUCTS_OR_SHARE,
                (string) config('services.whatsapp.followup_day_3_template', 'seguimiento_dia_3_productos'),
                [
                    $user->name,
                    $store->name,
                    url('/'.$store->slug),
                ],
                $startsAt->copy()->addDays(3),
            ),
            $this->schedule(
                $store,
                CustomerFollowup::TYPE_DAY_6_TRIAL_ENDING,
                (string) config('services.whatsapp.followup_day_6_template', 'seguimiento_dia_6_prueba'),
                [
                    $user->name,
                    $store->name,
                ],
                $startsAt->copy()->addDays(6),
            ),
        ])->filter();
    }

    public function scheduleSubscriptionReminders(Store $store): Collection
    {
        if (! $store->subscription_ends_at) {
            return collect();
        }

        $user = $store->user;
        $status = $store->subscriptionStatus();
        $canScheduleActiveReminders = $status === Store::SUBSCRIPTION_ACTIVE
            && $store->is_active
            && $user?->isActive()
            && $store->whatsappNumber() !== '';
        $canScheduleExpiredReminder = $status === Store::SUBSCRIPTION_EXPIRED
            && $store->is_active
            && (bool) $user?->is_active
            && $store->whatsappNumber() !== '';

        if (! $canScheduleActiveReminders && ! $canScheduleExpiredReminder) {
            return collect();
        }

        $endsAt = $store->subscription_ends_at->copy();
        $endDate = $endsAt->format('d/m/Y');
        $contextKey = 'subscription:'.$endsAt->toDateString();

        $this->cancelPendingSubscriptionRemindersExceptContext(
            $store,
            $contextKey,
            'El vencimiento del plan cambio y este recordatorio fue reemplazado.',
        );

        $expiredReminder = $this->scheduleOrUpdate(
            $store,
            CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED,
            (string) config('services.whatsapp.subscription_expired_template', 'plan_vencido'),
            [
                $user->name,
                $store->name,
            ],
            $endsAt,
            $contextKey,
            allowPast: true,
        );

        if (! $canScheduleActiveReminders) {
            return collect([$expiredReminder])->filter();
        }

        return collect([
            $this->scheduleOrUpdate(
                $store,
                CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE,
                (string) config('services.whatsapp.subscription_expires_3_days_template', 'plan_vence_3_dias'),
                [
                    $user->name,
                    $store->name,
                    $endDate,
                ],
                $endsAt->copy()->subDays(3),
                $contextKey,
            ),
            $this->scheduleOrUpdate(
                $store,
                CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE,
                (string) config('services.whatsapp.subscription_expires_1_day_template', 'plan_vence_manana'),
                [
                    $user->name,
                    $store->name,
                    $endDate,
                ],
                $endsAt->copy()->subDay(),
                $contextKey,
            ),
            $expiredReminder,
        ])->filter();
    }

    public function cancelPendingSubscriptionReminders(Store $store, string $reason = 'La tienda ya no es elegible para recordatorios de plan.'): int
    {
        return $this->cancelPendingForStore($store, $reason, $this->subscriptionReminderTypes());
    }

    public function cancelPendingSubscriptionRemindersExceptContext(Store $store, string $contextKey, string $reason = 'El vencimiento del plan cambio.'): int
    {
        return $this->cancelPendingForStore(
            $store,
            $reason,
            $this->subscriptionReminderTypes(),
            $contextKey,
        );
    }

    public function cancelPendingForStore(Store $store, string $reason = 'La tienda ya no es elegible para este seguimiento.', ?array $types = null, ?string $exceptContextKey = null): int
    {
        $followups = CustomerFollowup::query()
            ->with('whatsappMessage')
            ->where('store_id', $store->id)
            ->whereIn('status', [
                CustomerFollowup::STATUS_PENDING,
                CustomerFollowup::STATUS_QUEUED,
            ]);

        if ($types !== null) {
            $followups->whereIn('type', $types);
        }

        if ($exceptContextKey !== null) {
            $followups->where(function ($query) use ($exceptContextKey) {
                $query
                    ->whereNull('context_key')
                    ->orWhere('context_key', '!=', $exceptContextKey);
            });
        }

        $followups = $followups->get();

        foreach ($followups as $followup) {
            $message = $followup->whatsappMessage;

            if ($message?->status === WhatsAppMessage::STATUS_PROCESSING) {
                continue;
            }

            if ($message && in_array($message->status, [
                WhatsAppMessage::STATUS_QUEUED,
                WhatsAppMessage::STATUS_RETRYING,
            ], true)) {
                $message->update([
                    'status' => WhatsAppMessage::STATUS_CANCELLED,
                    'error' => $reason,
                    'cancelled_at' => now(),
                ]);
            }

            $followup->update([
                'status' => CustomerFollowup::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'error' => $reason,
            ]);
        }

        return $followups->count();
    }

    private function schedule(
        Store $store,
        string $type,
        string $template,
        array $parameters,
        mixed $scheduledFor,
        string $contextKey = 'trial',
    ): ?CustomerFollowup {
        $template = trim($template);

        if ($template === '') {
            return null;
        }

        return CustomerFollowup::firstOrCreate(
            [
                'store_id' => $store->id,
                'type' => $type,
                'context_key' => $contextKey,
            ],
            [
                'user_id' => $store->user_id,
                'template' => $template,
                'parameters' => $parameters,
                'status' => CustomerFollowup::STATUS_PENDING,
                'scheduled_for' => $scheduledFor,
            ],
        );
    }

    private function scheduleOrUpdate(
        Store $store,
        string $type,
        string $template,
        array $parameters,
        mixed $scheduledFor,
        string $contextKey,
        bool $allowPast = false,
    ): ?CustomerFollowup {
        $template = trim($template);

        if ($template === '') {
            return null;
        }

        $scheduledFor = $scheduledFor->copy();

        if ($scheduledFor->isPast()) {
            if (! $allowPast) {
                return null;
            }

            $scheduledFor = now();
        }

        $followup = CustomerFollowup::firstOrNew([
            'store_id' => $store->id,
            'type' => $type,
            'context_key' => $contextKey,
        ]);

        if ($followup->exists && $followup->status === CustomerFollowup::STATUS_SENT) {
            return $followup;
        }

        if ($followup->exists && $followup->status === CustomerFollowup::STATUS_FAILED) {
            $payloadChanged = $followup->template !== $template
                || (array) $followup->parameters !== $parameters;

            if (! $payloadChanged) {
                return $followup;
            }
        }

        $followup->fill([
            'user_id' => $store->user_id,
            'whatsapp_message_id' => null,
            'template' => $template,
            'parameters' => $parameters,
            'status' => CustomerFollowup::STATUS_PENDING,
            'scheduled_for' => $scheduledFor,
            'sent_at' => null,
            'failed_at' => null,
            'skipped_at' => null,
            'cancelled_at' => null,
            'error' => null,
        ])->save();

        return $followup;
    }

    private function subscriptionReminderTypes(): array
    {
        return [
            CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE,
            CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED,
        ];
    }
}
