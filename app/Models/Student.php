<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['school_id', 'first_name', 'middle_name', 'last_name', 'standard', 'division', 'blood_group', 'dob', 'address', 'pincode', 'contact_number', 'photo_path'])]
class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
