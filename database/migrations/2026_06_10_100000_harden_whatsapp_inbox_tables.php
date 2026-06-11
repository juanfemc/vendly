<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_conversations')) {
            return;
        }

        $addedConversationKey = false;

        if (! Schema::hasColumn('whatsapp_conversations', 'conversation_key')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table) {
                $table->string('conversation_key', 64)->nullable()->after('user_id');
            });

            $addedConversationKey = true;
        }

        DB::table('whatsapp_conversations')
            ->whereNull('conversation_key')
            ->orderBy('id')
            ->select(['id', 'store_id', 'contact_phone_hash', 'contact_phone'])
            ->chunkById(100, function ($conversations) {
                foreach ($conversations as $conversation) {
                    $phone = $this->decryptValue($conversation->contact_phone) ?: $conversation->contact_phone_hash;
                    $scope = $conversation->store_id ?: 'unassigned';
                    $key = hash('sha256', $scope.'|'.$phone);

                    if (DB::table('whatsapp_conversations')->where('conversation_key', $key)->exists()) {
                        $key = hash('sha256', $scope.'|'.$phone.'|'.$conversation->id);
                    }

                    DB::table('whatsapp_conversations')
                        ->where('id', $conversation->id)
                        ->update([
                            'conversation_key' => $key,
                        ]);
                }
            });

        if ($addedConversationKey) {
            Schema::table('whatsapp_conversations', function (Blueprint $table) {
                $table->unique('conversation_key');
            });
        }
    }

    public function down(): void
    {
        // Conservador a proposito: en instalaciones nuevas la columna vive en la
        // migracion base, asi que esta migracion no debe eliminarla al revertir.
    }

    private function decryptValue(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return decrypt($value);
        } catch (Throwable) {
            return null;
        }
    }
};
