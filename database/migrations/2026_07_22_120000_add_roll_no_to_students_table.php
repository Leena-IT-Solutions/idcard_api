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
        if (!Schema::hasColumn('students', 'roll_no')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('roll_no')->nullable()->after('last_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('students', 'roll_no')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('roll_no');
            });
        }
    }
};
