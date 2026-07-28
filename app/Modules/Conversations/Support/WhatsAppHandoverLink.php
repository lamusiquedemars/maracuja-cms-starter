<?php

namespace App\Modules\Conversations\Support;

use App\Modules\Conversations\Models\Conversation;

class WhatsAppHandoverLink
{
    public static function make(?Conversation $conversation = null): ?string
    {
        if (! config('maracuja.conversations.whatsapp.enabled')) {
            return null;
        }

        $number = preg_replace(
            '/\D/',
            '',
            (string) config('maracuja.conversations.whatsapp.number'),
        );

        if (blank($number)) {
            return null;
        }

        $message = $conversation === null
            ? (string) config('maracuja.conversations.whatsapp.direct_message')
            : str_replace(
                '{{reference}}',
                $conversation->public_reference,
                (string) config('maracuja.conversations.whatsapp.message'),
            );

        return "https://wa.me/{$number}?text=".rawurlencode(trim($message));
    }

    public static function makeForContact(Conversation $conversation): ?string
    {
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
            (string) config('maracuja.conversations.whatsapp.contact_message'),
        );

        return "https://wa.me/{$phone}?text=".rawurlencode(trim($message));
    }
}
