<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    protected $fillable = ['command', 'params', 'executed'];

    protected $casts = [
        'params' => 'array',
        'executed' => 'boolean'
    ];
}
