@php
    // Shared shape drawing logic used by both the Canva Studio editor canvas
    // and the id-card-renderer export pipeline, so preview and export never drift.
    $shapeType = in_array($layer['shape_type'] ?? '', ['circle', 'line', 'rectangle']) ? $layer['shape_type'] : 'rectangle';
    $w = max(1, (float)($layer['width'] ?? 120));
    $h = max(1, (float)($layer['height'] ?? 60));

    $fillType = ($layer['fill_type'] ?? 'solid') === 'none' ? 'none' : 'solid';
    $fillColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string)($layer['fill_color'] ?? '')) ? $layer['fill_color'] : '#4f46e5';
    $fillOpacity = max(0, min(100, (float)($layer['fill_opacity'] ?? 100))) / 100;

    $strokeColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string)($layer['stroke_color'] ?? '')) ? $layer['stroke_color'] : '#312e81';
    $strokeWidth = max(0, min(40, (float)($layer['stroke_width'] ?? 0)));
    $strokeStyle = in_array($layer['stroke_style'] ?? 'solid', ['solid', 'dashed', 'dotted']) ? ($layer['stroke_style'] ?? 'solid') : 'solid';

    $dasharray = match ($strokeStyle) {
        'dashed' => (max(2, $strokeWidth) * 3) . ' ' . (max(2, $strokeWidth) * 2),
        'dotted' => '0.1 ' . (max(2, $strokeWidth) * 2),
        default => null,
    };
    $strokeLinecap = $strokeStyle === 'dotted' ? 'round' : 'butt';
@endphp
<svg width="100%" height="100%" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" style="display: block; overflow: visible; pointer-events: none;">
    @if($shapeType === 'circle')
        <ellipse
            cx="{{ $w / 2 }}" cy="{{ $h / 2 }}"
            rx="{{ max(0, $w / 2 - $strokeWidth / 2) }}" ry="{{ max(0, $h / 2 - $strokeWidth / 2) }}"
            fill="{{ $fillType === 'none' ? 'none' : $fillColor }}" fill-opacity="{{ $fillOpacity }}"
            @if($strokeWidth > 0)
                stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"
                @if($dasharray) stroke-dasharray="{{ $dasharray }}" stroke-linecap="{{ $strokeLinecap }}" @endif
            @endif
            vector-effect="non-scaling-stroke"
        />
    @elseif($shapeType === 'line')
        @php $ly = $h / 2; @endphp
        <line
            x1="0" y1="{{ $ly }}" x2="{{ $w }}" y2="{{ $ly }}"
            stroke="{{ $strokeColor }}" stroke-width="{{ max(1, $strokeWidth) }}"
            @if($dasharray) stroke-dasharray="{{ $dasharray }}" @endif
            stroke-linecap="{{ $strokeLinecap }}"
            vector-effect="non-scaling-stroke"
        />
    @else
        @php
            $cornerPill = !empty($layer['corner_radius_pill']);
            $maxRadius = max(0, min($w, $h) / 2);
            $cornerRadius = $cornerPill ? $maxRadius : max(0, min((float)($layer['corner_radius'] ?? 0), $maxRadius));
            $inset = $strokeWidth / 2;
        @endphp
        <rect
            x="{{ $inset }}" y="{{ $inset }}"
            width="{{ max(0, $w - $strokeWidth) }}" height="{{ max(0, $h - $strokeWidth) }}"
            rx="{{ $cornerRadius }}" ry="{{ $cornerRadius }}"
            fill="{{ $fillType === 'none' ? 'none' : $fillColor }}" fill-opacity="{{ $fillOpacity }}"
            @if($strokeWidth > 0)
                stroke="{{ $strokeColor }}" stroke-width="{{ $strokeWidth }}"
                @if($dasharray) stroke-dasharray="{{ $dasharray }}" @endif
            @endif
            vector-effect="non-scaling-stroke"
        />
    @endif
</svg>
