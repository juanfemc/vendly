<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'prompt',
        'context',
        'response',
        'status',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'error',
    ];

    protected $casts = [
        'context' => 'array',
        'response' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];
}
