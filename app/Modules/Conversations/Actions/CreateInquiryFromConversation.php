<?php

namespace App\Modules\Conversations\Actions;

use App\Modules\Contacts\Actions\ResolveContact;
use App\Modules\Conversations\Mail\ConversationCallbackReceived;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Inquiries\Enums\InquiryStatus;
use App\Modules\Inquiries\Models\Inquiry;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CreateInquiryFromConversation
{
    /**
     * @param  array{name: string, email?: ?string, phone?: ?string, location?: ?string, preferred_contact?: ?string, consent: bool}  $data
     */
    public static function run(Conversation $conversation, array $data): Inquiry
    {
        $data = Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc', 'max:160', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:60', 'required_without:email'],
            'location' => ['nullable', 'string', 'max:120'],
            'preferred_contact' => ['nullable', 'in:email,phone,whatsapp'],
            'consent' => ['required', 'accepted'],
        ])->validate();

        [$inquiry, $created] = DB::transaction(function () use ($conversation, $data): array {
            $conversation = Conversation::query()->lockForUpdate()->findOrFail($conversation->getKey());

            if ($conversation->inquiry()->exists()) {
                return [$conversation->inquiry()->firstOrFail(), false];
            }

            $contact = ResolveContact::run([
                'display_name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'locale' => $conversation->locale,
                'source' => 'conversation',
            ]);

            $inquiry = Inquiry::query()->create([
                'conversation_id' => $conversation->getKey(),
                'contact_id' => $contact->getKey(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'subject' => "Conversation {$conversation->public_reference}",
                'message' => $conversation->summary ?: "Conversation {$conversation->public_reference}",
                'consent_at' => now(),
                'source' => 'conversation',
                'status' => InquiryStatus::New,
            ]);

            $conversation->update([
                'contact_id' => $contact->getKey(),
                'qualification' => [
                    ...($conversation->qualification ?? []),
                    'preferred_contact' => $data['preferred_contact'] ?? null,
                    'contact_collection' => 'complete',
                ],
            ]);

            return [$inquiry, true];
        });

        if ($created && config('maracuja.conversations.notifications.enabled')) {
            $recipient = config('maracuja.conversations.notifications.recipient')
                ?: SiteSetting::current()->contact_email;

            if (filled($recipient)) {
                Mail::to($recipient)->send(new ConversationCallbackReceived($inquiry));
            }
        }

        return $inquiry;
    }
}
