<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'type',
        'credits',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
