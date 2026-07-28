<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_settings', function (Blueprint $table): void {
            $table->text('welcome_message')->nullable()->after('widget_title');
            $table->unsignedSmallInteger('max_visitor_messages')->default(12)->after('expected_response_time');
            $table->unsignedSmallInteger('warning_at_message')->default(10)->after('max_visitor_messages');
            $table->text('interaction_limit_message')->nullable()->after('warning_at_message');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->string('handover_reason', 40)->nullable()->index()->after('human_handover_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropIndex(['handover_reason']);
            $table->dropColumn('handover_reason');
        });

        Schema::table('conversation_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'welcome_message',
                'max_visitor_messages',
                'warning_at_message',
                'interaction_limit_message',
            ]);
        });
    }
};
