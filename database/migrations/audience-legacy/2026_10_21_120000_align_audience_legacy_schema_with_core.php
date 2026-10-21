<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conserve le schéma des installations historiques pendant qu'elles
     * consomment le package Audience versionné du Site Core.
     */
    public function up(): void
    {
        Schema::table('audience_contacts', function (Blueprint $table): void {
            if (! Schema::hasColumn('audience_contacts', 'external_identity')) {
                $table->string('external_identity')->nullable()->index();
            }

            if (! Schema::hasColumn('audience_contacts', 'consent_source')) {
                $table->string('consent_source')->nullable();
            }

            if (! Schema::hasColumn('audience_contacts', 'consented_at')) {
                $table->timestamp('consented_at')->nullable();
            }

            if (! Schema::hasColumn('audience_contacts', 'provider_synced_at')) {
                $table->timestamp('provider_synced_at')->nullable();
                $table->string('provider_sync_status')->nullable()->index();
                $table->text('provider_sync_error')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Migration de compatibilité volontairement sans suppression de données.
    }
};
