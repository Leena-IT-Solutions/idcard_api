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

<div class="w-80 h-[480px] bg-slate-900 border-2 border-indigo-500 rounded-3xl overflow-hidden flex flex-col justify-between shadow-2xl relative">
    <!-- Top Header Pattern -->
    <div class="bg-indigo-600 px-5 py-4 border-b border-indigo-500 flex items-center space-x-3">
        @if(!empty($school->logo_path))
            <img src="{{ asset('storage/' . $school->logo_path) }}" class="w-10 h-10 rounded-xl bg-white/10 p-1 border border-white/20 object-contain shrink-0">
        @else
            <div class="w-10 h-10 rounded-xl bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-lg shrink-0">
                {{ strtoupper(substr($school->name ?? 'S', 0, 1)) }}
            </div>
        @endif
        <div class="min-w-0">
            <h3 class="text-xs font-black text-white uppercase tracking-wider truncate leading-tight">{{ $school->name ?? 'School Name' }}</h3>
            <span class="text-[9px] text-indigo-200 font-bold uppercase tracking-widest block mt-0.5">{{ $school->school_code ?? 'CODE-N/A' }}</span>
        </div>
    </div>

    <!-- Student Visual Details -->
    <div class="flex-1 px-6 py-4 flex flex-col items-center justify-between">
        <!-- Photo Container -->
        <div class="relative mt-1">
            <div class="w-28 h-28 rounded-full border-4 border-indigo-500/50 overflow-hidden bg-slate-950 flex items-center justify-center shadow-lg">
                @if(!empty($photo))
                    <img src="{{ asset('storage/' . $photo) }}" class="w-full h-full object-cover">
                @else
                    <span class="text-indigo-400 text-3xl font-black">{{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}</span>
                @endif
            </div>
            @if(!empty($serialNumber) && $serialNumber !== 'N/A')
                <span class="absolute bottom-0 right-0 bg-indigo-500 text-white font-black text-[9px] px-2 py-0.5 rounded-full shadow border border-indigo-400">
                    #{{ $serialNumber }}
                </span>
            @endif
        </div>

        <!-- Student Title -->
        <div class="text-center mt-2">
            <h4 class="text-base font-extrabold text-white leading-tight line-clamp-1">{{ $fullName }}</h4>
            <span class="inline-block mt-1 text-[9px] font-black text-indigo-400 uppercase tracking-widest bg-indigo-500/10 px-2.5 py-0.5 rounded-full border border-indigo-500/20">STUDENT</span>
        </div>

        <!-- Information Fields -->
        <div class="w-full space-y-2 mt-3">
            <div class="grid grid-cols-2 gap-2 text-[10px]">
                <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-2">
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Grade / Class</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $grade }}</span>
                </div>
                <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-2">
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Division</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $division }}</span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-[10px]">
                <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-2">
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Roll No</span>
                    <span class="text-indigo-300 font-extrabold block mt-0.5">{{ $rollNo }}</span>
                </div>
                <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-2 col-span-2">
                    <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Date of Birth</span>
                    <span class="text-white font-extrabold block mt-0.5">{{ $dob }}</span>
                </div>
            </div>

            <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-2 text-[10px]">
                <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Contact</span>
                <span class="text-white font-medium block mt-0.5 truncate">{{ $contact }}</span>
            </div>
        </div>
    </div>

    <!-- Bottom Footer Accent -->
    <div class="bg-indigo-950/80 px-6 py-3 border-t border-slate-800 text-center flex items-center justify-between">
        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Blood Group: <span class="text-red-400 font-extrabold">{{ $bloodGroup }}</span></span>
        <span class="text-[8px] text-slate-400 font-semibold tracking-wider italic">Valid 2026-2027</span>
    </div>
</div>
