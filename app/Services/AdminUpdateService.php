<?php

namespace App\Services;

use App\Models\AdminUpdate;
use Illuminate\Support\Facades\Schema;

class AdminUpdateService
{
    public function record(string $title, ?string $body = null, string $type = 'info', ?string $url = null): void
    {
        if (! Schema::hasTable('admin_updates')) {
            return;
        }

        AdminUpdate::create([
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'url' => $url,
        ]);

        $keepIds = AdminUpdate::orderByDesc('id')->limit(10)->pluck('id');
        AdminUpdate::whereNotIn('id', $keepIds)->delete();
    }
}
