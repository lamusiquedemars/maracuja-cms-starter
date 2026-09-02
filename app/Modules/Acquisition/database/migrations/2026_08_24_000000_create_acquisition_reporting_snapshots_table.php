<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_reporting_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('site_reference')->unique();
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_reporting_snapshots');
    }
};
