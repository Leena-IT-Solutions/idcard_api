<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolInvitationAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_invitation_id',
        'grade_id',
        'division_id',
    ];

    public function schoolInvitation()
    {
        return $this->belongsTo(SchoolInvitation::class);
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
