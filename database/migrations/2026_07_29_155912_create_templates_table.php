<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('view_path');
            $table->string('orientation');
            $table->string('category');
            $table->string('thumbnail_color');
            $table->timestamps();
        });

        // Insert initial seed data
        \Illuminate\Support\Facades\DB::table('templates')->insert([
            [
                'name' => 'Standard Student Badge (Portrait)',
                'view_path' => 'id-card-templates.standard-portrait',
                'orientation' => 'Portrait (85.6 x 54 mm)',
                'category' => 'student',
                'thumbnail_color' => 'from-indigo-600 to-blue-800',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Modern Student Badge (Landscape)',
                'view_path' => 'id-card-templates.modern-landscape',
                'orientation' => 'Landscape (54 x 85.6 mm)',
                'category' => 'student',
                'thumbnail_color' => 'from-purple-600 to-indigo-900',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Executive Staff / Teacher Pass',
                'view_path' => 'id-card-templates.executive-staff',
                'orientation' => 'Portrait (85.6 x 54 mm)',
                'category' => 'staff',
                'thumbnail_color' => 'from-amber-500 to-slate-900',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Visitor & Event Temporary Pass',
                'view_path' => 'id-card-templates.visitor-pass',
                'orientation' => 'Portrait (85.6 x 54 mm)',
                'category' => 'visitor',
                'thumbnail_color' => 'from-emerald-600 to-teal-900',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
