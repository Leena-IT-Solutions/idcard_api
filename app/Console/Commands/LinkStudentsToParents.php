<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Console\Command;

class LinkStudentsToParents extends Command
{
    protected $signature = 'students:link-parents';

    protected $description = 'One-time/idempotent backfill: link existing students to parent user accounts by matching contact_number to mobile.';

    public function handle(): int
    {
        $usersByMobile = User::query()
            ->get(['id', 'mobile'])
            ->filter(fn ($u) => PhoneNumber::normalize($u->mobile) !== null)
            ->keyBy(fn ($u) => PhoneNumber::normalize($u->mobile));

        $totalLinked = 0;

        Student::whereNull('user_id')->chunkById(200, function ($students) use ($usersByMobile, &$totalLinked) {
            foreach ($students as $student) {
                $normalized = PhoneNumber::normalize($student->contact_number);
                if ($normalized && $usersByMobile->has($normalized)) {
                    $student->update(['user_id' => $usersByMobile->get($normalized)->id]);
                    $totalLinked++;
                }
            }
        });

        $this->info("Linked {$totalLinked} student(s) to parent accounts.");

        return self::SUCCESS;
    }
}
