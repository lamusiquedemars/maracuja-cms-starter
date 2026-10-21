<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segment_message_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_message_id')->constrained('segment_messages')->cascadeOnDelete();
            $table->foreignId('audience_contact_id')->nullable()->constrained('audience_contacts')->nullOnDelete();
            $table->string('email');
            $table->string('status')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_message_deliveries');
    }
};
