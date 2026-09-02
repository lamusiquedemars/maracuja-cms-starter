<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquisition_deliveries', function (Blueprint $table): void {
            // The payload is encrypted by the model before it reaches the database.
            $table->longText('payload')->change();
        });
    }

    public function down(): void
    {
        Schema::table('acquisition_deliveries', function (Blueprint $table): void {
            $table->json('payload')->change();
        });
    }
};
