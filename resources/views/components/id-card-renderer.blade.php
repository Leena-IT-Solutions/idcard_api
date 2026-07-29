@props([
    'template' => null,
    'student' => null,
    'school' => null,
    'previewMode' => false, // If true, shows sample student data
])

@php
    // Determine layout config array
    $config = is_array($template->layout_config ?? null) 
        ? $template->layout_config 
        : (is_string($template->layout_config ?? null) ? json_decode($template->layout_config, true) : []);

    $isPortrait = ($template->orientation ?? 'landscape') === 'portrait';
    $widthPx = $isPortrait ? 638 : 1011;
    $heightPx = $isPortrait ? 1011 : 638;

    $bgPath = $template->background_image ?? null;
    $bgUrl = $bgPath 
        ? (str_starts_with($bgPath, 'http') ? $bgPath : asset('storage/' . $bgPath)) 
        : null;

    // Student fields
    $firstName = $student->first_name ?? ($student['first_name'] ?? 'Aaditya');
    $middleName = $student->middle_name ?? ($student['middle_name'] ?? 'Sonu');
    $lastName = $student->last_name ?? ($student['last_name'] ?? 'Thakur');
    $fullName = trim("$firstName $middleName $lastName");
    $dob = $student->dob ?? ($student['dob'] ?? '2017-10-27');
    $bloodGroup = $student->blood_group ?? ($student['blood_group'] ?? 'AB+');
    $gender = $student->gender ?? ($student['gender'] ?? 'Male');
    $contact = $student->contact_number ?? ($student['contact_number'] ?? '9730777244');
    $address = $student->address ?? ($student['address'] ?? 'Sarvodhya Nagar Phase 3 Flat No 704');
    $pincode = $student->pincode ?? ($student['pincode'] ?? '400001');
    $photoPath = $student->photo_path ?? ($student['photo_path'] ?? null);
    $photoUrl = $photoPath 
        ? (str_starts_with($photoPath, 'http') ? $photoPath : asset('storage/' . $photoPath)) 
        : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=6366f1&color=fff&size=200';

    // Enrollment details
    $enrollments = $student->campaignStudents ?? ($student['campaignStudents'] ?? []);
    $gradeName = 'V';
    $divName = 'B';
    $rollNo = '202';
    $serialNo = '202';

    if (!empty($enrollments)) {
        $firstE = is_object($enrollments) ? $enrollments->first() : ($enrollments[0] ?? null);
        if ($firstE) {
            $gradeObj = is_object($firstE) ? ($firstE->grade ?? null) : ($firstE['grade'] ?? null);
            $divObj = is_object($firstE) ? ($firstE->division ?? null) : ($firstE['division'] ?? null);
            $gradeName = is_object($gradeObj) ? ($gradeObj->name ?? 'V') : ($gradeObj['name'] ?? 'V');
            $divName = is_object($divObj) ? ($divObj->name ?? 'B') : ($divObj['name'] ?? 'B');
            $rollNo = is_object($firstE) ? ($firstE->roll_no ?? '202') : ($firstE['roll_no'] ?? '202');
            $serialNo = is_object($firstE) ? ($firstE->serial_number ?? '202') : ($firstE['serial_number'] ?? '202');
        }
    }

    // Campaign name
    $campaignName = 'iCard 2026-27';
    if (!empty($enrollments)) {
        $firstE = is_object($enrollments) ? $enrollments->first() : ($enrollments[0] ?? null);
        if ($firstE) {
            $cObj = is_object($firstE) ? ($firstE->campaign ?? null) : ($firstE['campaign'] ?? null);
            if ($cObj) {
                $campaignName = is_object($cObj) ? ($cObj->name ?? $campaignName) : ($cObj['name'] ?? $campaignName);
            }
        }
    }

    // School details
    $schoolName = $school->name ?? ($school['name'] ?? 'Sarvodya Vidyalay');
    $schoolCode = $school->school_code ?? ($school['school_code'] ?? 'SV-2026');
    $principalName = $school->principal_name ?? ($school['principal_name'] ?? 'Dr. R. K. Sharma');
    $schoolContact = $school->contact_number ?? ($school['contact_number'] ?? '9820198201');
    $schoolEmail = $school->email ?? ($school['email'] ?? 'info@sarvodya.edu.in');
    $schoolWebsite = $school->website ?? ($school['website'] ?? 'www.sarvodya.edu.in');
    $schoolAddress = $school->address ?? ($school['address'] ?? 'Station Road, Mumbai');
    $schoolLogo = $school->logo_path ?? ($school['logo_path'] ?? null);
    $schoolLogoUrl = $schoolLogo 
        ? (str_starts_with($schoolLogo, 'http') ? $schoolLogo : asset('storage/' . $schoolLogo)) 
        : null;

    $replaceMap = [
        '{first_name}' => $firstName,
        '{middle_name}' => $middleName,
        '{last_name}' => $lastName,
        '{First Name}' => $firstName,
        '{Middle Name}' => $middleName,
        '{Last Name}' => $lastName,
        '{dob}' => $dob,
        '{DOB}' => $dob,
        '{blood_group}' => $bloodGroup,
        '{Blood Group}' => $bloodGroup,
        '{gender}' => $gender,
        '{Gender}' => $gender,
        '{contact_number}' => $contact,
        '{Contact Number}' => $contact,
        '{address}' => $address,
        '{Address}' => $address,
        '{pincode}' => $pincode,
        '{Pincode}' => $pincode,
        '{grade}' => $gradeName,
        '{Grade}' => $gradeName,
        '{Standard}' => $gradeName,
        '{division}' => $divName,
        '{Division}' => $divName,
        '{Div}' => $divName,
        '{roll_no}' => $rollNo,
        '{Roll No}' => $rollNo,
        '{serial_number}' => $serialNo,
        '{Ref No}' => $serialNo,
        '{Campaign}' => $campaignName,
        '{School Name}' => $schoolName,
        '{School Code}' => $schoolCode,
        '{Registration Code}' => $schoolCode,
        '{Principal Name}' => $principalName,
        '{School Contact}' => $schoolContact,
        '{School Email}' => $schoolEmail,
        '{School Website}' => $schoolWebsite,
        '{School Address}' => $schoolAddress,
    ];
