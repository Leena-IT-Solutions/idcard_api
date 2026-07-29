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
                'name' => 'Premium Landscape Student ID',
                'view_path' => 'id-card-templates.premium-landscape',
                'orientation' => 'Landscape (54 x 85.6 mm)',
                'category' => 'student',
                'thumbnail_color' => 'from-blue-900 to-indigo-950',
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
