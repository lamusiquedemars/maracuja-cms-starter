<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Conversations\Services\ConversationInstructionsBuilder;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConversationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_starter_creates_neutral_conversation_defaults(): void
    {
        $settings = ConversationSetting::current();

        $this->assertFalse($settings->is_enabled);
        $this->assertSame('fr', $settings->assistant_language);
        $this->assertSame(['request_topic', 'location'], $settings->qualification_fields);
        $this->assertFalse($settings->whatsapp_enabled);
        $this->assertFalse($settings->callback_enabled);
        $this->assertDatabaseCount('conversation_settings', 1);
    }

    public function test_the_prompt_combines_guardrails_with_site_configuration(): void
    {
        $site = SiteSetting::current();
        $site->update(['site_name' => 'Example Organization']);

        $settings = ConversationSetting::current();
        $settings->update([
            'assistant_language' => 'en',
            'organization_summary' => 'A general service organization.',
            'qualification_fields' => ['request_topic', 'deadline'],
            'urgency_guidance' => 'A deadline within 24 hours.',
            'whatsapp_enabled' => true,
            'whatsapp_number' => '+33 6 00 00 00 00',
            'callback_enabled' => true,
            'callback_channels' => ['phone', 'email'],
        ]);

        $instructions = app(ConversationInstructionsBuilder::class)->build($settings, $site);

        $this->assertStringContainsString('Example Organization', $instructions);
        $this->assertStringContainsString('A deadline within 24 hours.', $instructions);
        $this->assertStringContainsString('WhatsApp for a direct conversation', $instructions);
        $this->assertStringContainsString('The visitor chooses the contact channel', $instructions);
        $this->assertStringNotContainsString('Marcos', $instructions);
        $this->assertStringNotContainsString('lawyer', strtolower($instructions));
    }

    public function test_enabled_channels_require_their_minimum_configuration(): void
    {
        $this->expectException(ValidationException::class);

        ConversationSetting::current()->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => null,
        ]);
    }
}
