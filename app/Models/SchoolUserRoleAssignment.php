<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolUserRoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_user_role_id',
        'grade_id',
        'division_id',
    ];

    public function schoolUserRole()
    {
        return $this->belongsTo(SchoolUserRole::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
