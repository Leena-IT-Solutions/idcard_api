<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_student', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('serial_number');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_student', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified_at', 'verified_by']);
        });
    }
};
