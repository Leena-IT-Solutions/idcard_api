<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'template_id',
        'name',
        'orientation',
        'width_mm',
        'height_mm',
        'background_image',
        'background_back_image',
        'layout_config',
        'is_default',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'width_mm' => 'float',
        'height_mm' => 'float',
        'is_default' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function masterTemplate()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'school_template_id');
    }
}
