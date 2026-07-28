<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Actions\CreateInquiryFromConversation;
use App\Modules\Conversations\Actions\FindAnonymousConversation;
use App\Modules\Conversations\Actions\StartAnonymousConversation;
use App\Modules\Conversations\Enums\ConversationChannel;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Enums\MessageVisibility;
use App\Modules\Conversations\Mail\ConversationCallbackReceived;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConversationDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_references_use_the_configured_unambiguous_format(): void
    {
        config()->set('maracuja.conversations.reference_length', 6);

        $reference = StartAnonymousConversation::run()->conversation->public_reference;

        $this->assertSame(6, strlen($reference));
        $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{6}$/', $reference);
    }

    public function test_it_starts_and_resumes_an_anonymous_website_conversation(): void
    {
        $session = StartAnonymousConversation::run(
            locale: 'fr',
            entryUrl: 'https://example.test/services',
        );

        $this->assertSame(ConversationChannel::Website, $session->conversation->channel);
        $this->assertSame(ConversationStatus::New, $session->conversation->status);
        $this->assertNotSame($session->token, $session->conversation->getRawOriginal('session_token_hash'));
        $this->assertTrue(
            FindAnonymousConversation::run($session->conversation->id, $session->token)
                ?->is($session->conversation),
        );
        $this->assertNull(FindAnonymousConversation::run($session->conversation->id, 'wrong-token'));
    }

    public function test_it_adds_public_messages_and_updates_the_timeline(): void
    {
        $conversation = StartAnonymousConversation::run()->conversation;

        $message = AddMessage::run(
            $conversation,
            'Bonjour, je voudrais une information.',
            MessageAuthorType::Visitor,
        );

        $this->assertSame(MessageVisibility::Public, $message->visibility);
        $this->assertSame('Bonjour, je voudrais une information.', $message->content);
        $this->assertNotNull($conversation->refresh()->last_message_at);
        $this->assertCount(1, $conversation->publicMessages);
    }

    public function test_internal_notes_never_belong_to_the_public_timeline(): void
    {
        $conversation = StartAnonymousConversation::run()->conversation;

        AddMessage::run(
            $conversation,
            'Rappeler demain matin.',
            MessageAuthorType::Human,
            MessageVisibility::Internal,
        );

        $this->assertCount(1, $conversation->messages);
        $this->assertCount(0, $conversation->publicMessages);
    }

    public function test_a_visitor_cannot_create_an_internal_note(): void
    {
        $this->expectException(ValidationException::class);

        AddMessage::run(
            StartAnonymousConversation::run()->conversation,
            'Tentative de note.',
            MessageAuthorType::Visitor,
            MessageVisibility::Internal,
        );
    }

    public function test_empty_and_oversized_messages_are_rejected(): void
    {
        $conversation = StartAnonymousConversation::run()->conversation;

        try {
            AddMessage::run($conversation, ' ', MessageAuthorType::Visitor);
            $this->fail('An empty message should be rejected.');
        } catch (ValidationException) {
            $this->assertCount(0, $conversation->messages);
        }

        $this->expectException(ValidationException::class);
        AddMessage::run($conversation, str_repeat('a', 5001), MessageAuthorType::Visitor);
    }

    public function test_a_consented_contact_creates_one_inquiry_from_a_conversation(): void
    {
        Mail::fake();
        config()->set('maracuja.conversations.notifications.recipient', 'equipe@example.test');
        $conversation = StartAnonymousConversation::run(locale: 'fr')->conversation;
        $conversation->update(['summary' => 'Demande initiale de contact.']);

        $data = [
            'name' => 'Personne de test',
            'phone' => '+33 6 12 34 56 78',
            'preferred_contact' => 'whatsapp',
            'consent' => true,
        ];

        $first = CreateInquiryFromConversation::run($conversation, $data);
        $second = CreateInquiryFromConversation::run($conversation, $data);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Inquiry::query()->count());
        $this->assertSame($conversation->id, $first->conversation_id);
        $this->assertSame($first->contact_id, $conversation->refresh()->contact_id);
        $this->assertNotNull($first->consent_at);
        $this->assertNull($first->email);
        Mail::assertSent(ConversationCallbackReceived::class, 1);
        Mail::assertSent(
            ConversationCallbackReceived::class,
            fn (ConversationCallbackReceived $mail): bool => $mail->hasTo('equipe@example.test'),
        );
    }
}
