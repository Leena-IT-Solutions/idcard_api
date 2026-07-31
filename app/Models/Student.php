<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use App\Support\PhoneNumber;

#[Fillable(['user_id', 'first_name', 'middle_name', 'last_name', 'gender', 'blood_group', 'dob', 'address', 'pincode', 'contact_number', 'photo_path'])]
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

    protected static function booted()
    {
        static::updated(function (Student $student) {
            $watched = ['first_name', 'middle_name', 'last_name', 'dob', 'blood_group', 'gender', 'contact_number', 'address', 'pincode', 'photo_path'];
            if ($student->wasChanged($watched)) {
                $student->campaignStudents()->whereNotNull('verified_at')->update([
                    'verified_at' => null,
                    'verified_by' => null,
                ]);
            }
        });
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_student')
            ->using(CampaignStudent::class)
            ->withPivot(['grade_id', 'division_id', 'roll_no', 'serial_number'])
            ->withTimestamps();
    }

    public function attemptParentLink(): bool
    {
        if ($this->user_id) {
            return false;
        }

        $normalizedContact = PhoneNumber::normalize($this->contact_number);
        if (!$normalizedContact) {
            return false;
        }

        $user = User::query()
            ->get(['id', 'mobile'])
            ->first(fn ($u) => PhoneNumber::normalize($u->mobile) === $normalizedContact);

        if (!$user) {
            return false;
        }

        $this->update(['user_id' => $user->id]);

        return true;
    }

    public static function findExisting(?string $contactNumber, string $firstName, string $lastName, ?string $dob): ?self
    {
        $normalizedContact = PhoneNumber::normalize($contactNumber);
        if (!$normalizedContact) {
            return null;
        }

        return static::query()
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower(trim($firstName))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower(trim($lastName))])
            ->when($dob, fn ($q) => $q->whereDate('dob', $dob), fn ($q) => $q->whereNull('dob'))
            ->get()
            ->first(fn ($s) => PhoneNumber::normalize($s->contact_number) === $normalizedContact);
    }
}
