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
        'roll_no',
        'serial_number',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::updating(function (CampaignStudent $enrollment) {
            $watched = ['grade_id', 'division_id', 'roll_no', 'serial_number'];
            if ($enrollment->isDirty($watched) && !$enrollment->isDirty(['verified_at', 'verified_by'])) {
                $enrollment->verified_at = null;
                $enrollment->verified_by = null;
            }
        });
    }

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

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
