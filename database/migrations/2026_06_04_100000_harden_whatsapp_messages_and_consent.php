<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('whatsapp_consent_text')->nullable()->after('whatsapp_consent_version');
            $table->string('whatsapp_consent_source', 60)->nullable()->after('whatsapp_consent_text');
            $table->string('whatsapp_consent_ip_hash', 64)->nullable()->after('whatsapp_consent_source');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('status');
            $table->timestamp('last_attempt_at')->nullable()->after('attempts');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'last_attempt_at']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_consent_text',
                'whatsapp_consent_source',
                'whatsapp_consent_ip_hash',
            ]);
        });
    }
};
