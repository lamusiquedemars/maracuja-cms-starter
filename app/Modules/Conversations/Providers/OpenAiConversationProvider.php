<?php

namespace App\Modules\Conversations\Providers;

use App\Modules\Conversations\Contracts\ConversationAiProvider;
use App\Modules\Conversations\Data\AiConversationRequest;
use App\Modules\Conversations\Data\AiConversationResult;
use App\Modules\Conversations\Enums\ConversationUrgency;
use App\Modules\Conversations\Enums\HandoverReason;
use App\Modules\Conversations\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OpenAiConversationProvider implements ConversationAiProvider
{
    public function respond(AiConversationRequest $request): AiConversationResult
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new AiProviderException('OpenAI is not configured.');
        }

        try {
            $response = Http::baseUrl((string) config('services.openai.base_url'))
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('maracuja.conversations.ai.timeout_seconds', 20))
                ->retry(2, 250, throw: false)
                ->post('/responses', array_filter([
                    'model' => (string) config('maracuja.conversations.ai.model'),
                    'instructions' => $request->instructions,
                    'input' => $request->messages,
                    'max_output_tokens' => (int) config('maracuja.conversations.ai.max_output_tokens', 600),
                    'reasoning' => [
                        'effort' => (string) config('maracuja.conversations.ai.reasoning_effort', 'low'),
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'conversation_reply',
                            'strict' => true,
                            'schema' => self::schema(),
                        ],
                    ],
                    'store' => false,
                    'safety_identifier' => $request->safetyIdentifier,
                ], fn (mixed $value): bool => $value !== null));
        } catch (ConnectionException $exception) {
            throw new AiProviderException('OpenAI is unavailable.', previous: $exception);
        }

        if ($response->failed()) {
            throw new AiProviderException("OpenAI returned HTTP {$response->status()}.");
        }

        $output = collect($response->json('output', []))
            ->flatMap(fn (array $item): array => $item['content'] ?? [])
            ->firstWhere('type', 'output_text');
        $text = is_array($output) ? ($output['text'] ?? null) : null;

        if (! is_string($text)) {
            throw new AiProviderException('OpenAI returned no structured text.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new AiProviderException('OpenAI returned invalid JSON.');
        }

        $validated = Validator::make($data, [
            'reply' => ['required', 'string', 'max:5000'],
            'summary' => ['present', 'string', 'max:5000'],
            'topic' => ['nullable', 'string', 'max:255'],
            'urgency' => ['required', Rule::enum(ConversationUrgency::class)],
            'requires_human' => ['required', 'boolean'],
            'handover_reason' => ['present', 'nullable', Rule::enum(HandoverReason::class)],
            'offer_contact_options' => ['required', 'boolean'],
            'qualification' => ['required', 'array'],
            'qualification.category' => ['nullable', 'string', 'max:120'],
            'qualification.location' => ['nullable', 'string', 'max:120'],
            'qualification.preferred_contact' => ['nullable', 'string', 'max:40'],
        ])->validate();

        return new AiConversationResult(
            reply: $validated['reply'],
            summary: $validated['summary'],
            topic: $validated['topic'],
            urgency: ConversationUrgency::from($validated['urgency']),
            requiresHuman: $validated['requires_human'],
            handoverReason: filled($validated['handover_reason'])
                ? HandoverReason::from($validated['handover_reason'])
                : null,
            offerContactOptions: $validated['offer_contact_options'],
            qualification: $validated['qualification'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reply' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'topic' => ['type' => ['string', 'null']],
                'urgency' => [
                    'type' => 'string',
                    'enum' => array_column(ConversationUrgency::cases(), 'value'),
                ],
                'requires_human' => ['type' => 'boolean'],
                'handover_reason' => [
                    'anyOf' => [
                        [
                            'type' => 'string',
                            'enum' => array_column(HandoverReason::cases(), 'value'),
                        ],
                        ['type' => 'null'],
                    ],
                ],
                'offer_contact_options' => ['type' => 'boolean'],
                'qualification' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => ['type' => ['string', 'null']],
                        'location' => ['type' => ['string', 'null']],
                        'preferred_contact' => ['type' => ['string', 'null']],
                    ],
                    'required' => ['category', 'location', 'preferred_contact'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => [
                'reply',
                'summary',
                'topic',
                'urgency',
                'requires_human',
                'handover_reason',
                'offer_contact_options',
                'qualification',
            ],
            'additionalProperties' => false,
        ];
    }
}
