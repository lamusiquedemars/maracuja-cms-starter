<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageVisibility;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('maracuja.modules.conversations', true);
        config()->set('maracuja.conversations.ai.provider', 'fake');
        ConversationSetting::current()->update(['is_enabled' => true]);
    }

    public function test_the_first_message_starts_a_session_without_a_qualification_form(): void
    {
        $response = $this->postJson('/conversation/messages', [
            'content' => 'Bonjour, j’ai une question.',
            'entry_url' => 'https://example.test/services',
            'website' => '',
        ])->assertCreated();

        $response
            ->assertJsonPath('messages.0.author', 'ai')
            ->assertJsonPath('messages.1.author', 'visitor')
            ->assertJsonPath('messages.2.author', 'ai')
            ->assertJsonPath('conversation.status', 'ai_active');

        $this->assertSame(1, Conversation::query()->count());
        $this->assertSame('https://example.test/services', Conversation::query()->first()->entry_url);
    }

    public function test_the_anonymous_session_restores_its_public_history(): void
    {
        $this->postJson('/conversation/messages', [
            'content' => 'Premier message.',
        ])->assertCreated();

        $this->getJson('/conversation/session')
            ->assertOk()
            ->assertJsonCount(3, 'messages')
            ->assertJsonPath('messages.1.content', 'Premier message.');
    }

    public function test_opening_the_widget_shows_a_welcome_message_without_creating_a_conversation(): void
    {
        ConversationSetting::current()->update([
            'welcome_message' => 'Bonjour, comment pouvons-nous vous orienter ?',
        ]);

        $this->getJson('/conversation/session')
            ->assertOk()
            ->assertJsonPath('conversation', null)
            ->assertJsonPath('messages.0.author', 'ai')
            ->assertJsonPath('messages.0.content', 'Bonjour, comment pouvons-nous vous orienter ?');

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_internal_notes_are_never_returned_by_the_public_endpoint(): void
    {
        $this->postJson('/conversation/messages', [
            'content' => 'Message public.',
        ])->assertCreated();

        $conversation = Conversation::query()->firstOrFail();
        AddMessage::run(
            $conversation,
            'Note strictement interne.',
            MessageAuthorType::Human,
            MessageVisibility::Internal,
        );

        $content = $this->getJson('/conversation/session')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Message public.', $content);
        $this->assertStringNotContainsString('Note strictement interne.', $content);
    }

    public function test_honeypot_and_oversized_messages_are_rejected(): void
    {
        $this->postJson('/conversation/messages', [
            'content' => 'Spam',
            'website' => 'bot',
        ])->assertUnprocessable();

        $this->postJson('/conversation/messages', [
            'content' => str_repeat('a', 5001),
        ])->assertUnprocessable();

        $this->assertSame(0, Conversation::query()->count());
    }

    public function test_disabled_module_exposes_no_public_conversation_data(): void
    {
        config()->set('maracuja.modules.conversations', false);

        $this->getJson('/conversation/session')->assertNotFound();
        $this->postJson('/conversation/messages', ['content' => 'Bonjour'])->assertNotFound();
    }

    public function test_human_handover_stops_ai_and_whatsapp_contains_only_the_public_reference(): void
    {
        ConversationSetting::current()->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '+33 6 12 34 56 78',
            'whatsapp_message_template' => 'Bonjour, référence {{reference}}.',
        ]);

        $this->postJson('/conversation/messages', [
            'content' => 'Détail confidentiel qui ne doit pas être dans URL.',
        ])->assertCreated();

        $response = $this->postJson('/conversation/handover')
            ->assertOk()
            ->assertJsonPath('conversation.status', 'needs_human')
            ->assertJsonPath('conversation.awaiting_human', true);

        $conversation = Conversation::query()->firstOrFail();
        $url = $response->json('conversation.whatsapp_url');

        $this->assertFalse($conversation->ai_enabled);
        $this->assertSame('visitor_request', $conversation->handover_reason->value);
        $this->assertStringContainsString('https://wa.me/33612345678', $url);
        $this->assertStringContainsString(rawurlencode($conversation->public_reference), $url);
        $this->assertStringNotContainsString('confidentiel', $url);
    }

    public function test_contact_channels_are_not_exposed_before_the_ai_routes_the_conversation(): void
    {
        ConversationSetting::current()->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '+55 65 99999-0000',
        ]);

        $this->getJson('/conversation/session')
            ->assertOk()
            ->assertJsonPath('whatsapp_url', null);
    }

    public function test_the_configured_interaction_limit_stops_ai_and_exposes_contact_options(): void
    {
        ConversationSetting::current()->update([
            'max_visitor_messages' => 2,
            'warning_at_message' => 1,
            'interaction_limit_message' => 'Choisissez maintenant un moyen de contact.',
            'callback_enabled' => true,
            'callback_channels' => ['phone'],
        ]);

        $this->postJson('/conversation/messages', ['content' => 'Premier message.'])
            ->assertCreated()
            ->assertJsonPath('conversation.accepting_messages', true);

        $response = $this->postJson('/conversation/messages', ['content' => 'Deuxième message.'])
            ->assertCreated()
            ->assertJsonPath('conversation.status', 'needs_human')
            ->assertJsonPath('conversation.accepting_messages', false)
            ->assertJsonPath('conversation.callback_enabled', true);

        $conversation = Conversation::query()->sole();
        $this->assertSame('interaction_limit', $conversation->handover_reason->value);
        $this->assertSame(
            'Choisissez maintenant un moyen de contact.',
            collect($response->json('messages'))->last()['content'],
        );
    }

    public function test_a_visitor_can_request_a_callback_without_a_qualification_form(): void
    {
        ConversationSetting::current()->update([
            'callback_enabled' => true,
            'callback_channels' => ['whatsapp', 'phone', 'email'],
        ]);

        $this->postJson('/conversation/messages', [
            'content' => 'Je souhaite parler à une personne.',
        ])->assertCreated();

        $this->postJson('/conversation/handover')->assertOk();
        $this->postJson('/conversation/callback')
            ->assertOk()
            ->assertJsonPath('conversation.collecting_contact', true);

        $invalidName = $this->postJson('/conversation/messages', ['content' => 'par téléphone'])
            ->assertCreated()
            ->assertJsonPath('conversation.collecting_contact', true);

        $this->assertSame(
            config('maracuja.conversations.callback.invalid_name'),
            collect($invalidName->json('messages'))->last()['content'],
        );

        foreach (['Personne de test', 'WhatsApp', '+33 6 12 34 56 78'] as $answer) {
            $this->postJson('/conversation/messages', ['content' => $answer])->assertCreated();
        }

        $this->postJson('/conversation/messages', ['content' => 'oui'])
            ->assertCreated()
            ->assertJsonPath('conversation.collecting_contact', false)
            ->assertJsonPath('conversation.inquiry_created', true);

        $inquiry = Inquiry::query()->sole();
        $this->assertSame('Personne de test', $inquiry->name);
        $this->assertSame('+33 6 12 34 56 78', $inquiry->phone);
        $this->assertNull($inquiry->email);
        $this->assertNotNull($inquiry->consent_at);

        $messageCount = $inquiry->conversation->messages()->count();

        $this->postJson('/conversation/messages', ['content' => 'Il y a encore quelqu’un ?'])
            ->assertConflict();

        $this->assertSame($messageCount, $inquiry->conversation->messages()->count());
    }
}
