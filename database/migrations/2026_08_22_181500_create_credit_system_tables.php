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
        // 1. Credit Plans (Pricing Slabs & Volume Bonuses)
        Schema::create('credit_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('price_per_credit', 8, 2);
            $table->unsignedInteger('bonus_percentage')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('badge_text')->nullable();
            $table->string('badge_color')->nullable();
            $table->timestamps();
        });

        // 2. Credit Orders (Recharge & Purchase Requests / Grants)
        Schema::create('credit_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('ordered_credits');
            $table->unsignedInteger('bonus_credits')->default(0);
            $table->unsignedInteger('total_credited');
            $table->decimal('price_per_credit', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->default('admin_grant'); // admin_grant, bank_transfer, razorpay, phonepe
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, refunded
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Credit Transactions (Complete Wallet Ledger)
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // recharge, export_deduction, admin_adjustment, welcome_bonus, refund
            $table->integer('credits'); // Signed: positive for addition, negative for deduction
            $table->unsignedInteger('balance_after');
            $table->nullableMorphs('reference');
            $table->string('description');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('credit_orders');
        Schema::dropIfExists('credit_plans');
    }
};
