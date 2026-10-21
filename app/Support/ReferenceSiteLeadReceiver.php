<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Acquisition\Actions\QueueContactMessageForCremona;
use App\Modules\Acquisition\Actions\QueueInquiryForCremona;
use App\Modules\Inquiries\Actions\StoreInquiry;
use Maracuja\SiteCore\Contact\Contracts\LeadReceiver;
use Maracuja\SiteCore\Contact\Data\ContactRequestData;

final class ReferenceSiteLeadReceiver implements LeadReceiver
{
    public function receive(ContactRequestData $request): void
    {
        if (config('maracuja.acquisition.cremona.enabled')) {
            QueueContactMessageForCremona::run($request);

            return;
        }

        if (! Modules::enabled('inquiries')) {
            return;
        }

        $inquiry = StoreInquiry::run($request);
        QueueInquiryForCremona::run($inquiry);
    }
}
