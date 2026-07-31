<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'type',
        'status',
        'params',
        'total_items',
        'processed_items',
        'file_path',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
