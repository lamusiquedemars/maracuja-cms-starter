<?php

namespace App\Http\Controllers;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Contracts\View\View;

class AudienceUnsubscribeController extends Controller
{
    public function __invoke(string $token): View
    {
        abort_unless(Modules::enabled('audience'), 404);

        $contact = AudienceContact::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $contact->unsubscribe();

        return view('site.audience-unsubscribed', [
            'contact' => $contact,
            'settings' => SiteSetting::current(),
            'seoTitle' => 'Désinscription confirmée',
            'seoDescription' => 'Cette adresse ne recevra plus les messages ciblés envoyés depuis ce site.',
        ]);
    }
}
