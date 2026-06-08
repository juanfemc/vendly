<?php

namespace App\Services;

use App\Models\CustomerFollowup;
use App\Models\Store;
use Illuminate\Support\Collection;

class CustomerFollowupScheduler
{
    public function scheduleForStore(Store $store): Collection
    {
        if (! $store->isTrialing()) {
            return collect();
        }

        $user = $store->user;

        if (! $user || $store->whatsappNumber() === '') {
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
        if ($store->subscriptionStatus() !== Store::SUBSCRIPTION_ACTIVE || ! $store->subscription_ends_at) {
            return collect();
        }

        $user = $store->user;

        if (! $user || $store->whatsappNumber() === '') {
            return collect();
        }

        $endsAt = $store->subscription_ends_at->copy();
        $endDate = $endsAt->format('d/m/Y');
        $contextKey = 'subscription:'.$endsAt->toDateString();

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
            $this->scheduleOrUpdate(
                $store,
                CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED,
                (string) config('services.whatsapp.subscription_expired_template', 'plan_vencido'),
                [
                    $user->name,
                    $store->name,
                ],
                $endsAt,
                $contextKey,
            ),
        ])->filter();
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
    ): ?CustomerFollowup {
        $template = trim($template);

        if ($template === '') {
            return null;
        }

        $scheduledFor = $scheduledFor->copy();

        if ($scheduledFor->isPast()) {
            return null;
        }

        $followup = CustomerFollowup::firstOrNew([
            'store_id' => $store->id,
            'type' => $type,
            'context_key' => $contextKey,
        ]);

        if ($followup->exists && in_array($followup->status, [
            CustomerFollowup::STATUS_SENT,
            CustomerFollowup::STATUS_CANCELLED,
        ], true)) {
            return $followup;
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
            'error' => null,
        ])->save();

        return $followup;
    }
}
