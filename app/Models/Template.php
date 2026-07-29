<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'orientation',
        'width_mm',
        'height_mm',
        'background_image',
        'background_back_image',
        'layout_config',
        'is_active',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'width_mm' => 'float',
        'height_mm' => 'float',
        'is_active' => 'boolean',
    ];

    public function schoolTemplates()
    {
        return $this->hasMany(SchoolTemplate::class, 'template_id');
    }
}
