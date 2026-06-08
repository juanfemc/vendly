<?php

use App\Models\CustomerFollowup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customer_followups')
            ->where('context_key', 'default')
            ->orderBy('id')
            ->chunkById(100, function ($followups) {
                foreach ($followups as $followup) {
                    $contextKey = $this->contextKeyFor($followup);

                    if ($contextKey === 'default') {
                        continue;
                    }

                    $duplicate = DB::table('customer_followups')
                        ->where('id', '!=', $followup->id)
                        ->where('store_id', $followup->store_id)
                        ->where('type', $followup->type)
                        ->where('context_key', $contextKey)
                        ->exists();

                    if ($duplicate) {
                        DB::table('customer_followups')
                            ->where('id', $followup->id)
                            ->update([
                                'status' => CustomerFollowup::STATUS_SKIPPED,
                                'skipped_at' => now(),
                                'error' => encrypt('Seguimiento antiguo reemplazado por un ciclo mas reciente.'),
                                'updated_at' => now(),
                            ]);

                        continue;
                    }

                    DB::table('customer_followups')
                        ->where('id', $followup->id)
                        ->update([
                            'context_key' => $contextKey,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function contextKeyFor(object $followup): string
    {
        $scheduledFor = Carbon::parse($followup->scheduled_for);

        return match ($followup->type) {
            CustomerFollowup::TYPE_DAY_1_SETUP,
            CustomerFollowup::TYPE_DAY_3_PRODUCTS_OR_SHARE,
            CustomerFollowup::TYPE_DAY_6_TRIAL_ENDING => 'trial',
            CustomerFollowup::TYPE_SUBSCRIPTION_3_DAYS_BEFORE => 'subscription:'.$scheduledFor->copy()->addDays(3)->toDateString(),
            CustomerFollowup::TYPE_SUBSCRIPTION_1_DAY_BEFORE => 'subscription:'.$scheduledFor->copy()->addDay()->toDateString(),
            CustomerFollowup::TYPE_SUBSCRIPTION_EXPIRED => 'subscription:'.$scheduledFor->toDateString(),
            default => 'default',
        };
    }
};
