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

<div class="w-[480px] h-[300px] bg-white border border-slate-200 rounded-[2rem] overflow-hidden flex flex-col justify-between shadow-2xl relative select-none font-sans">
    <!-- Subtle Grid Background Watermark -->
    <svg class="absolute inset-0 w-full h-full opacity-[0.04] pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
        <path d="M-10 30 L110 30 M-10 60 L110 60 M-10 90 L110 90 M20 -10 L20 110 M50 -10 L50 110 M80 -10 L80 110" stroke="currentColor" stroke-width="0.3" fill="none" />
        <path d="M-10 10 L110 90 M-10 40 L90 120 M10 -20 L110 60" stroke="currentColor" stroke-width="0.3" fill="none" />
    </svg>

    <!-- Top Wavy Header Pattern -->
    <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 480 300" preserveAspectRatio="none">
        <!-- Part A: Left Corner Orange Accent -->
        <path d="M 0 45 C 25 22, 55 0, 90 0 L 0 0 Z" fill="#E05B35" />
        <!-- Part A: Left Corner Blue Accent -->
        <path d="M 0 34 C 18 14, 42 0, 72 0 L 0 0 Z" fill="#0A2540" />

        <!-- Part C: Right Orange Wave -->
        <path d="M 165 0 C 225 75, 330 145, 480 125 L 480 0 Z" fill="#E05B35" />
        <!-- Part C: Right Dark Blue Wave -->
        <path d="M 195 0 C 245 60, 340 120, 480 95 L 480 0 Z" fill="#0A2540" />
    </svg>

    <!-- Card Content -->
    <div class="flex-1 p-6 pb-2 z-10 flex flex-col justify-between">
        <!-- Top Row: Logo & Title -->
        <div class="flex justify-between items-start">
            <!-- School Logo & Name -->
            <div class="flex items-center space-x-2">
                @if(!empty($school->logo_path))
                    <img src="{{ asset('storage/' . $school->logo_path) }}" class="w-8 h-8 rounded-lg bg-white/10 p-0.5 border border-slate-200 object-contain shadow-sm shrink-0">
                @else
                    <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-[#0A2540] shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                            <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                        </svg>
                    </div>
                @endif
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-[#0A2540] tracking-wider uppercase leading-none font-sans truncate max-w-[140px]">{{ $school->name ?? 'School Name' }}</span>
                    <span class="text-[7.5px] text-slate-400 font-bold uppercase tracking-widest block mt-0.5">High School</span>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="mt-2">
            <h1 class="text-[22px] font-black text-[#0A2540] tracking-wider uppercase leading-none">STUDENT ID CARD</h1>
        </div>

        <!-- Mid Row: Details (Left) & Photo (Right) -->
        <div class="flex items-start justify-between mt-3.5 gap-4">
            <!-- Left: Fields List -->
            <div class="flex-1 min-w-0">
                <div class="grid grid-cols-[65px_12px_1fr] gap-y-2.5 text-[10.5px] font-sans">
                    <div class="text-slate-500 font-extrabold tracking-widest uppercase">NAME</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">{{ $fullName }}</div>

                    <div class="text-slate-500 font-extrabold tracking-widest uppercase">ID</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">#{{ $serialNumber }}</div>

                    <div class="text-slate-500 font-extrabold tracking-widest uppercase">D.O.B</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">{{ $dob }}</div>

                    <div class="text-slate-500 font-extrabold tracking-widest uppercase">ADDRES</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase leading-tight line-clamp-2 pr-1">{{ $fullAddress }}</div>
                </div>
            </div>

            <!-- Right: Student Photo -->
            <div class="shrink-0 relative">
                <div class="w-[110px] h-[110px] rounded-3xl overflow-hidden border-[3px] border-[#E05B35] bg-slate-50 flex items-center justify-center shadow-lg">
                    @if(!empty($photo))
                        <img src="{{ asset('storage/' . $photo) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-300 text-3xl font-black">{{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Row: Barcode -->
        <div class="mt-2.5 flex items-end justify-between">
            <div class="shrink-0">
                <svg class="h-7 w-36 text-slate-900" viewBox="0 0 100 20" preserveAspectRatio="none">
                    <rect x="0" width="2" height="20" fill="currentColor"/>
                    <rect x="3" width="1" height="20" fill="currentColor"/>
                    <rect x="5" width="3" height="20" fill="currentColor"/>
                    <rect x="10" width="1" height="20" fill="currentColor"/>
                    <rect x="12" width="2" height="20" fill="currentColor"/>
                    <rect x="15" width="4" height="20" fill="currentColor"/>
                    <rect x="20" width="1" height="20" fill="currentColor"/>
                    <rect x="23" width="2" height="20" fill="currentColor"/>
                    <rect x="27" width="3" height="20" fill="currentColor"/>
                    <rect x="32" width="1" height="20" fill="currentColor"/>
                    <rect x="34" width="2" height="20" fill="currentColor"/>
                    <rect x="38" width="4" height="20" fill="currentColor"/>
                    <rect x="44" width="1" height="20" fill="currentColor"/>
                    <rect x="46" width="2" height="20" fill="currentColor"/>
                    <rect x="50" width="3" height="20" fill="currentColor"/>
                    <rect x="55" width="1" height="20" fill="currentColor"/>
                    <rect x="58" width="2" height="20" fill="currentColor"/>
                    <rect x="62" width="4" height="20" fill="currentColor"/>
                    <rect x="68" width="1" height="20" fill="currentColor"/>
                    <rect x="71" width="2" height="20" fill="currentColor"/>
                    <rect x="75" width="3" height="20" fill="currentColor"/>
                    <rect x="80" width="1" height="20" fill="currentColor"/>
                    <rect x="83" width="2" height="20" fill="currentColor"/>
                    <rect x="87" width="4" height="20" fill="currentColor"/>
                    <rect x="93" width="2" height="20" fill="currentColor"/>
                    <rect x="96" width="4" height="20" fill="currentColor"/>
                </svg>
            </div>
            <div class="text-right text-[8px] text-slate-400 font-bold uppercase tracking-wider">
                Class: <span class="text-[#0A2540] font-black">{{ $grade }} ({{ $division }})</span> | Roll: <span class="text-[#0A2540] font-black">#{{ $rollNo }}</span>
            </div>
        </div>
    </div>

    <!-- Solid Blue Footer Bar -->
    <div class="w-full h-4 bg-[#0A2540] shrink-0"></div>
</div>
