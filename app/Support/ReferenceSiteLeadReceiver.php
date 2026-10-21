<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Acquisition\Actions\QueueInquiryForCremona;
use App\Modules\Inquiries\Actions\StoreInquiry;
use Maracuja\SiteCore\Contact\Contracts\LeadReceiver;
use Maracuja\SiteCore\Contact\Data\ContactRequestData;
use Maracuja\SiteCore\CremonaConnector\Services\CremonaLeadReceiver;

final class ReferenceSiteLeadReceiver implements LeadReceiver
{
    public function receive(ContactRequestData $request): void
    {
        if (config('maracuja-cremona-connector.enabled')) {
            app(CremonaLeadReceiver::class)->receive($request);

            return;
        }

        if (! Modules::enabled('inquiries')) {
            return;
        }

        $inquiry = StoreInquiry::run($request);
        QueueInquiryForCremona::run($inquiry);
    }
}
