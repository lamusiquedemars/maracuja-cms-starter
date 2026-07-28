<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->timestamp('consent_at')->nullable()->after('message');
            $table->string('source', 40)->nullable()->after('consent_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn(['consent_at', 'source']);
        });
    }
};
