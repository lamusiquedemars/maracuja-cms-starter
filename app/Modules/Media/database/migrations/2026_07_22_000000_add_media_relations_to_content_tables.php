<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->foreignId('hero_media_id')->nullable()->after('hero_image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('news_posts', function (Blueprint $table): void {
            $table->foreignId('image_media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->foreignId('image_media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->foreignId('image_media_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('gallery_images', function (Blueprint $table): void {
            $table->foreignId('media_asset_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->foreignId('logo_media_id')->nullable()->after('logo_path')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('favicon_media_id')->nullable()->after('favicon_path')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('default_og_media_id')->nullable()->after('default_og_image_path')->constrained('media_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_og_media_id');
            $table->dropConstrainedForeignId('favicon_media_id');
            $table->dropConstrainedForeignId('logo_media_id');
        });

        Schema::table('gallery_images', fn (Blueprint $table) => $table->dropConstrainedForeignId('media_asset_id'));
        Schema::table('events', fn (Blueprint $table) => $table->dropConstrainedForeignId('image_media_id'));
        Schema::table('articles', fn (Blueprint $table) => $table->dropConstrainedForeignId('image_media_id'));
        Schema::table('news_posts', fn (Blueprint $table) => $table->dropConstrainedForeignId('image_media_id'));
        Schema::table('pages', fn (Blueprint $table) => $table->dropConstrainedForeignId('hero_media_id'));
    }
};
