<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_payment_sms_daily_budgets', function (Blueprint $table): void {
            $table->date('business_date')->primary();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_payment_sms_daily_budgets');
    }
};
