<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultConfig = [
            [
                'id' => 'school_logo',
                'type' => 'logo',
                'label' => 'School Logo',
                'x' => 24,
                "y" => 20,
                'width' => 45,
                'height' => 45,
                'border_radius' => 8,
                'rotation' => 0,
            ],
            [
                'id' => 'school_name',
                'type' => 'text',
                'label' => 'School Name',
                'text' => '{School Name}',
                'x' => 80,
                'y' => 22,
                'font_size' => 16,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => '#ffffff',
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'school_code',
                'type' => 'text',
                'label' => 'School Code',
                'text' => 'CODE: {School Code}',
                'x' => 80,
                'y' => 44,
                'font_size' => 10,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => '#818cf8',
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
                'border_radius' => 12,
                'border_color' => '#818cf8',
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
                'color' => '#ffffff',
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'grade_div',
                'type' => 'text',
                'label' => 'Grade & Division',
                'text' => 'Grade ({grade}) Div ({division})',
                'x' => 130,
                'y' => 106,
                'font_size' => 12,
                'font_weight' => 'semibold',
                'font_family' => 'Inter',
                'color' => '#fbbf24',
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
                'color' => '#cbd5e1',
                'align' => 'left',
                'rotation' => 0,
            ],
            [
                'id' => 'blood_contact',
                'type' => 'text',
                'label' => 'Blood Group & Contact',
                'text' => 'Blood Group: {Blood Group}  •  Ph: {Contact Number}',
                'x' => 130,
                'y' => 148,
                'font_size' => 11,
                'font_weight' => 'normal',
                'font_family' => 'Inter',
                'color' => '#cbd5e1',
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
                'color' => '#94a3b8',
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

        Template::updateOrCreate(
            ['id' => 'premium-landscape'],
            [
                'name' => 'Premium Landscape Student ID',
                'orientation' => 'landscape',
                'width_mm' => 85.60,
                'height_mm' => 54.00,
                'background_image' => null,
                'layout_config' => $defaultConfig,
                'is_active' => true,
            ]
        );
    }
}
