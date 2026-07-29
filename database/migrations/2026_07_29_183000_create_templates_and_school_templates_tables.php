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
        // 1. Create system master templates table
        if (!Schema::hasTable('templates')) {
            Schema::create('templates', function (Blueprint $table) {
                $table->string('id')->primary(); // e.g. 'premium-landscape'
                $table->string('name');
                $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');
                $table->decimal('width_mm', 5, 2)->default(85.60);
                $table->decimal('height_mm', 5, 2)->default(54.00);
                $table->string('background_image')->nullable();
                $table->string('background_back_image')->nullable();
                $table->json('layout_config');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create school customized templates table
        if (!Schema::hasTable('school_templates')) {
            Schema::create('school_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('template_id')->nullable();
                $table->foreign('template_id')->references('id')->on('templates')->nullOnDelete();
                $table->string('name');
                $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');
                $table->decimal('width_mm', 5, 2)->default(85.60);
                $table->decimal('height_mm', 5, 2)->default(54.00);
                $table->string('background_image')->nullable();
                $table->string('background_back_image')->nullable();
                $table->json('layout_config');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 3. Add school_template_id to grades table if not present
        if (!Schema::hasColumn('grades', 'school_template_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->foreignId('school_template_id')->nullable()->constrained('school_templates')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('grades', 'school_template_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropForeign(['school_template_id']);
                $table->dropColumn('school_template_id');
            });
        }

        Schema::dropIfExists('school_templates');
        Schema::dropIfExists('templates');
    }
};
