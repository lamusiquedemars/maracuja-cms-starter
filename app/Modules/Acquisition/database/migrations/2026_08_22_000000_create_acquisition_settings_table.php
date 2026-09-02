<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('gtm_container_id', 32)->nullable();
            $table->boolean('consent_enabled')->default(true);
            $table->string('consent_mode', 20)->default('basic');
            $table->string('privacy_policy_url', 2048)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->char('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_settings');
    }
};
