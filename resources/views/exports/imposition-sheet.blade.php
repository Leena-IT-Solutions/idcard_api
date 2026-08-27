<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Imposition Print Sheet</title>
    <style>
        @page {
            size: {{ $layout['page_width_mm'] }}mm {{ $layout['page_height_mm'] }}mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            position: relative;
            width: {{ $layout['page_width_mm'] }}mm;
            height: {{ $layout['page_height_mm'] }}mm;
            page-break-after: always;
            overflow: hidden;
            background: #ffffff;
        }
        .card-cell {
            position: absolute;
            width: {{ $layout['card_outer_width'] }}mm;
            height: {{ $layout['card_outer_height'] }}mm;
            z-index: 10;
        }
        .trim-area {
            position: absolute;
            top: {{ $layout['bleed_mm'] }}mm;
            left: {{ $layout['bleed_mm'] }}mm;
            width: {{ $layout['card_width_mm'] }}mm;
            height: {{ $layout['card_height_mm'] }}mm;
            overflow: hidden;
        }
        .card-inner-scale {
            transform-origin: top left;
        }
        .marks-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: {{ $layout['page_width_mm'] }}mm;
            height: {{ $layout['page_height_mm'] }}mm;
            pointer-events: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    @php
        $showCuttingMarks = !empty($layout['show_cutting_marks']);
        $showCenterMarks = !empty($layout['show_center_marks']);
        $hGutter = $layout['horizontal_gutter_mm'] ?? $layout['gutter_mm'] ?? 4.0;
        $vGutter = $layout['vertical_gutter_mm'] ?? $layout['gutter_mm'] ?? 4.0;
        $cols = $layout['cols'];
        $rows = $layout['rows'];
        $cardW = $layout['card_outer_width'];
        $cardH = $layout['card_outer_height'];
        $startLeft = $layout['start_left_mm'];
        $startTop = $layout['start_top_mm'];
        $pageW = $layout['page_width_mm'];
        $pageH = $layout['page_height_mm'];
    @endphp

    @foreach ($pages as $pageCards)
        <div class="page">
            <!-- Cards Layer -->
            @foreach ($pageCards as $cell)
                @php
                    $col = $cell['col'];
                    $row = $cell['row'];
                    $leftMm = $startLeft + ($col * ($cardW + $hGutter));
                    $topMm = $startTop + ($row * ($cardH + $vGutter));
                    $student = $cell['student'];
                    $template = $cell['template'];
                    $school = $cell['school'];
                @endphp

                <div class="card-cell" style="left: {{ $leftMm }}mm; top: {{ $topMm }}mm;">
                    <div class="trim-area">
                        @php
                            $orientation = $template->orientation ?? 'landscape';
                            $isPortrait = $orientation === 'portrait';
                            $isPunch = ($cardSize ?? 'bleed') === 'punch';
                            if ($isPunch) {
                                $targetWidthPx = $isPortrait ? 604.4 : 966.0;
                                $targetHeightPx = $isPortrait ? 966.0 : 604.4;
                            } else {
                                $targetWidthPx = $isPortrait ? 638 : 1011;
                                $targetHeightPx = $isPortrait ? 1011 : 638;
                            }
                            
                            $targetWidthMm = $layout['card_width_mm'];
                            $scaleRatio = $targetWidthMm / ($targetWidthPx / 3.7795275591); 
                        @endphp
                        <div class="card-inner-scale" style="transform: scale({{ round($scaleRatio, 4) }}); width: {{ round($targetWidthPx) }}px; height: {{ round($targetHeightPx) }}px;">
                            <x-id-card-renderer :template="$template" :student="$student" :school="$school" :scale="1.0" :forExport="true" :isMirrored="$isMirrored ?? false" :cardSize="$cardSize ?? 'bleed'" />
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Vector Marks Overlay Layer (Rendered on top of all cards) -->
            @if ($showCuttingMarks || $showCenterMarks)
                <svg class="marks-overlay" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $pageW }} {{ $pageH }}" width="{{ $pageW }}mm" height="{{ $pageH }}mm">
                    @if ($showCuttingMarks)
                        <!-- Cutting / Crop Marks -->
                        <g stroke="#000000" stroke-width="0.3" stroke-linecap="square">
                            @foreach ($pageCards as $cell)
                                @php
                                    $col = $cell['col'];
                                    $row = $cell['row'];
                                    $x1 = $startLeft + ($col * ($cardW + $hGutter));
                                    $x2 = $x1 + $cardW;
                                    $y1 = $startTop + ($row * ($cardH + $vGutter));
                                    $y2 = $y1 + $cardH;
                                    $markLen = 3.5;
                                    $gap = 0.5;
                                @endphp
                                <!-- Top-Left Corner -->
                                <line x1="{{ $x1 }}" y1="{{ $y1 - $gap }}" x2="{{ $x1 }}" y2="{{ $y1 - $markLen }}" />
                                <line x1="{{ $x1 - $gap }}" y1="{{ $y1 }}" x2="{{ $x1 - $markLen }}" y2="{{ $y1 }}" />

                                <!-- Top-Right Corner -->
                                <line x1="{{ $x2 }}" y1="{{ $y1 - $gap }}" x2="{{ $x2 }}" y2="{{ $y1 - $markLen }}" />
                                <line x1="{{ $x2 + $gap }}" y1="{{ $y1 }}" x2="{{ $x2 + $markLen }}" y2="{{ $y1 }}" />

                                <!-- Bottom-Left Corner -->
                                <line x1="{{ $x1 }}" y1="{{ $y2 + $gap }}" x2="{{ $x1 }}" y2="{{ $y2 + $markLen }}" />
                                <line x1="{{ $x1 - $gap }}" y1="{{ $y2 }}" x2="{{ $x1 - $markLen }}" y2="{{ $y2 }}" />

                                <!-- Bottom-Right Corner -->
                                <line x1="{{ $x2 }}" y1="{{ $y2 + $gap }}" x2="{{ $x2 }}" y2="{{ $y2 + $markLen }}" />
                                <line x1="{{ $x2 + $gap }}" y1="{{ $y2 }}" x2="{{ $x2 + $markLen }}" y2="{{ $y2 }}" />
                            @endforeach
                        </g>
                    @endif

                    @if ($showCenterMarks && !empty($layout['center_marks']))
                        <!-- Registration Center Marks -->
                        @foreach ($layout['center_marks'] as $cm)
                            <g transform="translate({{ $cm['x'] }}, {{ $cm['y'] }}) scale(0.25)">
                                <circle cx="0" cy="0" r="10" fill="#ffffff" stroke="#000000" stroke-width="1.2" />
                                <path d="M0,0 L-10,0 A10,10 0 0,1 0,-10 Z" fill="#93c5fd" />
                                <path d="M0,0 L10,0 A10,10 0 0,1 0,10 Z" fill="#93c5fd" />
                                <circle cx="0" cy="0" r="10" fill="none" stroke="#000000" stroke-width="1.2" />
                                <line x1="0" y1="-12" x2="0" y2="12" stroke="#000000" stroke-width="1.2" />
                                <line x1="-12" y1="0" x2="12" y2="0" stroke="#000000" stroke-width="1.2" />
                            </g>
                        @endforeach
                    @endif
                </svg>
            @endif
        </div>
    @endforeach
</body>
</html>
