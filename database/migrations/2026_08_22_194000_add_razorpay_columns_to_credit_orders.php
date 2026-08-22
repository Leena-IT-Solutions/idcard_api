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
        Schema::table('credit_orders', function (Blueprint $table) {
            $table->string('razorpay_order_id')->nullable()->after('payment_reference')->index();
            $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id')->index();
            $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_orders', function (Blueprint $table) {
            $table->dropColumn(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature']);
        });
    }
};
