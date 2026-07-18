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
        Schema::create('school_user_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_user_role_id')->constrained('school_user_roles')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('school_invitation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_invitation_id')->constrained('school_invitations')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Migrate existing assignments
        $existingRoles = DB::table('school_user_roles')->whereNotNull('grade_id')->whereNotNull('division_id')->get();
        foreach ($existingRoles as $role) {
            // Avoid duplicate insert if migrating multiple times or data exists
            $exists = DB::table('school_user_role_assignments')
                ->where('school_user_role_id', $role->id)
                ->where('grade_id', $role->grade_id)
                ->where('division_id', $role->division_id)
                ->exists();

            if (!$exists) {
                DB::table('school_user_role_assignments')->insert([
                    'school_user_role_id' => $role->id,
                    'grade_id' => $role->grade_id,
                    'division_id' => $role->division_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $existingInvites = DB::table('school_invitations')->whereNotNull('grade_id')->whereNotNull('division_id')->get();
        foreach ($existingInvites as $invite) {
            $exists = DB::table('school_invitation_assignments')
                ->where('school_invitation_id', $invite->id)
                ->where('grade_id', $invite->grade_id)
                ->where('division_id', $invite->division_id)
                ->exists();

            if (!$exists) {
                DB::table('school_invitation_assignments')->insert([
                    'school_invitation_id' => $invite->id,
                    'grade_id' => $invite->grade_id,
                    'division_id' => $invite->division_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_user_role_assignments');
        Schema::dropIfExists('school_invitation_assignments');
    }
};
