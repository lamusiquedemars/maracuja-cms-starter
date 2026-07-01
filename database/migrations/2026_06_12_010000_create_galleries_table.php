<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::table('gallery_images', function (Blueprint $table) {
            $table->foreignId('gallery_id')
                ->nullable()
                ->after('id')
                ->constrained('galleries')
                ->nullOnDelete();
        });

        $galleryId = DB::table('galleries')->insertGetId([
            'title' => 'Galerie principale',
            'slug' => 'home',
            'intro' => null,
            'position' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gallery_images')->whereNull('gallery_id')->update([
            'gallery_id' => $galleryId,
        ]);
    }

    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gallery_id');
        });

        Schema::dropIfExists('galleries');
    }
};
