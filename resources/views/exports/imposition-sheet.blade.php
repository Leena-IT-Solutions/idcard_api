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
        }
        .trim-area {
            position: absolute;
            top: {{ $layout['bleed_mm'] }}mm;
            left: {{ $layout['bleed_mm'] }}mm;
            width: {{ $layout['card_width_mm'] }}mm;
            height: {{ $layout['card_height_mm'] }}mm;
            overflow: hidden;
        }

        /* Cutting / Crop Guides (Corner Marks) */
        .crop-mark {
            position: absolute;
            background-color: #000000;
            z-index: 100;
        }
        /* Top-Left */
        .cm-tl-v {
            top: -3.5mm;
            left: 0;
            width: 0.25mm;
            height: 3.0mm;
        }
        .cm-tl-h {
            top: 0;
            left: -3.5mm;
            width: 3.0mm;
            height: 0.25mm;
        }
        /* Top-Right */
        .cm-tr-v {
            top: -3.5mm;
            right: 0;
            width: 0.25mm;
            height: 3.0mm;
        }
        .cm-tr-h {
            top: 0;
            right: -3.5mm;
            width: 3.0mm;
            height: 0.25mm;
        }
        /* Bottom-Left */
        .cm-bl-v {
            bottom: -3.5mm;
            left: 0;
            width: 0.25mm;
            height: 3.0mm;
        }
        .cm-bl-h {
            bottom: 0;
            left: -3.5mm;
            width: 3.0mm;
            height: 0.25mm;
        }
        /* Bottom-Right */
        .cm-br-v {
            bottom: -3.5mm;
            right: 0;
            width: 0.25mm;
            height: 3.0mm;
        }
        .cm-br-h {
            bottom: 0;
            right: -3.5mm;
            width: 3.0mm;
            height: 0.25mm;
        }

        /* Registration Center Mark */
        .center-reg-mark {
            position: absolute;
            width: 5.5mm;
            height: 5.5mm;
            transform: translate(-50%, -50%);
            z-index: 150;
            pointer-events: none;
        }

        .card-inner-scale {
            transform-origin: top left;
        }
    </style>
</head>
<body>
    @foreach ($pages as $pageCards)
        <div class="page">
            <!-- Center Registration Targets (Marks) -->
            @if(!empty($layout['show_center_marks']) && !empty($layout['center_marks']))
                @foreach ($layout['center_marks'] as $cm)
                    <div class="center-reg-mark" style="left: {{ $cm['x'] }}mm; top: {{ $cm['y'] }}mm;">
                        <svg width="5.5mm" height="5.5mm" viewBox="0 0 24 24" style="display: block;">
                            <circle cx="12" cy="12" r="9.5" fill="#ffffff" stroke="#000000" stroke-width="1.2" />
                            <path d="M12,12 L2.5,12 A9.5,9.5 0 0,1 12,2.5 Z" fill="#93c5fd" />
                            <path d="M12,12 L21.5,12 A9.5,9.5 0 0,1 12,21.5 Z" fill="#93c5fd" />
                            <circle cx="12" cy="12" r="9.5" fill="none" stroke="#000000" stroke-width="1.2" />
                            <line x1="12" y1="0" x2="12" y2="24" stroke="#000000" stroke-width="1.2" />
                            <line x1="0" y1="12" x2="24" y2="12" stroke="#000000" stroke-width="1.2" />
                        </svg>
                    </div>
                @endforeach
            @endif

            <!-- Cards & Cutting Guides -->
            @foreach ($pageCards as $cell)
                @php
                    $col = $cell['col'];
                    $row = $cell['row'];
                    $hGutter = $layout['horizontal_gutter_mm'] ?? $layout['gutter_mm'] ?? 4.0;
                    $vGutter = $layout['vertical_gutter_mm'] ?? $layout['gutter_mm'] ?? 4.0;
                    $leftMm = $layout['start_left_mm'] + ($col * ($layout['card_outer_width'] + $hGutter));
                    $topMm = $layout['start_top_mm'] + ($row * ($layout['card_outer_height'] + $vGutter));
                    $student = $cell['student'];
                    $template = $cell['template'];
                    $school = $cell['school'];
                @endphp

                <div class="card-cell" style="left: {{ $leftMm }}mm; top: {{ $topMm }}mm;">
                    @if(!empty($layout['show_cutting_marks']))
                        <!-- Hairline Corner Cutting Marks -->
                        <div class="crop-mark cm-tl-v"></div>
                        <div class="crop-mark cm-tl-h"></div>
                        <div class="crop-mark cm-tr-v"></div>
                        <div class="crop-mark cm-tr-h"></div>
                        <div class="crop-mark cm-bl-v"></div>
                        <div class="crop-mark cm-bl-h"></div>
                        <div class="crop-mark cm-br-v"></div>
                        <div class="crop-mark cm-br-h"></div>
                    @endif

                    <!-- Trim Area with Exact Card Content -->
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
                            
                            // Calculate scale factor from rendered PX to MM trim box
                            $targetWidthMm = $layout['card_width_mm'];
                            $scaleRatio = $targetWidthMm / ($targetWidthPx / 3.7795275591); 
                        @endphp
                        <div class="card-inner-scale" style="transform: scale({{ round($scaleRatio, 4) }}); width: {{ round($targetWidthPx) }}px; height: {{ round($targetHeightPx) }}px;">
                            <x-id-card-renderer :template="$template" :student="$student" :school="$school" :scale="1.0" :forExport="true" :isMirrored="$isMirrored ?? false" :cardSize="$cardSize ?? 'bleed'" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
