<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminUpdate extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'url',
    ];
}
