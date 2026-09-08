<?php

namespace App\Modules\Acquisition\Actions;

use App\Modules\Acquisition\Jobs\SendAcquisitionDelivery;
use App\Modules\Acquisition\Models\AcquisitionDelivery;
use App\Modules\Inquiries\Models\Inquiry;

class QueueInquiryForCremona
{
    public static function run(Inquiry $inquiry): ?AcquisitionDelivery
    {
        if (! config('maracuja.acquisition.cremona.enabled') || blank(config('maracuja.acquisition.cremona.endpoint')) || blank(config('maracuja.acquisition.cremona.token'))) {
            return null;
        }
        $delivery = AcquisitionDelivery::query()->firstOrCreate(['inquiry_id' => $inquiry->getKey()], [
            'idempotency_key' => 'maracuja-cms:inquiry:'.$inquiry->getKey(), 'status' => 'pending',
            'payload' => ['source' => ['channel' => 'website', 'name' => 'maracuja-cms', 'site_reference' => config('maracuja.acquisition.cremona.site_reference'), 'form_reference' => 'contact-v1'],
                'attribution' => ['source' => $inquiry->attribution_source, 'medium' => $inquiry->attribution_medium, 'campaign' => $inquiry->attribution_campaign, 'first_touch' => $inquiry->attribution_first_touch, 'last_touch' => $inquiry->attribution_last_touch, 'method' => $inquiry->attribution_method, 'confidence' => $inquiry->attribution_confidence],
                'contact' => ['name' => $inquiry->name, 'email' => $inquiry->email, 'phone' => $inquiry->phone],
                'request' => ['subject' => $inquiry->subject, 'message' => $inquiry->message],
                'consent' => ['purpose' => 'respond_to_request', 'status' => 'granted', 'source' => 'website_contact_form', 'granted_at' => $inquiry->consent_at?->toIso8601String()], ],
        ]);
        if ($delivery->wasRecentlyCreated || $delivery->status !== 'sent') {
            (new SendAcquisitionDelivery($delivery->getKey()))->handle();
        }

        return $delivery;
    }
}
