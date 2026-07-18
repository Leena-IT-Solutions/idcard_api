<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\Models\SchoolUserRole;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_path',
        'address',
        'contact_number',
        'email',
        'website',
        'school_code',
        'principal_name',
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function getOwnerAttribute()
    {
        $schoolAdminRole = Role::where('slug', 'school_admin')->first();
        if (!$schoolAdminRole) return null;
        
        $roleMapping = SchoolUserRole::where('school_id', $this->id)
            ->where('role_id', $schoolAdminRole->id)
            ->orderBy('id', 'asc')
            ->first();
            
        return $roleMapping ? $roleMapping->user : null;
    }
}
