<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasAdminRouteKey
{
    protected static function bootHasAdminRouteKey(): void
    {
        static::creating(function ($model) {
            if (! $model->admin_token) {
                $model->admin_token = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'admin_token';
    }
}
