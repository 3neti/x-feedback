<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_delivery_records', function (Blueprint $table): void {
            $table->id();
            $table->string('delivery_id')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('intent_key')->index();
            $table->string('channel')->index();
            $table->string('recipient_type')->nullable()->index();
            $table->string('recipient_id')->nullable()->index();
            $table->json('recipient');
            $table->string('status')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('max_attempts')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('provider_status')->nullable()->index();
            $table->json('provider_response')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->string('causation_id')->nullable()->index();
            $table->timestamp('last_attempted_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_delivery_records');
    }
};
