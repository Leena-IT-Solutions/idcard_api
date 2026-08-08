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
        /* Crop / Trim Marks */
        .crop-mark {
            position: absolute;
            background-color: #000000;
            z-index: 100;
        }
        /* Top-Left */
        .cm-tl-v {
            top: 0;
            left: {{ $layout['bleed_mm'] }}mm;
            width: 0.2mm;
            height: {{ $layout['bleed_mm'] - 1 }}mm;
        }
        .cm-tl-h {
            top: {{ $layout['bleed_mm'] }}mm;
            left: 0;
            width: {{ $layout['bleed_mm'] - 1 }}mm;
            height: 0.2mm;
        }
        /* Top-Right */
        .cm-tr-v {
            top: 0;
            right: {{ $layout['bleed_mm'] }}mm;
            width: 0.2mm;
            height: {{ $layout['bleed_mm'] - 1 }}mm;
        }
        .cm-tr-h {
            top: {{ $layout['bleed_mm'] }}mm;
            right: 0;
            width: {{ $layout['bleed_mm'] - 1 }}mm;
            height: 0.2mm;
        }
        /* Bottom-Left */
        .cm-bl-v {
            bottom: 0;
            left: {{ $layout['bleed_mm'] }}mm;
            width: 0.2mm;
            height: {{ $layout['bleed_mm'] - 1 }}mm;
        }
        .cm-bl-h {
            bottom: {{ $layout['bleed_mm'] }}mm;
            left: 0;
            width: {{ $layout['bleed_mm'] - 1 }}mm;
            height: 0.2mm;
        }
        /* Bottom-Right */
        .cm-br-v {
            bottom: 0;
            right: {{ $layout['bleed_mm'] }}mm;
            width: 0.2mm;
            height: {{ $layout['bleed_mm'] - 1 }}mm;
        }
        .cm-br-h {
            bottom: {{ $layout['bleed_mm'] }}mm;
            right: 0;
            width: {{ $layout['bleed_mm'] - 1 }}mm;
            height: 0.2mm;
        }
        .card-inner-scale {
            transform-origin: top left;
        }
    </style>
</head>
<body>
    @foreach ($pages as $pageCards)
        <div class="page">
            @foreach ($pageCards as $cell)
                @php
                    $col = $cell['col'];
                    $row = $cell['row'];
                    $leftMm = $layout['start_left_mm'] + ($col * ($layout['card_outer_width'] + $layout['gutter_mm']));
                    $topMm = $layout['start_top_mm'] + ($row * ($layout['card_outer_height'] + $layout['gutter_mm']));
                    $student = $cell['student'];
                    $template = $cell['template'];
                    $school = $cell['school'];
                @endphp

                <div class="card-cell" style="left: {{ $leftMm }}mm; top: {{ $topMm }}mm;">
                    <!-- Hairline Crop Marks -->
                    <div class="crop-mark cm-tl-v"></div>
                    <div class="crop-mark cm-tl-h"></div>
                    <div class="crop-mark cm-tr-v"></div>
                    <div class="crop-mark cm-tr-h"></div>
                    <div class="crop-mark cm-bl-v"></div>
                    <div class="crop-mark cm-bl-h"></div>
                    <div class="crop-mark cm-br-v"></div>
                    <div class="crop-mark cm-br-h"></div>

                    <!-- Trim Area with Exact Card Content -->
                    <div class="trim-area">
                        @php
                            $orientation = $template->orientation ?? 'landscape';
                            $isPortrait = $orientation === 'portrait';
                            $targetWidthPx = $isPortrait ? 638 : 1011;
                            $targetHeightPx = $isPortrait ? 1011 : 638;
                            
                            // Calculate scale factor from rendered PX to MM trim box
                            $targetWidthMm = $layout['card_width_mm'];
                            $scaleRatio = $targetWidthMm / ($targetWidthPx / 3.7795275591); 
                        @endphp
                        <div class="card-inner-scale" style="transform: scale({{ round($scaleRatio, 4) }}); width: {{ $targetWidthPx }}px; height: {{ $targetHeightPx }}px;">
                            <x-id-card-renderer :template="$template" :student="$student" :school="$school" :scale="1.0" :forExport="true" :isMirrored="$isMirrored ?? false" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
