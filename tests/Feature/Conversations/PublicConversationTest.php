<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageVisibility;
use App\Modules\Conversations\Models\Conversation;
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
    }

    public function test_the_first_message_starts_a_session_without_a_qualification_form(): void
    {
        $response = $this->postJson('/conversation/messages', [
            'content' => 'Bonjour, j’ai une question.',
            'entry_url' => 'https://example.test/services',
            'website' => '',
        ])->assertCreated();

        $response
            ->assertJsonPath('messages.0.author', 'visitor')
            ->assertJsonPath('messages.1.author', 'ai')
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
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.content', 'Premier message.');
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
        config()->set('maracuja.conversations.whatsapp.enabled', true);
        config()->set('maracuja.conversations.whatsapp.number', '+33 6 12 34 56 78');
        config()->set(
            'maracuja.conversations.whatsapp.message',
            'Bonjour, référence {{reference}}.',
        );

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
        $this->assertStringContainsString('https://wa.me/33612345678', $url);
        $this->assertStringContainsString(rawurlencode($conversation->public_reference), $url);
        $this->assertStringNotContainsString('confidentiel', $url);
    }

    public function test_direct_whatsapp_is_available_before_starting_a_conversation(): void
    {
        config()->set('maracuja.conversations.whatsapp.enabled', true);
        config()->set('maracuja.conversations.whatsapp.number', '+55 65 99999-0000');
        config()->set('maracuja.conversations.whatsapp.direct_message', 'Bonjour, contact direct.');

        $this->getJson('/conversation/session')
            ->assertOk()
            ->assertJsonPath(
                'whatsapp_url',
                fn (string $url): bool => str_starts_with($url, 'https://wa.me/5565999990000')
                    && str_contains(rawurldecode($url), 'Bonjour, contact direct.')
                    && ! str_contains($url, '{{reference}}'),
            );
    }

    public function test_a_visitor_can_request_a_callback_without_a_qualification_form(): void
    {
        $this->postJson('/conversation/messages', [
            'content' => 'Je souhaite parler à une personne.',
        ])->assertCreated();

        $this->postJson('/conversation/handover')->assertOk();
        $this->postJson('/conversation/callback')
            ->assertOk()
            ->assertJsonPath('conversation.collecting_contact', true);

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
    }
}
