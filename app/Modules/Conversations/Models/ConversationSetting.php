<?php

namespace App\Modules\Conversations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ConversationSetting extends Model
{
    public const QUALIFICATION_FIELDS = [
        'request_topic' => 'Objet général de la demande',
        'location' => 'Localisation',
        'deadline' => 'Date, échéance ou moment important',
        'existing_contact' => 'Interlocuteur déjà impliqué',
        'preferred_contact' => 'Canal de contact préféré',
    ];

    public const ROUTING_TRIGGERS = [
        'minimum_context' => 'Le contexte minimal est compris',
        'visitor_request' => 'Le visiteur demande à parler à une personne',
        'urgency' => 'Une urgence configurée est détectée',
        'assistant_limit' => 'L’assistant atteint la limite de son rôle',
    ];

    public const CALLBACK_CHANNELS = [
        'whatsapp' => 'WhatsApp',
        'phone' => 'Téléphone',
        'email' => 'E-mail',
    ];

    protected $fillable = [
        'is_enabled',
        'widget_button_label',
        'widget_title',
        'welcome_message',
        'privacy_notice',
        'assistant_language',
        'assistant_tone',
        'organization_summary',
        'qualification_fields',
        'qualification_guidance',
        'urgency_guidance',
        'sensitive_data_guidance',
        'routing_triggers',
        'whatsapp_enabled',
        'whatsapp_number',
        'whatsapp_message_template',
        'whatsapp_contact_message_template',
        'callback_enabled',
        'callback_channels',
        'notification_email',
        'expected_response_time',
        'max_visitor_messages',
        'warning_at_message',
        'interaction_limit_message',
        'additional_instructions',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'qualification_fields' => 'array',
            'routing_triggers' => 'array',
            'whatsapp_enabled' => 'boolean',
            'callback_enabled' => 'boolean',
            'callback_channels' => 'array',
            'max_visitor_messages' => 'integer',
            'warning_at_message' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $setting): void {
            if ($setting->whatsapp_enabled && blank($setting->whatsapp_number)) {
                throw ValidationException::withMessages([
                    'whatsapp_number' => 'Renseignez le numéro WhatsApp proposé aux visiteurs.',
                ]);
            }

            if ($setting->callback_enabled && blank($setting->callback_channels)) {
                throw ValidationException::withMessages([
                    'callback_channels' => 'Sélectionnez au moins un canal de rappel.',
                ]);
            }

            if ($setting->warning_at_message >= $setting->max_visitor_messages) {
                throw ValidationException::withMessages([
                    'warning_at_message' => 'L’avertissement doit intervenir avant la limite de conversation.',
                ]);
            }
        });
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], self::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'is_enabled' => false,
            'widget_button_label' => 'Nous écrire',
            'widget_title' => 'Comment pouvons-nous vous aider ?',
            'welcome_message' => 'Bonjour ! Expliquez brièvement l’objet de votre demande pour que nous puissions vous orienter.',
            'privacy_notice' => 'Ne transmettez pas de mots de passe, coordonnées bancaires ou documents sensibles.',
            'assistant_language' => 'fr',
            'assistant_tone' => 'clair, calme et concis',
            'organization_summary' => null,
            'qualification_fields' => ['request_topic', 'location'],
            'qualification_guidance' => null,
            'urgency_guidance' => null,
            'sensitive_data_guidance' => null,
            'routing_triggers' => array_keys(self::ROUTING_TRIGGERS),
            'whatsapp_enabled' => false,
            'whatsapp_number' => null,
            'whatsapp_message_template' => 'Bonjour, je viens du site. Ma référence de conversation est {{reference}}.',
            'whatsapp_contact_message_template' => 'Bonjour, je vous contacte au sujet de votre demande {{reference}}.',
            'callback_enabled' => false,
            'callback_channels' => ['phone', 'email'],
            'notification_email' => null,
            'expected_response_time' => null,
            'max_visitor_messages' => 12,
            'warning_at_message' => 10,
            'interaction_limit_message' => 'Nous avons atteint la limite de cet échange initial. Vous pouvez choisir l’un des moyens de contact proposés.',
            'additional_instructions' => null,
        ];
    }
}
