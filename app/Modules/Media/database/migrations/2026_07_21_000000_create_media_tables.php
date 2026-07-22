<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('disk', 50)->default('public');
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('display_name')->index();
            $table->string('mime_type', 100)->index();
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->string('credit')->nullable();
            $table->char('checksum', 64)->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('usable_type', 100);
            $table->unsignedBigInteger('usable_id');
            $table->string('field', 100);
            $table->string('context', 100)->default('');
            $table->timestamps();

            $table->index(['usable_type', 'usable_id']);
            $table->unique(
                ['media_asset_id', 'usable_type', 'usable_id', 'field', 'context'],
                'media_usages_unique_reference'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_assets');
    }
};
