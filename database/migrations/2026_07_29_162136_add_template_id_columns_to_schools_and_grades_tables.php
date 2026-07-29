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
        // Drop foreign keys if they exist
        Schema::table('schools', function (Blueprint $table) {
            try {
                $table->dropForeign(['template_id']);
            } catch (\Exception $e) {}
        });

        Schema::table('grades', function (Blueprint $table) {
            try {
                $table->dropForeign(['template_id']);
            } catch (\Exception $e) {}
        });

        // Drop templates table
        Schema::dropIfExists('templates');

        // Ensure template_id columns are nullable strings
        if (!Schema::hasColumn('schools', 'template_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('template_id')->nullable();
            });
        } else {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('template_id')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('grades', 'template_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->string('template_id')->nullable();
            });
        } else {
            Schema::table('grades', function (Blueprint $table) {
                $table->string('template_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('schools', 'template_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('template_id');
            });
        }

        if (Schema::hasColumn('grades', 'template_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropColumn('template_id');
            });
        }
    }
};
