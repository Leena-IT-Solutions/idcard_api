<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['school_id', 'name', 'registration_start_date', 'registration_end_date'])]
class Campaign extends Model
{
    use HasFactory;

    protected $casts = [
        'registration_start_date' => 'date',
        'registration_end_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
