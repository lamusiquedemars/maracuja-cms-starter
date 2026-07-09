<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Support\Facades\DB;

class QueueSegmentMessage
{
    public static function run(SegmentMessage $message): int
    {
        if (! $message->isDraft()) {
            return $message->recipients_count;
        }

        $contacts = $message->segment
            ->contacts()
            ->where('accepts_email', true)
            ->whereNull('unsubscribed_at')
            ->whereNull('hard_bounced_at')
            ->whereNull('email_blacklisted_at')
            ->orderBy('audience_contacts.id')
            ->get();

        if ($contacts->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($message, $contacts): void {
            $contacts->each(function (AudienceContact $contact) use ($message): void {
                SegmentMessageDelivery::query()->firstOrCreate(
                    [
                        'segment_message_id' => $message->id,
                        'audience_contact_id' => $contact->id,
                    ],
                    [
                        'email' => $contact->email,
                        'status' => SegmentMessageDelivery::STATUS_PENDING,
                    ],
                );
            });

            $message->forceFill([
                'status' => SegmentMessage::STATUS_QUEUED,
                'recipients_count' => $contacts->count(),
                'sent_at' => null,
            ])->save();
        });

        return $contacts->count();
    }
}
