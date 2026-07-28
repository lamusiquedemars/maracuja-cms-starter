<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_reference', 16)->unique();
            $table->string('session_token_hash', 64)->nullable()->index();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 24)->default('website')->index();
            $table->string('status', 32)->default('new')->index();
            $table->string('locale', 16)->nullable();
            $table->text('summary')->nullable();
            $table->string('topic')->nullable()->index();
            $table->string('urgency', 24)->default('unknown')->index();
            $table->json('qualification')->nullable();
            $table->string('entry_url', 2048)->nullable();
            $table->boolean('ai_enabled')->default(true);
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('human_handover_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });

        Schema::create('conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_type', 24)->index();
            $table->text('content');
            $table->string('channel', 24)->default('website')->index();
            $table->string('visibility', 16)->default('public')->index();
            $table->string('delivery_status', 24)->default('sent')->index();
            $table->string('external_id')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['conversation_id', 'visibility', 'sent_at'], 'conversation_public_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
