<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'template_id',
        'school_template_id',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function schoolTemplate()
    {
        return $this->belongsTo(SchoolTemplate::class, 'school_template_id');
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }
}
