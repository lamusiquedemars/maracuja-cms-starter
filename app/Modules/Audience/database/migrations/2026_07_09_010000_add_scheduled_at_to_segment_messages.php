<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('segment_messages') || Schema::hasColumn('segment_messages', 'scheduled_at')) {
            return;
        }

        Schema::table('segment_messages', function (Blueprint $table): void {
            $table->timestamp('scheduled_at')->nullable()->after('recipients_count')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('segment_messages') || ! Schema::hasColumn('segment_messages', 'scheduled_at')) {
            return;
        }

        Schema::table('segment_messages', function (Blueprint $table): void {
            $table->dropColumn('scheduled_at');
        });
    }
};
