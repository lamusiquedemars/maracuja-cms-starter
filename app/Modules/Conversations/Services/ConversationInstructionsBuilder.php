<?php

namespace App\Modules\Conversations\Services;

use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\SiteSettings\Models\SiteSetting;

class ConversationInstructionsBuilder
{
    public function build(ConversationSetting $settings, ?SiteSetting $site = null): string
    {
        $site ??= SiteSetting::current();

        $sections = [
            $this->universalRules(),
            $this->profile($settings, $site),
            $this->qualification($settings),
            $this->routing($settings),
        ];

        if (filled($settings->additional_instructions)) {
            $sections[] = "Additional site instructions:\n".trim((string) $settings->additional_instructions);
        }

        return implode("\n\n", array_filter($sections));
    }

    private function universalRules(): string
    {
        return <<<'INSTRUCTIONS'
Universal rules:
- You are an initial routing assistant, not a replacement for a qualified human.
- Be transparent about your role. Never impersonate a person or professional.
- Do not promise outcomes or make decisions that bind the organization or visitor.
- Ask one short question at a time and collect only information needed to understand and route the request.
- Never request passwords, banking details, full identity documents, authentication codes, or unnecessary sensitive data.
- Do not ask for documents during the initial conversation.
- Before collecting contact details, explain their purpose and obtain explicit consent.
- The visitor chooses the contact channel. You may propose configured options but never choose one on their behalf.
- Keep summaries and channel handovers brief and exclude unnecessary sensitive details.
INSTRUCTIONS;
    }

    private function profile(ConversationSetting $settings, SiteSetting $site): string
    {
        $summary = filled($settings->organization_summary)
            ? trim((string) $settings->organization_summary)
            : 'No additional organization context has been configured.';

        return implode("\n", [
            'Organization profile:',
            '- Name: '.($site->site_name ?: 'This organization'),
            '- Assistant language: '.$settings->assistant_language,
            '- Tone: '.$settings->assistant_tone,
            '- Context: '.$summary,
        ]);
    }

    private function qualification(ConversationSetting $settings): string
    {
        $fields = collect($settings->qualification_fields ?? [])
            ->map(fn (string $field): string => ConversationSetting::QUALIFICATION_FIELDS[$field] ?? $field)
            ->implode(', ');

        $lines = [
            'Qualification:',
            '- Information that may be collected: '.($fields !== '' ? $fields : 'the general purpose of the request only'),
        ];

        if (filled($settings->qualification_guidance)) {
            $lines[] = '- Site guidance: '.trim((string) $settings->qualification_guidance);
        }

        if (filled($settings->sensitive_data_guidance)) {
            $lines[] = '- Additional data to avoid: '.trim((string) $settings->sensitive_data_guidance);
        }

        return implode("\n", $lines);
    }

    private function routing(ConversationSetting $settings): string
    {
        $triggers = collect($settings->routing_triggers ?? [])
            ->map(fn (string $trigger): string => ConversationSetting::ROUTING_TRIGGERS[$trigger] ?? $trigger)
            ->implode(', ');
        $channels = [];

        if ($settings->whatsapp_enabled) {
            $channels[] = 'WhatsApp for a direct conversation';
        }

        if ($settings->callback_enabled) {
            $callback = collect($settings->callback_channels ?? [])
                ->map(fn (string $channel): string => ConversationSetting::CALLBACK_CHANNELS[$channel] ?? $channel)
                ->implode(', ');
            $channels[] = 'a later contact request through: '.$callback;
        }

        $lines = [
            'Routing:',
            '- Propose contact options when: '.($triggers !== '' ? $triggers : 'the visitor explicitly asks'),
            '- Available options: '.($channels !== [] ? implode('; ', $channels) : 'none; continue the initial conversation only'),
            '- When more than one option is available, explain the practical difference and let the visitor choose.',
            '- Set offer_contact_options to true only when a configured trigger is met and at least one contact option is available.',
        ];

        if (filled($settings->urgency_guidance)) {
            $lines[] = '- Urgency criteria configured by the site: '.trim((string) $settings->urgency_guidance);
        }

        if (filled($settings->expected_response_time)) {
            $lines[] = '- Announced response time: '.trim((string) $settings->expected_response_time);
        }

        return implode("\n", $lines);
    }
}
