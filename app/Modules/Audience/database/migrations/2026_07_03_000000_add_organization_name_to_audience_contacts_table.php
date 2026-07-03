<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audience_contacts') || Schema::hasColumn('audience_contacts', 'organization_name')) {
            return;
        }

        Schema::table('audience_contacts', function (Blueprint $table): void {
            $table->string('organization_name')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audience_contacts') || ! Schema::hasColumn('audience_contacts', 'organization_name')) {
            return;
        }

        Schema::table('audience_contacts', function (Blueprint $table): void {
            $table->dropColumn('organization_name');
        });
    }
};
