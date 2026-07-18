<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['school_id', 'mobile'])]
class ParentAccess extends Model
{
    use HasFactory;

    protected $table = 'parent_accesses';

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
