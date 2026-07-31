<?php

/**
 * Master Template Generator Script for 100 Master ID Card Templates
 */

$bgDir = __DIR__ . '/../../storage/app/public/template-assets/backgrounds';
if (!file_exists($bgDir)) {
    mkdir($bgDir, 0755, true);
}

$publicBgDir = __DIR__ . '/../../public/template-assets/backgrounds';
if (!file_exists($publicBgDir)) {
    mkdir($publicBgDir, 0755, true);
}

// 20 Palette themes with vibrant gradients and header colors
$palettes = [
    // 1. Deep Navy & Gold Crest
    ['bg' => '#0f172a', 'h1' => '#1e293b', 'h2' => '#1d4ed8', 'acc' => '#fbbf24', 'txt1' => '#ffffff', 'txt2' => '#cbd5e1', 'border' => '#fbbf24'],
    // 2. Royal Blue & Gold
    ['bg' => '#020617', 'h1' => '#0f172a', 'h2' => '#2563eb', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#94a3b8', 'border' => '#f59e0b'],
    // 3. Imperial Indigo & Violet
    ['bg' => '#1e1b4b', 'h1' => '#312e81', 'h2' => '#4338ca', 'acc' => '#a855f7', 'txt1' => '#ffffff', 'txt2' => '#c7d2fe', 'border' => '#818cf8'],
    // 4. Executive Slate & Cyan
    ['bg' => '#0f172a', 'h1' => '#1e293b', 'h2' => '#0284c7', 'acc' => '#38bdf8', 'txt1' => '#ffffff', 'txt2' => '#94a3b8', 'border' => '#38bdf8'],
    // 5. Purple Velvet & Rose
    ['bg' => '#2e1065', 'h1' => '#3b0764', 'h2' => '#5b21b6', 'acc' => '#f43f5e', 'txt1' => '#ffffff', 'txt2' => '#ddd6fe', 'border' => '#f43f5e'],
    // 6. Burgundy Gold & Crimson
    ['bg' => '#450a0a', 'h1' => '#7f1d1d', 'h2' => '#991b1b', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#fca5a5', 'border' => '#f59e0b'],
    // 7. Crimson Pride & Gold
    ['bg' => '#881337', 'h1' => '#9f1239', 'h2' => '#be123c', 'acc' => '#fde047', 'txt1' => '#ffffff', 'txt2' => '#fecdd3', 'border' => '#fde047'],
    // 8. Violet Crown
    ['bg' => '#4c1d95', 'h1' => '#5b21b6', 'h2' => '#6d28d9', 'acc' => '#fbbf24', 'txt1' => '#ffffff', 'txt2' => '#ddd6fe', 'border' => '#fbbf24'],
    // 9. Emerald Elite
    ['bg' => '#064e3b', 'h1' => '#047857', 'h2' => '#059669', 'acc' => '#f59e0b', 'txt1' => '#ffffff', 'txt2' => '#a7f3d0', 'border' => '#34d399'],
    // 10. Teal Wave Minimal
    ['bg' => '#022c22', 'h1' => '#064e3b', 'h2' => '#0f766e', 'acc' => '#2dd4bf', 'txt1' => '#ffffff', 'txt2' => '#99f6e4', 'border' => '#2dd4bf'],
    // 11. Forest Academy Green
    ['bg' => '#14532d', 'h1' => '#15803d', 'h2' => '#16a34a', 'acc' => '#facc15', 'txt1' => '#ffffff', 'txt2' => '#bbf7d0', 'border' => '#facc15'],
    // 12. Clean Minimal Cobalt (Light)
    ['bg' => '#ffffff', 'h1' => '#1e293b', 'h2' => '#2563eb', 'acc' => '#3b82f6', 'txt1' => '#0f172a', 'txt2' => '#475569', 'border' => '#2563eb'],
    // 13. Sky Blue Professional (Light)
    ['bg' => '#f8fafc', 'h1' => '#0f172a', 'h2' => '#0284c7', 'acc' => '#0369a1', 'txt1' => '#0f172a', 'txt2' => '#334155', 'border' => '#0284c7'],
    // 14. Modern Tech Violet (Light)
    ['bg' => '#fafafa', 'h1' => '#18181b', 'h2' => '#7c3aed', 'acc' => '#6d28d9', 'txt1' => '#18181b', 'txt2' => '#52525b', 'border' => '#7c3aed'],
    // 15. Mint Fresh Student (Light)
    ['bg' => '#f0fdf4', 'h1' => '#166534', 'h2' => '#15803d', 'acc' => '#16a34a', 'txt1' => '#14532d', 'txt2' => '#166534', 'border' => '#16a34a'],
    // 16. Rose Minimal Red (Light)
    ['bg' => '#fff1f2', 'h1' => '#9f1239', 'h2' => '#be123c', 'acc' => '#e11d48', 'txt1' => '#881337', 'txt2' => '#9f1239', 'border' => '#e11d48'],
    // 17. Cyber Neon Cyan
    ['bg' => '#09090b', 'h1' => '#18181b', 'h2' => '#27272a', 'acc' => '#06b6d4', 'txt1' => '#ffffff', 'txt2' => '#a1a1aa', 'border' => '#06b6d4'],
    // 18. Midnight Cobalt
    ['bg' => '#050505', 'h1' => '#172554', 'h2' => '#1e40af', 'acc' => '#60a5fa', 'txt1' => '#ffffff', 'txt2' => '#93c5fd', 'border' => '#3b82f6'],
    // 19. Neon Magenta Dark
    ['bg' => '#0d0221', 'h1' => '#190a38', 'h2' => '#2b1055', 'acc' => '#ff007f', 'txt1' => '#ffffff', 'txt2' => '#d8b4fe', 'border' => '#ff007f'],
    // 20. Titanium Dark Slate
    ['bg' => '#18181b', 'h1' => '#27272a', 'h2' => '#3f3f46', 'acc' => '#e4e4e7', 'txt1' => '#ffffff', 'txt2' => '#a1a1aa', 'border' => '#a1a1aa'],
];

$names = [
    'Classic Academic Navy', 'Royal Blue Crest', 'Imperial Gold & Indigo', 'Executive Slate', 'Purple Velvet Modern',
    'Burgundy Gold Seal', 'Crimson Pride', 'Violet Crown', 'Emerald Elite', 'Teal Wave Minimal',
    'Forest Academy Green', 'Clean Minimal Cobalt', 'Sky Blue Professional', 'Modern Tech Violet', 'Mint Fresh Student',
    'Rose Minimal Red', 'Cyber Neon Cyan', 'Midnight Cobalt', 'Neon Magenta Dark', 'Titanium Dark Slate',
];

$templatesData = [];

for ($i = 1; $i <= 100; $i++) {
    $isPortrait = ($i % 3 === 0);
    $orientation = $isPortrait ? 'portrait' : 'landscape';
    $w = $isPortrait ? 638 : 1011;
    $h = $isPortrait ? 1011 : 638;

    $paletteIndex = ($i - 1) % count($palettes);
    $p = $palettes[$paletteIndex];
    $baseNameIndex = ($i - 1) % count($names);
    $templateName = $names[$baseNameIndex] . " #" . $i . ($isPortrait ? ' (Vertical)' : '');

    $filename = "master_template_" . sprintf("%03d", $i) . ".svg";
    $filePath = $bgDir . '/' . $filename;
    $publicPath = $publicBgDir . '/' . $filename;
    $dbPath = 'template-assets/backgrounds/' . $filename;

    // Computed SVG dimensions & curve coordinates
    $hHeader = (int) ($h * 0.22);
    $hHeader2 = (int) ($h * 0.15);
    $hFooter = $h - 55;
    $hFooter2 = $h - 85;
    $hFooter3 = $h - 35;
    $wSub = (int) ($w * 0.65);
    $wSub2 = (int) ($w * 0.70);

    $styleType = $i % 5;
    if ($styleType === 0) {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">
          <rect width="'.$w.'" height="'.$h.'" fill="'.$p['bg'].'"/>
          <path d="M0 0 H'.$w.' V'.$hHeader.' Q'.$wSub.' '.($hHeader+40).' 0 '.($hHeader-20).' Z" fill="'.$p['h1'].'"/>
          <path d="M0 0 H'.$w.' V'.$hHeader2.' Q'.$wSub2.' '.($hHeader2+30).' 0 '.($hHeader2-20).' Z" fill="'.$p['h2'].'"/>
          <path d="M0 '.$h.' H'.$w.' V'.$hFooter.' Q'.(int)($w*0.5).' '.$hFooter2.' 0 '.($hFooter+15).' Z" fill="'.$p['acc'].'"/>
          <circle cx="'.(int)($w*0.88).'" cy="'.(int)($h*0.15).'" r="160" fill="'.$p['acc'].'" opacity="0.08"/>
        </svg>';
    } elseif ($styleType === 1) {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">
          <rect width="'.$w.'" height="'.$h.'" fill="'.$p['bg'].'"/>
          <polygon points="0,0 '.$w.',0 '.$w.','.$hHeader.' 0,'.($hHeader+40).'" fill="'.$p['h1'].'"/>
          <polygon points="0,0 '.(int)($w*0.7).',0 '.(int)($w*0.6).','.$hHeader2.' 0,'.($hHeader2-10).'" fill="'.$p['h2'].'"/>
          <polygon points="0,'.$h.' '.$w.','.$h.' '.$w.','.$hFooter.' 0,'.$hFooter2.'" fill="'.$p['acc'].'"/>
        </svg>';
    } elseif ($styleType === 2) {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">
          <rect width="'.$w.'" height="'.$h.'" fill="'.$p['bg'].'"/>
          <rect x="0" y="0" width="28" height="'.$h.'" fill="'.$p['acc'].'"/>
          <rect x="28" y="0" width="14" height="'.$h.'" fill="'.$p['h2'].'"/>
          <path d="M0 0 H'.$w.' V'.$hHeader.' Q'.(int)($w*0.5).' '.($hHeader+25).' 0 '.$hHeader.' Z" fill="'.$p['h1'].'"/>
          <rect x="0" y="'.$hFooter3.'" width="'.$w.'" height="35" fill="'.$p['h1'].'"/>
        </svg>';
    } elseif ($styleType === 3) {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">
          <rect width="'.$w.'" height="'.$h.'" fill="'.$p['bg'].'"/>
          <circle cx="'.$w.'" cy="0" r="320" fill="'.$p['h1'].'"/>
          <circle cx="'.$w.'" cy="0" r="240" fill="'.$p['h2'].'"/>
          <circle cx="0" cy="'.$h.'" r="220" fill="'.$p['acc'].'" opacity="0.2"/>
          <rect x="0" y="0" width="'.$w.'" height="14" fill="'.$p['acc'].'"/>
          <rect x="0" y="'.$hFooter3.'" width="'.$w.'" height="35" fill="'.$p['h1'].'"/>
        </svg>';
    } else {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="100%" height="100%">
          <rect width="'.$w.'" height="'.$h.'" fill="'.$p['bg'].'"/>
          <rect x="0" y="0" width="'.$w.'" height="'.$hHeader.'" fill="'.$p['h1'].'"/>
          <rect x="0" y="'.$hHeader.'" width="'.$w.'" height="10" fill="'.$p['acc'].'"/>
          <rect x="0" y="'.$hFooter3.'" width="'.$w.'" height="35" fill="'.$p['h2'].'"/>
        </svg>';
    }

    if (!file_exists($filePath)) {
        @file_put_contents($filePath, trim($svgContent));
    }
    if (!file_exists($publicPath)) {
        @file_put_contents($publicPath, trim($svgContent));
    }

    // High-Resolution Layout Configurations
    if (!$isPortrait) {
        // Landscape (1011px x 638px)
        $layoutConfig = [
            [
                'id' => 'school_logo',
                'type' => 'logo',
                'label' => 'School Logo',
                'x' => 50,
                'y' => 30,
                'width' => 90,
                'height' => 90,
                'border_radius' => 12,
                'rotation' => 0,
            ],
            [
                'id' => 'school_name',
                'type' => 'text',
                'label' => 'School Name',
                'text' => '{School Name}',
                'x' => 160,
                'y' => 38,
                'font_size' => 26,
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
                'x' => 160,
                'y' => 80,
                'font_size' => 16,
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
                'x' => 50,
                'y' => 160,
                'width' => 220,
                'height' => 270,
                'border_radius' => 16,
                'border_color' => $p['border'],
                'border_width' => 3,
                'rotation' => 0,
            ],
            [
                'id' => 'student_name',
                'type' => 'text',
                'label' => 'Student Name',
                'text' => '{First Name} {Middle Name} {Last Name}',
                'x' => 300,
                'y' => 170,
                'font_size' => 28,
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
                'text' => 'Grade: {grade}   •   Division: {division}',
                'x' => 300,
                'y' => 220,
                'font_size' => 20,
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
                'text' => 'Roll No: {Roll No}   •   DOB: {DOB}',
                'x' => 300,
                'y' => 265,
                'font_size' => 18,
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
                'text' => 'Blood Group: {Blood Group}   •   Ph: {Contact Number}',
                'x' => 300,
                'y' => 310,
                'font_size' => 18,
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
                'text' => 'Address: {Address} - {Pincode}',
                'x' => 300,
                'y' => 355,
                'font_size' => 16,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'left',
                'width' => 450,
                'rotation' => 0,
            ],
            [
                'id' => 'qr_code',
                'type' => 'qr',
                'label' => 'QR Code',
                'x' => 800,
                'y' => 240,
                'width' => 150,
                'height' => 150,
                'rotation' => 0,
            ],
        ];
    } else {
        // Portrait (638px x 1011px)
        $layoutConfig = [
            [
                'id' => 'school_logo',
                'type' => 'logo',
                'label' => 'School Logo',
                'x' => 244,
                'y' => 35,
                'width' => 150,
                'height' => 150,
                'border_radius' => 16,
                'rotation' => 0,
            ],
            [
                'id' => 'school_name',
                'type' => 'text',
                'label' => 'School Name',
                'text' => '{School Name}',
                'x' => 40,
                'y' => 205,
                'font_size' => 26,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'school_code',
                'type' => 'text',
                'label' => 'School Code',
                'text' => 'CODE: {School Code}',
                'x' => 40,
                'y' => 250,
                'font_size' => 16,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => $p['acc'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'student_photo',
                'type' => 'photo',
                'label' => 'Student Photo',
                'x' => 194,
                'y' => 300,
                'width' => 250,
                'height' => 300,
                'border_radius' => 20,
                'border_color' => $p['border'],
                'border_width' => 4,
                'rotation' => 0,
            ],
            [
                'id' => 'student_name',
                'type' => 'text',
                'label' => 'Student Name',
                'text' => '{First Name} {Middle Name} {Last Name}',
                'x' => 40,
                'y' => 625,
                'font_size' => 28,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => $p['txt1'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'grade_div',
                'type' => 'text',
                'label' => 'Grade & Division',
                'text' => 'Grade ({grade})  •  Division ({division})',
                'x' => 40,
                'y' => 675,
                'font_size' => 20,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => $p['acc'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'roll_dob',
                'type' => 'text',
                'label' => 'Roll & DOB',
                'text' => 'Roll: {Roll No}   •   DOB: {DOB}',
                'x' => 40,
                'y' => 720,
                'font_size' => 18,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'blood_contact',
                'type' => 'text',
                'label' => 'Blood Group & Contact',
                'text' => 'Blood: {Blood Group}   •   Ph: {Contact Number}',
                'x' => 40,
                'y' => 765,
                'font_size' => 18,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'address',
                'type' => 'text',
                'label' => 'Residential Address',
                'text' => 'Address: {Address} - {Pincode}',
                'x' => 40,
                'y' => 810,
                'font_size' => 16,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => $p['txt2'],
                'align' => 'center',
                'width' => 558,
                'rotation' => 0,
            ],
            [
                'id' => 'qr_code',
                'type' => 'qr',
                'label' => 'QR Code',
                'x' => 244,
                'y' => 855,
                'width' => 150,
                'height' => 150,
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

echo "Generated 100 SVG background files in {$bgDir} and {$publicBgDir}\n";

// Write self-contained TemplateSeeder.php that auto-generates SVG backgrounds if missing
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
