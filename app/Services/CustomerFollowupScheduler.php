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

    private function schedule(
        Store $store,
        string $type,
        string $template,
        array $parameters,
        mixed $scheduledFor,
    ): ?CustomerFollowup {
        $template = trim($template);

        if ($template === '') {
            return null;
        }

        return CustomerFollowup::firstOrCreate(
            [
                'store_id' => $store->id,
                'type' => $type,
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
}
