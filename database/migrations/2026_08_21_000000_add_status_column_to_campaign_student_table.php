<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaign_student', function (Blueprint $table) {
            $table->enum('status', [
                'drafting',
                'verified',
                'sent_for_printing',
                'printed',
                'distributed'
            ])->default('drafting')->after('serial_number');
            $table->timestamp('status_updated_at')->nullable()->after('status');
            $table->foreignId('status_updated_by')->nullable()->after('status_updated_at')->constrained('users')->nullOnDelete();
        });

        // Migrate existing records: set status='verified' if verified_at is not null, otherwise 'drafting'
        DB::table('campaign_student')
            ->whereNotNull('verified_at')
            ->update([
                'status' => 'verified',
                'status_updated_at' => DB::raw('verified_at'),
                'status_updated_by' => DB::raw('verified_by'),
            ]);

        DB::table('campaign_student')
            ->whereNull('verified_at')
            ->update([
                'status' => 'drafting',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_student', function (Blueprint $table) {
            $table->dropForeign(['status_updated_by']);
            $table->dropColumn(['status', 'status_updated_at', 'status_updated_by']);
        });
    }
};
