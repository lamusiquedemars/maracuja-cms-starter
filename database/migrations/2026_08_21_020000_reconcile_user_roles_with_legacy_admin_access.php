<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The first role migration used an admin default. It could therefore
        // promote newly created legacy accounts, or leave a former admin with
        // an incoherent role. The boolean remains the source of truth for
        // accounts created before roles existed.
        DB::table('users')
            ->where('is_admin', true)
            ->where('role', '!=', 'admin')
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where('is_admin', false)
            ->where('role', 'admin')
            ->update(['role' => 'viewer']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('viewer')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('admin')->change();
        });
    }
};
