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
        'template_id',
    ];

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function schoolTemplates()
    {
        return $this->hasMany(SchoolTemplate::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function campaignStudents()
    {
        return $this->hasManyThrough(CampaignStudent::class, Campaign::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($school) {
            $campaignIds = $school->campaigns()->pluck('id');
            $studentIds = CampaignStudent::whereIn('campaign_id', $campaignIds)->pluck('student_id');
            $students = Student::whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                if ($student->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
                }
            }

            // Delete school logo from storage
            if ($school->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($school->logo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($school->logo_path);
            }
        });
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
