<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'first_name', 'middle_name', 'last_name', 'roll_no', 'blood_group', 'dob', 'address', 'pincode', 'contact_number', 'photo_path'])]
class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaignStudents()
    {
        return $this->hasMany(CampaignStudent::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_student')
            ->using(CampaignStudent::class)
            ->withPivot(['grade_id', 'division_id'])
            ->withTimestamps();
    }
}
