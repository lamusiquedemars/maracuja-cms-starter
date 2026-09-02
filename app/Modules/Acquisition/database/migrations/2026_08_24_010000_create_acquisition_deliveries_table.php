<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            // Laravel's encrypted:array cast stores an encrypted string, not JSON.
            $table->longText('payload');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_deliveries');
    }
};
