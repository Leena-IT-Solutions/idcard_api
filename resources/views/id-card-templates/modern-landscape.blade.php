@php
    $middleName = $student->middle_name ?? '';
    $fullName = trim(($student->first_name ?? '') . ' ' . $middleName . ' ' . ($student->last_name ?? ''));
    $dob = $student->dob ?? 'N/A';
    $contact = $student->contact_number ?? 'N/A';
    $bloodGroup = $student->blood_group ?? 'N/A';
    $address = $student->address ?? '';
    $pincode = $student->pincode ?? '';
    $fullAddress = trim($address . ' ' . $pincode);
    if (empty($fullAddress)) $fullAddress = 'N/A';
    $photo = $student->photo_path ?? '';

    // Extract enrollment details
    $enrollment = $student->campaignStudents->first() ?? null;
    $grade = $enrollment && $enrollment->grade ? $enrollment->grade->name : 'N/A';
    $division = $enrollment && $enrollment->division ? $enrollment->division->name : 'N/A';
    $rollNo = $enrollment->roll_no ?? 'N/A';
    $serialNumber = $enrollment->serial_number ?? 'N/A';
@endphp

<div class="w-[450px] h-72 bg-slate-900 border-2 border-purple-500 rounded-3xl overflow-hidden flex shadow-2xl relative">
    <!-- Left Column (Visuals & Branding) -->
    <div class="w-[160px] bg-purple-950/40 border-r border-slate-800 flex flex-col items-center justify-between p-4 py-5 shrink-0">
        <!-- Logo -->
        @if(!empty($school->logo_path))
            <img src="{{ asset('storage/' . $school->logo_path) }}" class="w-12 h-12 rounded-xl bg-white/10 p-1 border border-white/20 object-contain shadow-sm">
        @else
            <div class="w-12 h-12 rounded-xl bg-purple-500/20 border border-purple-500/30 flex items-center justify-center text-purple-400 font-bold text-xl">
                {{ strtoupper(substr($school->name ?? 'S', 0, 1)) }}
            </div>
        @endif

        <!-- Photo -->
        <div class="relative mt-2">
            <div class="w-24 h-24 rounded-2xl border-2 border-purple-500/40 overflow-hidden bg-slate-950 flex items-center justify-center shadow-md">
                @if(!empty($photo))
                    <img src="{{ asset('storage/' . $photo) }}" class="w-full h-full object-cover">
                @else
                    <span class="text-purple-400 text-2xl font-black">{{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}</span>
                @endif
            </div>
        </div>

        <!-- Serial / Ref -->
        @if(!empty($serialNumber) && $serialNumber !== 'N/A')
            <span class="mt-2 text-[8px] font-black text-purple-300 uppercase tracking-widest bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20">
                #{{ $serialNumber }}
            </span>
        @else
            <span class="mt-2 text-[8px] font-black text-purple-400 uppercase tracking-widest">STUDENT</span>
        @endif
    </div>

    <!-- Right Column (Student Details) -->
    <div class="flex-1 flex flex-col justify-between p-5 min-w-0">
        <!-- Header Text -->
        <div class="space-y-0.5">
            <h3 class="text-sm font-black text-white uppercase tracking-wider truncate leading-tight">{{ $school->name ?? 'School Name' }}</h3>
            <p class="text-[9px] text-purple-400 uppercase tracking-widest font-black">STUDENT IDENTIFICATION CARD</p>
        </div>

        <!-- Details Grid -->
        <div class="space-y-2.5 my-auto">
            <!-- Student Name -->
            <h4 class="text-base font-extrabold text-white leading-tight truncate mt-1">{{ $fullName }}</h4>

            <div class="grid grid-cols-2 gap-2 text-[9px] mt-2">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Grade / Class</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $grade }}</span>
                </div>
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Division</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $division }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[9px]">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Roll No</span>
                    <span class="text-purple-300 font-extrabold block mt-0.5">{{ $rollNo }}</span>
                </div>
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Date of Birth</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $dob }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 text-[9px]">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Contact</span>
                    <span class="text-white font-semibold block mt-0.5 truncate">{{ $contact }}</span>
                </div>
                <div>
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[7.5px]">Blood Group</span>
                    <span class="text-red-400 font-extrabold block mt-0.5">{{ $bloodGroup }}</span>
                </div>
            </div>
        </div>

        <!-- Footer Card Details -->
        <div class="border-t border-slate-800 pt-2 flex items-center justify-between text-[8px] text-slate-500">
            <span class="font-semibold tracking-wider uppercase">{{ $school->school_code ?? 'CODE-N/A' }}</span>
            <span class="font-semibold tracking-wider italic">Valid 2026-2027</span>
        </div>
    </div>
</div>
