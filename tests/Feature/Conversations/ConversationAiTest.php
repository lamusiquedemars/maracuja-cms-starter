<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Actions\GenerateAiReply;
use App\Modules\Conversations\Actions\StartAnonymousConversation;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Conversations\Enums\MessageAuthorType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConversationAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_uses_responses_api_with_a_strict_schema_and_updates_the_conversation(): void
    {
        config()->set('maracuja.conversations.ai.provider', 'openai');
        config()->set('services.openai.api_key', 'test-key');
        config()->set('services.openai.base_url', 'https://api.openai.test/v1');
        config()->set('maracuja.conversations.ai.model', 'gpt-5.6-luna');

        Http::fake([
            'api.openai.test/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => json_encode([
                            'reply' => 'Je transmets votre demande à une personne.',
                            'summary' => 'Demande urgente à traiter.',
                            'topic' => 'assistance',
                            'urgency' => 'high',
                            'requires_human' => true,
                            'offer_contact_options' => true,
                            'qualification' => [
                                'category' => 'assistance',
                                'location' => null,
                                'preferred_contact' => 'phone',
                            ],
                        ]),
                    ]],
                ]],
            ]),
        ]);

        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'J’ai besoin d’une réponse rapide.', MessageAuthorType::Visitor);

        $reply = GenerateAiReply::run($conversation);
        $conversation->refresh();

        $this->assertSame(MessageAuthorType::Ai, $reply->author_type);
        $this->assertSame(ConversationStatus::NeedsHuman, $conversation->status);
        $this->assertSame(ConversationUrgency::High, $conversation->urgency);
        $this->assertFalse($conversation->ai_enabled);
        $this->assertSame('assistance', $conversation->qualification['category']);
        $this->assertTrue($conversation->qualification['_routing']['contact_options_suggested']);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $payload['model'] === 'gpt-5.6-luna'
                && $payload['store'] === false
                && $payload['text']['format']['type'] === 'json_schema'
                && $payload['text']['format']['strict'] === true
                && filled($payload['safety_identifier']);
        });
    }

    public function test_invalid_structured_output_falls_back_to_a_human_without_logging_content(): void
    {
        config()->set('maracuja.conversations.ai.provider', 'openai');
        config()->set('services.openai.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => '{"reply":"incomplete"}',
                    ]],
                ]],
            ]),
        ]);
        Log::spy();

        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'Contenu privé à ne pas journaliser.', MessageAuthorType::Visitor);

        $reply = GenerateAiReply::run($conversation);

        $this->assertSame(MessageAuthorType::System, $reply->author_type);
        $this->assertSame(ConversationStatus::NeedsHuman, $conversation->refresh()->status);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'Conversation AI provider failed.'
                && $context['conversation_id'] === $conversation->id
                && ! str_contains(json_encode($context), 'Contenu privé'),
        );
    }

    public function test_fake_provider_keeps_local_development_independent_from_openai(): void
    {
        config()->set('maracuja.conversations.ai.provider', 'fake');

        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'Bonjour.', MessageAuthorType::Visitor);

        $reply = GenerateAiReply::run($conversation);

        $this->assertSame(MessageAuthorType::Ai, $reply->author_type);
        $this->assertSame(ConversationStatus::AiActive, $conversation->refresh()->status);
        Http::assertNothingSent();
    }
}
