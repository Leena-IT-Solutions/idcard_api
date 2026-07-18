<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CampaignStudent extends Pivot
{
    protected $table = 'campaign_student';

    protected $fillable = [
        'campaign_id',
        'student_id',
        'grade_id',
        'division_id',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
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
