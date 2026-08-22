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
        'credits_balance',
        'template_id',
    ];

    protected function casts(): array
    {
        return [
            'credits_balance' => 'integer',
        ];
    }

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

    public function creditOrders()
    {
        return $this->hasMany(CreditOrder::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class)->orderBy('id', 'desc');
    }

    /**
     * Check if school has sufficient credits
     */
    public function hasCredits(int $needed): bool
    {
        return (int) $this->credits_balance >= $needed;
    }

    /**
     * Add credits to school wallet and log transaction
     */
    public function addCredits(
        int $amount,
        string $type,
        string $description,
        ?Model $reference = null,
        ?User $performer = null
    ): CreditTransaction {
        $amount = abs($amount);
        $this->increment('credits_balance', $amount);
        $this->refresh();

        return CreditTransaction::create([
            'school_id' => $this->id,
            'type' => $type,
            'credits' => $amount,
            'balance_after' => $this->credits_balance,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
            'performed_by' => $performer?->id ?? auth()->id(),
        ]);
    }

    /**
     * Deduct credits from school wallet and log transaction
     */
    public function deductCredits(
        int $amount,
        string $description,
        ?Model $reference = null,
        ?User $performer = null
    ): CreditTransaction {
        $amount = abs($amount);
        $newBalance = max(0, (int) $this->credits_balance - $amount);
        $this->update(['credits_balance' => $newBalance]);
        $this->refresh();

        return CreditTransaction::create([
            'school_id' => $this->id,
            'type' => 'export_deduction',
            'credits' => -$amount,
            'balance_after' => $this->credits_balance,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
            'performed_by' => $performer?->id ?? auth()->id(),
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-grant 50 welcome credits on new school registration
        static::created(function ($school) {
            $school->update(['credits_balance' => 50]);
            CreditTransaction::create([
                'school_id' => $school->id,
                'type' => 'welcome_bonus',
                'credits' => 50,
                'balance_after' => 50,
                'description' => 'Welcome Free Starter Credits (50 Cards)',
                'performed_by' => auth()->id(),
            ]);
        });

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
