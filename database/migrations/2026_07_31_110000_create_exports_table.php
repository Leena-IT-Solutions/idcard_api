<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // excel_photo_zip | png_zip | imposition_pdf
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('params')->nullable(); // campaign_id, student_ids, filters, page size, bleed, margin, etc.
            $table->unsignedInteger('total_items')->nullable();
            $table->unsignedInteger('processed_items')->default(0);
            $table->string('file_path')->nullable(); // path on local private disk
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
