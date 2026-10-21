<?php

namespace App\Http\Controllers;

use App\Modules\Acquisition\Support\Attribution;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Maracuja\SiteCore\Contact\Contracts\LeadReceiver;
use Maracuja\SiteCore\Contact\Data\ContactRequestData;
use Maracuja\SiteCore\Contact\Mail\ContactRequestConfirmation;
use Maracuja\SiteCore\Contact\Mail\ContactRequestReceived;
use Maracuja\SiteCore\Contact\Support\ContactRequestRules;
use Maracuja\SiteCore\Foundation\Models\Page;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;

class ContactController extends Controller
{
    public function create(): View
    {
        abort_unless(Modules::enabled('contact_form'), 404);

        return view('site.contact', [
            'settings' => SiteSetting::current(),
            'page' => Modules::enabled('pages')
                ? Page::query()->where('slug', 'contact')->where('is_published', true)->first()
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Modules::enabled('contact_form'), 404);

        $settings = SiteSetting::current();
        $data = $request->validate([
            ...ContactRequestRules::make($settings->contact_form_show_name),
            'acquisition_attribution' => ['nullable', 'json', 'max:20000'],
        ]);

        if (! $settings->contact_form_show_name) {
            $data['name'] = $data['email'];
        }

        $message = new ContactRequestData(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            subject: $data['subject'] ?? null,
            message: $data['message'],
            consentedAt: ($data['contact_consent'] ?? false) ? now() : null,
            metadata: ['attribution' => Attribution::fromJson($data['acquisition_attribution'] ?? null)],
        );

        app(LeadReceiver::class)->receive($message);

        if ($settings->contact_form_send_admin_email && $settings->contact_email) {
            Mail::to($settings->contact_email)->send(new ContactRequestReceived($message));
        }

        if ($settings->contact_form_send_confirmation_email) {
            Mail::to($message->email)->send(new ContactRequestConfirmation($message));
        }

        return redirect()->route('contact')->with('status', config('maracuja.acquisition.cremona.enabled')
            ? 'Votre message a bien été envoyé.'
            : 'Votre message a bien été enregistré. Nous répondrons dans les meilleurs délais.');
    }
}
