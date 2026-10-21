<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('segment_message_deliveries')) {
            return;
        }

        Schema::table('segment_message_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('segment_message_deliveries', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('status');
            }

            if (! Schema::hasColumn('segment_message_deliveries', 'attempted_at')) {
                $table->timestamp('attempted_at')->nullable()->after('attempts');
            }
        });

        Schema::table('segment_message_deliveries', function (Blueprint $table): void {
            $table->unique(['segment_message_id', 'audience_contact_id'], 'segment_delivery_contact_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('segment_message_deliveries')) {
            return;
        }

        Schema::table('segment_message_deliveries', function (Blueprint $table): void {
            $table->dropUnique('segment_delivery_contact_unique');

            if (Schema::hasColumn('segment_message_deliveries', 'attempted_at')) {
                $table->dropColumn('attempted_at');
            }

            if (Schema::hasColumn('segment_message_deliveries', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
