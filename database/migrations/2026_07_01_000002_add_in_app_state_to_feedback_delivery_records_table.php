<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_delivery_records', function (Blueprint $table): void {
            $table->string('in_app_state')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamp('dismissed_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('feedback_delivery_records', function (Blueprint $table): void {
            $table->dropColumn([
                'in_app_state',
                'read_at',
                'archived_at',
                'dismissed_at',
            ]);
        });
    }
};
