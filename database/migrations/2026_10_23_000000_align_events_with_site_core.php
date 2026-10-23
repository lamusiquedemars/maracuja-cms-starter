<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'image_media_id')) {
            Schema::table('events', function (Blueprint $table): void {
                $table->foreignId('image_media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('participants')) Schema::create('participants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable();
            $table->text('bio')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (! Schema::hasTable('event_participants')) Schema::create('event_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['event_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('participants');
    }
};
