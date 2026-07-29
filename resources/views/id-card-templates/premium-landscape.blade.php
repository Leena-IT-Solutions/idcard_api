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

<div class="w-[480px] h-[300px] bg-white border border-slate-200 rounded-2xl overflow-hidden flex flex-col justify-between shadow-2xl relative select-none font-sans text-left">
    <!-- Official Background Vector Artwork Overlay (Inline SVG from user svg/1.svg) -->
    {!! file_get_contents(public_path('svg/card-template-background.svg')) !!}

    <!-- Card Content -->
    <div class="flex-1 p-6 pb-4 z-10 flex flex-col justify-between h-full">
        <!-- Top Row: Logo & Title -->
        <div>
            <!-- School Logo & Name -->
            <div class="flex items-center space-x-2.5">
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

            <!-- Title -->
            <div class="mt-3">
                <h1 class="text-[20px] font-black text-[#0A2540] tracking-wider uppercase leading-none">STUDENT ID CARD</h1>
            </div>
        </div>

        <!-- Mid Row: Details (Left) & Photo (Right) -->
        <div class="flex items-start justify-between mt-1 gap-2">
            <!-- Left: Fields List -->
            <div class="flex-1 min-w-0 pr-2">
                <div class="grid grid-cols-[48px_10px_1fr] gap-y-1.5 text-[10px] font-sans items-center">
                    <div class="text-slate-600 font-extrabold tracking-wider uppercase">NAME</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">{{ $fullName }}</div>

                    <div class="text-slate-600 font-extrabold tracking-wider uppercase">ID</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">#{{ $serialNumber }}</div>

                    <div class="text-slate-600 font-extrabold tracking-wider uppercase">D.O.B</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase truncate leading-tight">{{ $dob }}</div>

                    <div class="text-slate-600 font-extrabold tracking-wider uppercase">ADDRES</div>
                    <div class="text-slate-400 font-extrabold text-center">:</div>
                    <div class="text-[#0A2540] font-black uppercase leading-tight line-clamp-2 pr-1">{{ $fullAddress }}</div>
                </div>
            </div>

            <!-- Right: Student Photo -->
            <div class="shrink-0 relative mr-2">
                <div class="w-[96px] h-[96px] rounded-2xl overflow-hidden border-2 border-[#E05B35] bg-slate-50 flex items-center justify-center shadow-md">
                    @if(!empty($photo))
                        <img src="{{ asset('storage/' . $photo) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-300 text-3xl font-black">{{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Row: Barcode & Class/Roll Info -->
        <div class="flex items-end justify-between mb-2">
            <div class="shrink-0">
                <svg class="h-6 w-32 text-slate-900" viewBox="0 0 100 20" preserveAspectRatio="none">
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
            <div class="text-right text-[8.5px] text-slate-500 font-bold uppercase tracking-wider mr-1">
                Class: <span class="text-[#0A2540] font-black">{{ $grade }} ({{ $division }})</span> | Roll: <span class="text-[#0A2540] font-black">#{{ $rollNo }}</span>
            </div>
        </div>
    </div>

</div>
