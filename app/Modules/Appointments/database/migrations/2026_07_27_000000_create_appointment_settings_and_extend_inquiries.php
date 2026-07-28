<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider', 20)->default('fake');
            $table->string('mode', 30)->default('after_review');
            $table->string('booking_url', 2048)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();
        });

        if (Schema::hasTable('inquiries')) {
            Schema::table('inquiries', function (Blueprint $table): void {
                $table->string('appointment_status', 30)->default('not_requested')->after('status');
                $table->timestamp('booking_opened_at')->nullable()->after('appointment_status');
                $table->timestamp('scheduled_start_at')->nullable()->after('booking_opened_at');
                $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_start_at');
                $table->string('appointment_timezone', 64)->nullable()->after('scheduled_end_at');
                $table->string('appointment_external_reference')->nullable()->after('appointment_timezone');
                $table->index(['appointment_status', 'scheduled_start_at'], 'inquiries_appointment_follow_up_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inquiries') && Schema::hasColumn('inquiries', 'appointment_status')) {
            Schema::table('inquiries', function (Blueprint $table): void {
                $table->dropIndex('inquiries_appointment_follow_up_idx');
                $table->dropColumn([
                    'appointment_status',
                    'booking_opened_at',
                    'scheduled_start_at',
                    'scheduled_end_at',
                    'appointment_timezone',
                    'appointment_external_reference',
                ]);
            });
        }

        Schema::dropIfExists('appointment_settings');
    }
};
