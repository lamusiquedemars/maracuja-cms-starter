<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'body_blocks')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('body_blocks');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'body_blocks')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->json('body_blocks')->nullable()->after('hero_image_path');
        });
    }
};
