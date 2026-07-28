<?php

namespace App\Modules\Conversations\Support;

use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;

class WhatsAppHandoverLink
{
    public static function make(?Conversation $conversation = null): ?string
    {
        $settings = ConversationSetting::current();

        if (! $settings->whatsapp_enabled) {
            return null;
        }

        $number = preg_replace(
            '/\D/',
            '',
            (string) $settings->whatsapp_number,
        );

        if (blank($number)) {
            return null;
        }

        $message = str_replace(
            '{{reference}}',
            $conversation?->public_reference ?? '',
            (string) $settings->whatsapp_message_template,
        );

        return "https://wa.me/{$number}?text=".rawurlencode(trim($message));
    }

    public static function makeForContact(Conversation $conversation): ?string
    {
        $settings = ConversationSetting::current();
        $phone = preg_replace(
            '/\D/',
            '',
            (string) ($conversation->contact?->normalized_phone ?: $conversation->contact?->phone),
        );

        if (blank($phone)) {
            return null;
        }

        $message = str_replace(
            '{{reference}}',
            $conversation->public_reference,
            (string) $settings->whatsapp_contact_message_template,
        );

        return "https://wa.me/{$phone}?text=".rawurlencode(trim($message));
    }
}
