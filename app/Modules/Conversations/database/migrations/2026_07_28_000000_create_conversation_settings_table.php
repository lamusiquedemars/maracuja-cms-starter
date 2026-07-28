<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('widget_button_label')->default('Nous écrire');
            $table->string('widget_title')->default('Comment pouvons-nous vous aider ?');
            $table->text('privacy_notice')->nullable();
            $table->string('assistant_language', 20)->default('fr');
            $table->string('assistant_tone')->default('clair, calme et concis');
            $table->text('organization_summary')->nullable();
            $table->json('qualification_fields')->nullable();
            $table->text('qualification_guidance')->nullable();
            $table->text('urgency_guidance')->nullable();
            $table->text('sensitive_data_guidance')->nullable();
            $table->json('routing_triggers')->nullable();
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_number', 40)->nullable();
            $table->text('whatsapp_message_template')->nullable();
            $table->text('whatsapp_contact_message_template')->nullable();
            $table->boolean('callback_enabled')->default(false);
            $table->json('callback_channels')->nullable();
            $table->string('notification_email')->nullable();
            $table->string('expected_response_time')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_settings');
    }
};
