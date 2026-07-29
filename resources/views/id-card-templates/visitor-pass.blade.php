@php
    $middleName = $student->middle_name ?? '';
    $fullName = trim(($student->first_name ?? '') . ' ' . $middleName . ' ' . ($student->last_name ?? ''));
    $contact = $student->contact_number ?? 'N/A';
    $photo = $student->photo_path ?? '';
    $serialNumber = $student->campaignStudents->first()->serial_number ?? 'N/A';
@endphp

<div class="w-80 h-[480px] bg-slate-900 border-2 border-emerald-500 rounded-3xl overflow-hidden flex flex-col justify-between shadow-2xl relative">
    <!-- Top Header -->
    <div class="bg-emerald-600 px-5 py-4 border-b border-emerald-500 flex items-center space-x-3">
        @if(!empty($school->logo_path))
            <img src="{{ asset('storage/' . $school->logo_path) }}" class="w-10 h-10 rounded-xl bg-white/10 p-1 border border-white/20 object-contain shrink-0">
        @else
            <div class="w-10 h-10 rounded-xl bg-white/20 border border-white/30 flex items-center justify-center text-white font-bold text-lg shrink-0">
                {{ strtoupper(substr($school->name ?? 'S', 0, 1)) }}
            </div>
        @endif
        <div class="min-w-0">
            <h3 class="text-xs font-black text-white uppercase tracking-wider truncate leading-tight">{{ $school->name ?? 'School Name' }}</h3>
            <span class="text-[9px] text-emerald-200 font-bold uppercase tracking-widest block mt-0.5">{{ $school->school_code ?? 'CODE-N/A' }}</span>
        </div>
    </div>

    <!-- Visitor Visual Details -->
    <div class="flex-1 px-6 py-4 flex flex-col items-center justify-between">
        <!-- Temporary Icon / Avatar -->
        <div class="relative mt-4">
            <div class="w-28 h-28 rounded-full border-4 border-emerald-500/50 overflow-hidden bg-slate-950 flex items-center justify-center shadow-lg">
                @if(!empty($photo))
                    <img src="{{ asset('storage/' . $photo) }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-16 h-16 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                @endif
            </div>
        </div>

        <!-- Visitor Title -->
        <div class="text-center mt-3">
            <h4 class="text-base font-extrabold text-white leading-tight line-clamp-1">{{ $fullName }}</h4>
            <span class="inline-block mt-1.5 text-[9px] font-black text-emerald-400 uppercase tracking-widest bg-emerald-500/10 px-3 py-0.5 rounded-full border border-emerald-500/20">VISITOR</span>
        </div>

        <!-- Information Fields -->
        <div class="w-full space-y-2 mt-4">
            <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-3 text-[10px]">
                <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Access Reference</span>
                <span class="text-white font-extrabold block mt-0.5">#{{ $serialNumber !== 'N/A' ? $serialNumber : 'VIS-2026-TMP' }}</span>
            </div>

            <div class="bg-slate-950/40 border border-slate-800 rounded-xl p-3 text-[10px]">
                <span class="text-slate-500 uppercase tracking-wider block font-bold text-[8px]">Contact Number</span>
                <span class="text-white font-semibold block mt-0.5 truncate">{{ $contact }}</span>
            </div>
        </div>
    </div>

    <!-- Bottom Footer Accent -->
    <div class="bg-emerald-950/80 px-6 py-3 border-t border-slate-800 text-center flex items-center justify-between">
        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Type: <span class="text-emerald-400 font-extrabold">TEMPORARY</span></span>
        <span class="text-[8px] text-slate-400 font-semibold tracking-wider italic text-emerald-500">GUEST PASS</span>
    </div>
</div>
