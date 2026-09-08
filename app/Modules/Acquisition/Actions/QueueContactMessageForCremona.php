<?php

namespace App\Modules\Acquisition\Actions;

use App\Modules\Acquisition\Jobs\SendAcquisitionDelivery;
use App\Modules\Acquisition\Models\AcquisitionDelivery;
use App\Modules\ContactForm\Data\ContactMessage;

class QueueContactMessageForCremona
{
    public static function run(ContactMessage $message): ?AcquisitionDelivery
    {
        if (! config('maracuja.acquisition.cremona.enabled') || blank(config('maracuja.acquisition.cremona.endpoint')) || blank(config('maracuja.acquisition.cremona.token'))) {
            return null;
        }

        $key = 'maracuja-cms:contact:'.str()->ulid();
        $delivery = AcquisitionDelivery::query()->create([
            'idempotency_key' => $key,
            'status' => 'pending',
            'payload' => [
                'source' => ['channel' => 'website', 'name' => 'maracuja-cms', 'site_reference' => config('maracuja.acquisition.cremona.site_reference'), 'form_reference' => 'contact'],
                'attribution' => ['source' => $message->attributionSource, 'medium' => $message->attributionMedium, 'campaign' => $message->attributionCampaign, 'first_touch' => $message->attributionFirstTouch, 'last_touch' => $message->attributionLastTouch, 'method' => $message->attributionMethod, 'confidence' => $message->attributionConfidence],
                'contact' => ['name' => $message->name, 'email' => $message->email, 'phone' => $message->phone],
                'request' => ['subject' => $message->subject, 'message' => $message->message],
            ],
        ]);
        SendAcquisitionDelivery::dispatch($delivery->getKey())->afterCommit();

        return $delivery;
    }
}
