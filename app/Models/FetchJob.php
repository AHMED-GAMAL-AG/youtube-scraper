<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FetchJob extends Model
{
    protected $fillable = [
        'categories',
        'status',
        'progress',
        'current_step',
        'total_found',
        'stopped',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'stopped' => 'boolean',
        ];
    }
}
