<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class StudentRosterExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Collection $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Date of Birth',
            'Gender',
            'Blood Group',
            'Contact Number',
            'Address',
            'Pincode',
            'Grade',
            'Division',
            'Roll No',
            'Ref / Serial No',
            'Verification Status',
            'Verified By',
            'Verified At',
            'Campaign',
        ];
    }

    public function map($row): array
    {
        $student = $row['student'];
        $enrollment = $row['enrollment'];
        $verifier = $enrollment?->verifier;

        $firstName = $student->first_name ?? '';
        $middleName = $student->middle_name ?? '';
        $lastName = $student->last_name ?? '';
        $fullName = trim("$firstName " . ($middleName ? "$middleName " : "") . $lastName);

        return [
            $fullName,
            $student->dob ?? '',
            $student->gender ?? '',
            $student->blood_group ?? '',
            $student->contact_number ?? '',
            $student->address ?? '',
            $student->pincode ?? '',
            $enrollment?->grade?->name ?? '',
            $enrollment?->division?->name ?? '',
            $enrollment?->roll_no ?? '',
            $enrollment?->serial_number ?? '',
            $enrollment?->verified_at ? 'Verified' : 'Pending Verification',
            $verifier?->name ?? '',
            $enrollment?->verified_at ? $enrollment->verified_at->format('Y-m-d H:i:s') : '',
            $enrollment?->campaign?->name ?? '',
        ];
    }
}
