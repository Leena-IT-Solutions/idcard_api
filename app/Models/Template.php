<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'view_path',
        'orientation',
        'category',
        'thumbnail_color',
    ];

    public function schools()
    {
        return $this->hasMany(School::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