@endphp

<div 
    class="relative overflow-hidden select-none shadow-2xl rounded-2xl border border-slate-700/50 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950"
    style="width: {{ $widthPx }}px; height: {{ $heightPx }}px; background-image: {{ $bgUrl ? "url('$bgUrl')" : 'none' }}; background-size: cover; background-position: center;"
>
    @foreach($config as $layer)
        @php
            $type = $layer['type'] ?? 'text';
            $x = $layer['x'] ?? 0;
            $y = $layer['y'] ?? 0;
            $w = $layer['width'] ?? 'auto';
            $h = $layer['height'] ?? 'auto';
            $rot = $layer['rotation'] ?? 0;

            $style = "position: absolute; left: {$x}px; top: {$y}px; transform: rotate({$rot}deg); transform-origin: top left;";
        @endphp

        @if($type === 'text')
            @php
                $rawText = $layer['text'] ?? '';
                $displayText = strtr($rawText, $replaceMap);
                $fontSize = $layer['font_size'] ?? 14;
                $fontWeight = $layer['font_weight'] ?? 'normal';
                $fontFamily = $layer['font_family'] ?? 'Inter';
                $color = $layer['color'] ?? '#ffffff';
                $align = $layer['align'] ?? 'left';

                $textStyle = $style . " font-size: {$fontSize}px; font-weight: {$fontWeight}; font-family: {$fontFamily}, sans-serif; color: {$color}; text-align: {$align}; white-space: nowrap;";
            @endphp
            <div style="{{ $textStyle }}">
                {{ $displayText }}
            </div>

        @elseif($type === 'photo')
            @php
                $borderRadius = $layer['border_radius'] ?? 12;
                $borderColor = $layer['border_color'] ?? '#818cf8';
                $borderWidth = $layer['border_width'] ?? 2;
                $imgStyle = $style . " width: {$w}px; height: {$h}px; border-radius: {$borderRadius}px; border: {$borderWidth}px solid {$borderColor}; object-fit: cover;";
            @endphp
            <img src="{{ $photoUrl }}" alt="Student Photo" style="{{ $imgStyle }}" />

        @elseif($type === 'logo')
            @php
                $borderRadius = $layer['border_radius'] ?? 8;
                $imgStyle = $style . " width: {$w}px; height: {$h}px; border-radius: {$borderRadius}px; object-fit: contain;";
            @endphp
            @if($schoolLogoUrl)
                <img src="{{ $schoolLogoUrl }}" alt="School Logo" style="{{ $imgStyle }}" />
            @else
                <div style="{{ $imgStyle }} background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8; font-weight: bold; font-size: 10px;">
                    LOGO
                </div>
            @endif

        @elseif($type === 'qr')
            @php
                $qrStyle = $style . " width: {$w}px; height: {$h}px; background: white; padding: 4px; border-radius: 8px; display: flex; align-items: center; justify-content: center;";
            @endphp
            <div style="{{ $qrStyle }}">
                <svg viewBox="0 0 24 24" style="width: 100%; height: 100%;" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1" fill="#0f172a" />
                    <rect x="14" y="3" width="7" height="7" rx="1" fill="#0f172a" />
                    <rect x="3" y="14" width="7" height="7" rx="1" fill="#0f172a" />
                    <path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 18h3v3h-3z" fill="#0f172a" />
                </svg>
            </div>
        @endif
    @endforeach
</div>
