<?php

namespace App\Modules\Inquiries\Actions;

use Maracuja\SiteCore\Contact\Data\ContactRequestData;
use Maracuja\SiteCore\Contact\Actions\StoreInquiry as CoreStoreInquiry;
use App\Modules\Inquiries\Models\Inquiry;

class StoreInquiry
{
    public static function run(ContactRequestData $message): Inquiry
    {
        return CoreStoreInquiry::run($message, Inquiry::class);
    }
}
