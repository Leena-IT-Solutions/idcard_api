<?php

/**
 * Master Template Generator Script for 100 Master ID Card Templates
 */

$bgDir = __DIR__ . '/../../storage/app/public/templates/backgrounds';
if (!file_exists($bgDir)) {
    mkdir($bgDir, 0755, true);
}

// Color palettes for 100 templates
$palettes = [
    // Deep Navy & Gold
    ['bg' => '#0f172a', 'h1' => '#1e293b', 'h2' => '#3b82f6', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#cbd5e1', 'border' => '#f59e0b'],
    ['bg' => '#020617', 'h1' => '#0f172a', 'h2' => '#1d4ed8', 'acc' => '#fbbf24', 'txt1' => '#ffffff', 'txt2' => '#94a3b8', 'border' => '#fbbf24'],
    ['bg' => '#1e1b4b', 'h1' => '#312e81', 'h2' => '#4338ca', 'acc' => '#e0e7ff', 'txt1' => '#ffffff', 'txt2' => '#c7d2fe', 'border' => '#818cf8'],
    ['bg' => '#1e293b', 'h1' => '#0f172a', 'h2' => '#2563eb', 'acc' => '#38bdf8', 'txt1' => '#ffffff', 'txt2' => '#cbd5e1', 'border' => '#38bdf8'],
    ['bg' => '#0f172a', 'h1' => '#1e1b4b', 'h2' => '#4338ca', 'acc' => '#a855f7', 'txt1' => '#ffffff', 'txt2' => '#e9d5ff', 'border' => '#c084fc'],
    
    // Burgundy & Crimson Gold
    ['bg' => '#450a0a', 'h1' => '#7f1d1d', 'h2' => '#991b1b', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#fca5a5', 'border' => '#f59e0b'],
    ['bg' => '#881337', 'h1' => '#9f1239', 'h2' => '#be123c', 'acc' => '#fde047', 'txt1' => '#ffffff', 'txt2' => '#fecdd3', 'border' => '#fde047'],
    ['bg' => '#581c87', 'h1' => '#6b21a8', 'h2' => '#7e22ce', 'acc' => '#f43f5e', 'txt1' => '#ffffff', 'txt2' => '#e9d5ff', 'border' => '#f43f5e'],
    
    // Emerald & Forest
    ['bg' => '#064e3b', 'h1' => '#047857', 'h2' => '#059669', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#a7f3d0', 'border' => '#34d399'],
    ['bg' => '#022c22', 'h1' => '#064e3b', 'h2' => '#0f766e', 'acc' => '#2dd4bf', 'txt1' => '#ffffff', 'txt2' => '#99f6e4', 'border' => '#2dd4bf'],
    ['bg' => '#14532d', 'h1' => '#15803d', 'h2' => '#16a34a', 'acc' => '#facc15', 'txt1' => '#ffffff', 'txt2' => '#bbf7d0', 'border' => '#facc15'],

    // Modern Light / Minimalist
    ['bg' => '#ffffff', 'h1' => '#1e293b', 'h2' => '#2563eb', 'acc' => '#3b82f6', 'txt1' => '#0f172a', 'txt2' => '#475569', 'border' => '#2563eb'],
    ['bg' => '#f8fafc', 'h1' => '#0f172a', 'h2' => '#0284c7', 'acc' => '#38bdf8', 'txt1' => '#0f172a', 'txt2' => '#334155', 'border' => '#0284c7'],
    ['bg' => '#fafafa', 'h1' => '#18181b', 'h2' => '#7c3aed', 'acc' => '#a855f7', 'txt1' => '#18181b', 'txt2' => '#52525b', 'border' => '#7c3aed'],
    ['bg' => '#f0fdf4', 'h1' => '#166534', 'h2' => '#15803d', 'acc' => '#22c55e', 'txt1' => '#14532d', 'txt2' => '#166534', 'border' => '#16a34a'],
    ['bg' => '#fff1f2', 'h1' => '#9f1239', 'h2' => '#be123c', 'acc' => '#f43f5e', 'txt1' => '#881337', 'txt2' => '#9f1239', 'border' => '#e11d48'],

    // Cyber & Dark Neon
    ['bg' => '#09090b', 'h1' => '#18181b', 'h2' => '#27272a', 'acc' => '#06b6d4', 'txt1' => '#ffffff', 'txt2' => '#a1a1aa', 'border' => '#06b6d4'],
    ['bg' => '#050505', 'h1' => '#172554', 'h2' => '#1e40af', 'acc' => '#60a5fa', 'txt1' => '#ffffff', 'txt2' => '#93c5fd', 'border' => '#3b82f6'],
    ['bg' => '#0d0221', 'h1' => '#190a38', 'h2' => '#2b1055', 'acc' => '#ff007f', 'txt1' => '#ffffff', 'txt2' => '#d8b4fe', 'border' => '#ff007f'],
];

$names = [
    'Classic Academic Navy', 'Royal Blue Crest', 'Imperial Gold & Indigo', 'Executive Slate', 'Purple Velvet Modern',
    'Burgundy Gold Seal', 'Crimson Pride', 'Violet Crown', 'Emerald Elite', 'Teal Wave Minimal',
    'Forest Academy Green', 'Clean Minimal Cobalt', 'Sky Blue Professional', 'Modern Tech Violet', 'Mint Fresh Student',
    'Rose Minimal Red', 'Cyber Neon Cyan', 'Midnight Cobalt', 'Neon Magenta Dark', 'Titanium Dark Slate',
];

$templatesData = [];

for ($i = 1; $i <= 100; $i++) {
    $isPortrait = ($i % 3 === 0); // 70 Landscape, 30 Portrait
    $orientation = $isPortrait ? 'portrait' : 'landscape';
    $w = $isPortrait ? 638 : 1011;
    $h = $isPortrait ? 1011 : 638;

    $paletteIndex = ($i - 1) % count($palettes);
    $p = $palettes[$paletteIndex];
    $baseNameIndex = ($i - 1) % count($names);
    $templateName = $names[$baseNameIndex] . " #" . $i . ($isPortrait ? ' (Vertical)' : '');

    $filename = "master_template_" . sprintf("%03d", $i) . ".svg";
    $filePath = $bgDir . '/' . $filename;
    $dbPath = 'templates/backgrounds/' . $filename;

    // Generate SVG Content based on style variations
    $styleType = $i % 5;
    $svgContent = '';

    if ($styleType === 0) {
        // Curve Header & Footer Accent
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="100%" height="100%">
  <rect width="{$w}" height="{$h}" fill="{$p['bg']}"/>
  <path d="M0 0 H{$w} V140 Q{$w} 200 650 160 T0 120 Z" fill="{$p['h1']}"/>
  <path d="M0 0 H{$w} V90 Q700 150 400 110 T0 80 Z" fill="{$p['h2']}"/>
  <path d="M0 {$h} H{$w} V" . ($h - 60) . " Q500 " . ($h - 90) . " 0 " . ($h - 40) . " Z" fill="{$p['acc']}"/>
  <circle cx="900" cy="100" r="180" fill="{$p['acc']}" opacity="0.08"/>
</svg>
SVG;
    } elseif ($styleType === 1) {
        // Diagonal Slashes & Angles
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="100%" height="100%">
  <rect width="{$w}" height="{$h}" fill="{$p['bg']}"/>
  <polygon points="0,0 {$w},0 {$w},120 0,180" fill="{$p['h1']}"/>
  <polygon points="0,0 700,0 600,140 0,100" fill="{$p['h2']}"/>
  <polygon points="0,{$h} {$w},{$h} {$w}," . ($h - 45) . " 0," . ($h - 85) . "" fill="{$p['acc']}"/>
  <polygon points="0," . ($h - 10) . " {$w}," . ($h - 10) . " {$w}," . ($h - 40) . " 0," . ($h - 80) . "" fill="{$p['h2']}" opacity="0.6"/>
</svg>
SVG;
    } elseif ($styleType === 2) {
        // Side Strip / Border Accent Frame
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="100%" height="100%">
  <rect width="{$w}" height="{$h}" fill="{$p['bg']}"/>
  <rect x="0" y="0" width="24" height="{$h}" fill="{$p['acc']}"/>
  <rect x="24" y="0" width="12" height="{$h}" fill="{$p['h2']}"/>
  <path d="M0 0 H{$w} V110 Q500 130 0 110 Z" fill="{$p['h1']}"/>
  <rect x="0" y="{$h}-35" width="{$w}" height="35" fill="{$p['h1']}"/>
</svg>
SVG;
    } elseif ($styleType === 3) {
        // Geometric Hex / Tech Circles
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="100%" height="100%">
  <rect width="{$w}" height="{$h}" fill="{$p['bg']}"/>
  <circle cx="{$w}" cy="0" r="320" fill="{$p['h1']}"/>
  <circle cx="{$w}" cy="0" r="240" fill="{$p['h2']}"/>
  <circle cx="0" cy="{$h}" r="220" fill="{$p['acc']}" opacity="0.25"/>
  <rect x="0" y="0" width="{$w}" height="10" fill="{$p['acc']}"/>
  <rect x="0" y="" . ($h - 12) . "" width="{$w}" height="12" fill="{$p['h1']}"/>
</svg>
SVG;
    } else {
        // Minimalist Top Badge Ribbon
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="100%" height="100%">
  <rect width="{$w}" height="{$h}" fill="{$p['bg']}"/>
  <rect x="0" y="0" width="{$w}" height="120" fill="{$p['h1']}"/>
  <rect x="0" y="120" width="{$w}" height="8" fill="{$p['acc']}"/>
  <rect x="0" y="" . ($h - 40) . "" width="{$w}" height="40" fill="{$p['h2']}"/>
</svg>
SVG;
    }

    file_put_contents($filePath, trim($svgContent));

    // Build Layout Config
    if (!$isPortrait) {
        // Landscape Layout Config
        $layoutConfig = [
            [
                'id' => 'school_logo',
                'type' => 'logo',
                'label' => 'School Logo',
                'x' => 24,
                'y' => 18,
                'width' => 50,
                'height' => 50,
                'border_radius' => 8,
                'rotation' => 0,
            ],
            [
                'id' => 'school_name',
                'type' => 'text',
                'label' => 'School Name',
                'text' => '{School Name}',
                'x' => 86,
                'y' => 20,
                'font_size' => 16,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'school_code',
                'type' => 'text',
                'label' => 'School Code',
                'text' => 'CODE: {School Code}',
                'x' => 86,
                'y' => 44,
                'font_size' => 10,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => $p['acc'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'student_photo',
                'type' => 'photo',
                'label' => 'Student Photo',
                'x' => 24,
                'y' => 80,
                'width' => 90,
                'height' => 110,
                'border_radius' => 10,
                'border_color' => $p['border'],
                'border_width' => 2,
                'rotation' => 0,
            ],
            [
                'id' => 'student_name',
                'type' => 'text',
                'label' => 'Student Name',
                'text' => '{First Name} {Middle Name} {Last Name}',
                'x' => 130,
                'y' => 82,
                'font_size' => 15,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'grade_div',
                'type' => 'text',
                'label' => 'Grade & Division',
                'text' => 'Grade: {grade}  •  Division: {division}',
                'x' => 130,
                'y' => 106,
                'font_size' => 12,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => $p['acc'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'roll_dob',
                'type' => 'text',
                'label' => 'Roll & DOB',
                'text' => 'Roll No: {Roll No}  •  DOB: {DOB}',
                'x' => 130,
                'y' => 128,
                'font_size' => 11,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'blood_contact',
                'type' => 'text',
                'label' => 'Blood Group & Contact',
                'text' => 'Blood: {Blood Group}  •  Ph: {Contact Number}',
                'x' => 130,
                'y' => 148,
                'font_size' => 11,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'address',
                'type' => 'text',
                'label' => 'Residential Address',
                'text' => 'Addr: {Address} - {Pincode}',
                'x' => 130,
                'y' => 168,
                'font_size' => 10,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'qr_code',
                'type' => 'qr',
                'label' => 'QR Code',
                'x' => 280,
                'y' => 130,
                'width' => 55,
                'height' => 55,
                'rotation' => 0,
            ],
        ];
    } else {
        // Portrait Layout Config
        $layoutConfig = [
            [
                'id' => 'school_logo',
                'type' => 'logo',
                'label' => 'School Logo',
                'x' => 75,
                'y' => 20,
                'width' => 50,
                'height' => 50,
                'border_radius' => 8,
                'rotation' => 0,
            ],
            [
                'id' => 'school_name',
                'type' => 'text',
                'label' => 'School Name',
                'text' => '{School Name}',
                'x' => 20,
                'y' => 75,
                'font_size' => 14,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'center',
                'rotation' => 0,
            ],
            [
                'id' => 'student_photo',
                'type' => 'photo',
                'label' => 'Student Photo',
                'x' => 52,
                'y' => 100,
                'width' => 96,
                'height' => 116,
                'border_radius' => 10,
                'border_color' => $p['border'],
                'border_width' => 2,
                'rotation' => 0,
            ],
            [
                'id' => 'student_name',
                'type' => 'text',
                'label' => 'Student Name',
                'text' => '{First Name} {Last Name}',
                'x' => 20,
                'y' => 225,
                'font_size' => 14,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'center',
                'rotation' => 0,
            ],
            [
                'id' => 'grade_div',
                'type' => 'text',
                'label' => 'Grade & Division',
                'text' => 'Grade ({grade}) - Div ({division})',
                'x' => 20,
                'y' => 245,
                'font_size' => 11,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => $p['acc'],
                'align' => 'center',
                'rotation' => 0,
            ],
            [
                'id' => 'roll_dob',
                'type' => 'text',
                'label' => 'Roll & DOB',
                'text' => 'Roll: {Roll No}  •  DOB: {DOB}',
                'x' => 20,
                'y' => 265,
                'font_size' => 10,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'center',
                'rotation' => 0,
            ],
            [
                'id' => 'blood_contact',
                'type' => 'text',
                'label' => 'Blood Group & Contact',
                'text' => 'Blood: {Blood Group}  •  Ph: {Contact Number}',
                'x' => 20,
                'y' => 283,
                'font_size' => 10,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'center',
                'rotation' => 0,
            ],
            [
                'id' => 'qr_code',
                'type' => 'qr',
                'label' => 'QR Code',
                'x' => 75,
                'y' => 305,
                'width' => 50,
                'height' => 50,
                'rotation' => 0,
            ],
        ];
    }

    $templatesData[] = [
        'id' => 'master-tpl-' . sprintf("%03d", $i),
        'name' => $templateName,
        'orientation' => $orientation,
        'width_mm' => $isPortrait ? 54.00 : 85.60,
        'height_mm' => $isPortrait ? 85.60 : 54.00,
        'background_image' => $dbPath,
        'layout_config' => $layoutConfig,
        'is_active' => true,
    ];
}

echo "Generated 100 SVG background files in {$bgDir}\n";

// Now write TemplateSeeder.php
$seederContent = '<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = ' . var_export($templatesData, true) . ';

        foreach ($templates as $tpl) {
            Template::updateOrCreate(
                [\'id\' => $tpl[\'id\']],
                [
                    \'name\' => $tpl[\'name\'],
                    \'orientation\' => $tpl[\'orientation\'],
                    \'width_mm\' => $tpl[\'width_mm\'],
                    \'height_mm\' => $tpl[\'height_mm\'],
                    \'background_image\' => $tpl[\'background_image\'],
                    \'layout_config\' => $tpl[\'layout_config\'],
                    \'is_active\' => $tpl[\'is_active\'],
                ]
            );
        }
    }
}
';

file_put_contents(__DIR__ . '/TemplateSeeder.php', $seederContent);
echo "Successfully updated TemplateSeeder.php with 100 master templates!\n";
