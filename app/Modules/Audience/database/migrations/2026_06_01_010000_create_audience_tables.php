<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audience_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('email')->unique();
            $table->text('notes')->nullable();
            $table->boolean('accepts_email')->default(true);
            $table->string('unsubscribe_token', 64)->nullable()->unique();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audience_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('audience_contact_segment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_contact_id')->constrained('audience_contacts')->cascadeOnDelete();
            $table->foreignId('audience_segment_id')->constrained('audience_segments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['audience_contact_id', 'audience_segment_id'],
                'audience_contact_segment_unique',
            );
        });

        Schema::create('segment_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_segment_id')->constrained('audience_segments')->cascadeOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_messages');
        Schema::dropIfExists('audience_contact_segment');
        Schema::dropIfExists('audience_segments');
        Schema::dropIfExists('audience_contacts');
    }
};
