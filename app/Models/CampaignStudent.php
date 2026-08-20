<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CampaignStudent extends Pivot
{
    protected $table = 'campaign_student';

    public const STATUS_DRAFTING = 'drafting';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_SENT_FOR_PRINTING = 'sent_for_printing';
    public const STATUS_PRINTED = 'printed';
    public const STATUS_DISTRIBUTED = 'distributed';

    public const STATUSES = [
        self::STATUS_DRAFTING => [
            'label' => 'Drafting',
            'color' => 'slate',
            'order' => 1,
        ],
        self::STATUS_VERIFIED => [
            'label' => 'Verified',
            'color' => 'emerald',
            'order' => 2,
        ],
        self::STATUS_SENT_FOR_PRINTING => [
            'label' => 'Sent for Printing',
            'color' => 'blue',
            'order' => 3,
        ],
        self::STATUS_PRINTED => [
            'label' => 'Printed',
            'color' => 'purple',
            'order' => 4,
        ],
        self::STATUS_DISTRIBUTED => [
            'label' => 'Distributed',
            'color' => 'teal',
            'order' => 5,
        ],
    ];

    protected $fillable = [
        'campaign_id',
        'student_id',
        'grade_id',
        'division_id',
        'roll_no',
        'serial_number',
        'status',
        'status_updated_at',
        'status_updated_by',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'status_updated_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::updating(function (CampaignStudent $enrollment) {
            $watched = ['grade_id', 'division_id', 'roll_no', 'serial_number'];
            if ($enrollment->isDirty($watched) && !$enrollment->isDirty(['verified_at', 'verified_by', 'status'])) {
                $enrollment->verified_at = null;
                $enrollment->verified_by = null;
                $enrollment->status = self::STATUS_DRAFTING;
                $enrollment->status_updated_at = now();
                $enrollment->status_updated_by = auth()->id();
            }
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? ucfirst(str_replace('_', ' ', $this->status ?? 'drafting'));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'slate';
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

    public function statusUpdater()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }
}
