<?php

namespace App\Modules\Inquiries\Actions;

use Maracuja\SiteCore\Contact\Data\ContactRequestData;
use App\Modules\Inquiries\Enums\InquiryStatus;
use App\Modules\Inquiries\Models\Inquiry;

class StoreInquiry
{
    public static function run(ContactRequestData $message): Inquiry
    {
        return Inquiry::query()->create([
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'subject' => $message->subject,
            'message' => $message->message,
            'consent_at' => $message->consentedAt,
            'source' => $message->source,
            'attribution_source' => $message->metadata['attribution']['source'] ?? null,
            'attribution_medium' => $message->metadata['attribution']['medium'] ?? null,
            'attribution_campaign' => $message->metadata['attribution']['campaign'] ?? null,
            'attribution_first_touch' => $message->metadata['attribution']['first_touch'] ?? null,
            'attribution_last_touch' => $message->metadata['attribution']['last_touch'] ?? null,
            'attribution_method' => $message->metadata['attribution']['method'] ?? null,
            'attribution_confidence' => $message->metadata['attribution']['confidence'] ?? null,
            'status' => InquiryStatus::New,
        ]);
    }
}
