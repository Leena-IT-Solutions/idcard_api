<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Single Card PDF</title>
    <style>
        @page {
            size: {{ $cardWidthMm }}mm {{ $cardHeightMm }}mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: {{ $cardWidthMm }}mm;
            height: {{ $cardHeightMm }}mm;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .card-page {
            width: {{ $cardWidthMm }}mm;
            height: {{ $cardHeightMm }}mm;
            page-break-after: always;
            overflow: hidden;
            position: relative;
        }
        .card-page:last-child {
            page-break-after: avoid;
        }
        .card-inner-scale {
            transform-origin: top left;
        }
    </style>
</head>
<body style="margin: 0; padding: 0;">
    @foreach ($items as $item)
        @php
            $template = $item['template'];
            $student = $item['student'];
            $school = $item['school'];
            
            $orientation = $template->orientation ?? 'landscape';
            $isPortrait = $orientation === 'portrait';
            $targetWidthPx = $isPortrait ? 638 : 1011;
            $targetHeightPx = $isPortrait ? 1011 : 638;
            
            $scaleRatio = $cardWidthMm / ($targetWidthPx / 3.7795275591);
        @endphp
        <div class="card-page">
            <div class="card-inner-scale" style="transform: scale({{ round($scaleRatio, 4) }}); width: {{ $targetWidthPx }}px; height: {{ $targetHeightPx }}px;">
                <x-id-card-renderer :template="$template" :student="$student" :school="$school" :scale="1.0" :forExport="true" />
            </div>
        </div>
    @endforeach
</body>
</html>
