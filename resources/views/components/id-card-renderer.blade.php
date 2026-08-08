@props([
    'template' => null,
    'student' => null,
    'school' => null,
    'scale' => 1.0,
    'previewMode' => false,
    'forExport' => false,
    'isMirrored' => false,
])

@php
    if (!$template) return;

    // Determine layout config array
    $config = is_array($template->layout_config ?? null) 
        ? $template->layout_config 
        : (is_string($template->layout_config ?? null) ? json_decode($template->layout_config, true) : []);

    $orientation = is_object($template) ? ($template->orientation ?? 'landscape') : ($template['orientation'] ?? 'landscape');
    $isPortrait = $orientation === 'portrait';
    $widthPx = $isPortrait ? 638 : 1011;
    $heightPx = $isPortrait ? 1011 : 638;

    $bgPath = is_object($template) 
        ? ($template->background_image ?: (is_object($template->masterTemplate ?? null) ? $template->masterTemplate->background_image : null)) 
        : ($template['background_image'] ?? ($template['master_template']['background_image'] ?? null));

    // Student fields
    $firstName = is_object($student) ? ($student->first_name ?? '') : ($student['first_name'] ?? '');
    $middleName = is_object($student) ? ($student->middle_name ?? '') : ($student['middle_name'] ?? '');
    $lastName = is_object($student) ? ($student->last_name ?? '') : ($student['last_name'] ?? '');
    $fullName = trim("$firstName " . ($middleName ? "$middleName " : "") . $lastName);
    if (!$fullName && $previewMode) $fullName = 'Aaditya Sonu Thakur';

    $dob = is_object($student) ? ($student->dob ?? '') : ($student['dob'] ?? '');
    if ($dob) {
        try {
            $formattedDob = \Carbon\Carbon::parse($dob)->format('d/m/Y');
        } catch (\Exception $e) {
            $formattedDob = $dob;
        }
    } else {
        $formattedDob = $previewMode ? '2017-10-27' : '';
    }

    $bloodGroup = is_object($student) ? ($student->blood_group ?? '') : ($student['blood_group'] ?? '');
    if (!$bloodGroup && $previewMode) $bloodGroup = 'AB+';

    $gender = is_object($student) ? ($student->gender ?? '') : ($student['gender'] ?? '');
    if (!$gender && $previewMode) $gender = 'Male';

    $contact = is_object($student) ? ($student->contact_number ?? '') : ($student['contact_number'] ?? '');
    if (!$contact && $previewMode) $contact = '9730777244';

    $address = is_object($student) ? ($student->address ?? '') : ($student['address'] ?? '');
    if (!$address && $previewMode) $address = 'Sarvodhya Nagar Phase 3 Flat No 704';

    $pincode = is_object($student) ? ($student->pincode ?? '') : ($student['pincode'] ?? '');
    if (!$pincode && $previewMode) $pincode = '400001';

    $photoPath = is_object($student) ? ($student->photo_path ?? null) : ($student['photo_path'] ?? null);
    $photoUrl = $photoPath 
        ? (str_starts_with($photoPath, 'http') ? $photoPath : asset('storage/' . $photoPath)) 
        : null;

    // Enrollment details
    $enrollments = is_object($student) ? ($student->campaignStudents ?? []) : ($student['campaignStudents'] ?? []);
    $firstE = is_object($enrollments) && method_exists($enrollments, 'first') ? $enrollments->first() : (is_array($enrollments) ? ($enrollments[0] ?? null) : null);
    
    $gradeName = $previewMode ? 'V' : '';
    $divName = $previewMode ? 'B' : '';
    $rollNo = $previewMode ? '202' : '';
    $serialNo = $previewMode ? '202' : '';
    $campaignName = $previewMode ? 'iCard 2026-27' : '';

    if ($firstE) {
        $gradeObj = is_object($firstE) ? ($firstE->grade ?? null) : ($firstE['grade'] ?? null);
        $divObj = is_object($firstE) ? ($firstE->division ?? null) : ($firstE['division'] ?? null);
        $cObj = is_object($firstE) ? ($firstE->campaign ?? null) : ($firstE['campaign'] ?? null);

        $gradeName = is_object($gradeObj) ? ($gradeObj->name ?? $gradeName) : ($gradeObj['name'] ?? $gradeName);
        $divName = is_object($divObj) ? ($divObj->name ?? $divName) : ($divObj['name'] ?? $divName);
        $rollNo = is_object($firstE) ? ($firstE->roll_no ?? $rollNo) : ($firstE['roll_no'] ?? $rollNo);
        $serialNo = is_object($firstE) ? ($firstE->serial_number ?? $serialNo) : ($firstE['serial_number'] ?? $serialNo);
        $campaignName = is_object($cObj) ? ($cObj->name ?? $campaignName) : ($cObj['name'] ?? $campaignName);
    }

    // School details
    $schoolName = is_object($school) ? ($school->name ?? '') : ($school['name'] ?? '');
    if (!$schoolName && $previewMode) $schoolName = 'Sarvodya Vidyalay';

    $schoolCode = is_object($school) ? ($school->school_code ?? '') : ($school['school_code'] ?? '');
    if (!$schoolCode && $previewMode) $schoolCode = 'SV-2026';

    $principalName = is_object($school) ? ($school->principal_name ?? '') : ($school['principal_name'] ?? '');
    if (!$principalName && $previewMode) $principalName = 'Dr. R. K. Sharma';

    $schoolContact = is_object($school) ? ($school->contact_number ?? '') : ($school['contact_number'] ?? '');
    if (!$schoolContact && $previewMode) $schoolContact = '9820198201';

    $schoolEmail = is_object($school) ? ($school->email ?? '') : ($school['email'] ?? '');
    if (!$schoolEmail && $previewMode) $schoolEmail = 'info@sarvodya.edu.in';

    $schoolWebsite = is_object($school) ? ($school->website ?? '') : ($school['website'] ?? '');
    if (!$schoolWebsite && $previewMode) $schoolWebsite = 'www.sarvodya.edu.in';

    $schoolAddress = is_object($school) ? ($school->address ?? '') : ($school['address'] ?? '');
    if (!$schoolAddress && $previewMode) $schoolAddress = 'Station Road, Mumbai';

    $schoolLogo = is_object($school) ? ($school->logo_path ?? null) : ($school['logo_path'] ?? null);
    $schoolLogoUrl = $schoolLogo 
        ? (str_starts_with($schoolLogo, 'http') ? $schoolLogo : asset('storage/' . $schoolLogo)) 
        : null;

    $replaceMap = [
        '{Student Name}' => $fullName,
        '{student_name}' => $fullName,
        '{First Name}' => $firstName,
        '{first_name}' => $firstName,
        '{Middle Name}' => $middleName,
        '{middle_name}' => $middleName,
        '{Last Name}' => $lastName,
        '{last_name}' => $lastName,
        '{DOB}' => $formattedDob,
        '{dob}' => $formattedDob,
        '{Blood Group}' => $bloodGroup,
        '{blood_group}' => $bloodGroup,
        '{Gender}' => $gender,
        '{gender}' => $gender,
        '{Contact Number}' => $contact,
        '{contact_number}' => $contact,
        '{Address}' => $address,
        '{address}' => $address,
        '{Pincode}' => $pincode,
        '{pincode}' => $pincode,
        '{Grade}' => $gradeName,
        '{grade}' => $gradeName,
        '{Standard}' => $gradeName,
        '{standard}' => $gradeName,
        '{Division}' => $divName,
        '{division}' => $divName,
        '{Div}' => $divName,
        '{Grade ({grade}) Div ({division})}' => ($gradeName && $divName) ? "Grade ({$gradeName}) Div ({$divName})" : ($gradeName ?: $divName),
        '{Roll No}' => $rollNo,
        '{roll_no}' => $rollNo,
        '{Ref No}' => $serialNo,
        '{serial_number}' => $serialNo,
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

    $resolveImageUrl = function($path) use ($forExport) {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        $cleanPath = ltrim($path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }
        if ($forExport) {
            $localPath = storage_path('app/public/' . $cleanPath);
            if (!file_exists($localPath)) {
                $localPath = public_path('storage/' . $cleanPath);
            }
            if (!file_exists($localPath)) {
                $localPath = public_path($cleanPath);
            }
            if (file_exists($localPath)) {
                $mime = @mime_content_type($localPath) ?: 'image/png';
                if (str_ends_with(strtolower($localPath), '.svg')) {
                    $mime = 'image/svg+xml';
                }
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
            }
        }
        if (file_exists(public_path($cleanPath))) {
            return asset($cleanPath);
        }
        return asset('storage/' . $cleanPath);
    };

    $bgUrl = $resolveImageUrl($bgPath);
    $photoUrl = $resolveImageUrl($photoPath);
    $schoolLogoUrl = $resolveImageUrl($schoolLogo);

    $scaledW = round($widthPx * $scale);
    $scaledH = round($heightPx * $scale);

    $cardStyle = $forExport 
        ? "position: relative; overflow: hidden; width: {$widthPx}px; height: {$heightPx}px; transform: scale({$scale}); transform-origin: top left; background-color: #ffffff;" 
        : "position: relative; overflow: hidden; border-radius: 12px; width: {$widthPx}px; height: {$heightPx}px; transform: scale({$scale}); transform-origin: top left; background-color: #ffffff;";

    $bgStyle = "position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: fill; pointer-events: none; z-index: 0; display: block;";
@endphp

<!-- Responsive Container Wrapper -->
<div style="position: relative; overflow: hidden; flex-shrink: 0; display: inline-block; vertical-align: top; user-select: none; width: {{ $scaledW }}px; height: {{ $scaledH }}px;">
    <!-- Actual Native Scale Inner Card -->
    <div style="{{ $cardStyle }}">
        <div style="width: 100%; height: 100%; position: relative; {{ $isMirrored ? 'transform: scaleX(-1); transform-origin: 50% 50%;' : '' }}">
            @if($bgUrl)
                <img src="{{ $bgUrl }}" style="{{ $bgStyle }}" alt="Card Background" />
            @endif

        @foreach($config as $layer)
            @php
                $type = $layer['type'] ?? 'text';
                $x = $layer['x'] ?? 0;
                $y = $layer['y'] ?? 0;
                $w = $layer['width'] ?? 'auto';
                $h = $layer['height'] ?? 'auto';
                $rot = $layer['rotation'] ?? 0;

                $style = "position: absolute; left: {$x}px; top: {$y}px; transform: rotate({$rot}deg); transform-origin: top left; z-index: 10;";
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

                    $hasCustomWidth = !empty($w) && $w > 0;
                    $widthStyle = $hasCustomWidth 
                        ? "width: {$w}px; max-width: {$w}px; white-space: normal; word-break: break-word;" 
                        : "width: max-content; white-space: nowrap;";

                    $textStyle = $style . " font-size: {$fontSize}pt; font-weight: {$fontWeight}; font-family: '{$fontFamily}', sans-serif; color: {$color}; text-align: {$align}; {$widthStyle}";
                @endphp
                <div style="{{ $textStyle }}">
                    {{ $displayText }}
                </div>

            @elseif($type === 'photo')
                @php
                    $borderRadius = $layer['border_radius'] ?? 12;
                    $borderColor = $layer['border_color'] ?? '#818cf8';
                    $borderWidth = $layer['border_width'] ?? 2;
                    $shape = $layer['shape'] ?? (($borderRadius >= 999) ? 'round' : 'square');
                    $radiusStyle = ($borderRadius >= 999 || $shape === 'round') ? '50%' : ($borderRadius . 'px');
                    $boxStyle = $style . " width: {$w}px; height: {$h}px; border-radius: {$radiusStyle}; border: {$borderWidth}px solid {$borderColor}; overflow: hidden; box-sizing: border-box;";
                @endphp
                <div style="{{ $boxStyle }}">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Student Photo" style="width: 100%; height: 100%; object-fit: cover;" />
                    @else
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #818cf8;" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            <span style="font-size: 8px; font-weight: 800; color: #a5b4fc; text-transform: uppercase; margin-top: 2px;">STUDENT PHOTO</span>
                        </div>
                    @endif
                </div>

            @elseif($type === 'logo')
                @php
                    $borderRadius = $layer['border_radius'] ?? 8;
                    $logoStyle = $style . " width: {$w}px; height: {$h}px; border-radius: {$borderRadius}px; overflow: hidden;";
                @endphp
                <div style="{{ $logoStyle }}">
                    @if($schoolLogoUrl)
                        <img src="{{ $schoolLogoUrl }}" alt="School Logo" style="width: 100%; height: 100%; object-fit: contain;" />
                    @else
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #312e81 0%, #4338ca 100%); border: 1.5px dashed #818cf8; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; box-sizing: border-box;">
                            <svg viewBox="0 0 24 24" style="width: 40%; height: 40%; color: #fbbf24;" fill="currentColor">
                                <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM3.82 9L12 4.54 20.18 9 12 13.46 3.82 9zM5 14.45v3.55l7 3.82 7-3.82v-3.55l-7 3.81-7-3.81z"/>
                            </svg>
                        </div>
                    @endif
                </div>

            @elseif($type === 'qr')
                @php
                    $qrStyle = $style . " width: {$w}px; height: {$h}px; background: white; padding: 4px; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;";
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
    </div>
</div>
