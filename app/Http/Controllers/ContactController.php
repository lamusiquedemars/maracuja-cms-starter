<?php

namespace App\Http\Controllers;

use App\Modules\ContactForm\Data\ContactMessage;
use App\Modules\ContactForm\Mail\ContactMessageConfirmation;
use App\Modules\ContactForm\Mail\ContactMessageReceived;
use App\Modules\Inquiries\Actions\StoreInquiry;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

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

        $rules = [
            'website' => ['nullable', 'string', 'max:0'],
            'email' => ['required', 'string', 'max:160', 'email:rfc', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/'],
            'message' => ['required', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:60'],
            'subject' => ['nullable', 'string', 'max:160'],
        ];

        if ($settings->contact_form_show_name) {
            $rules['name'] = ['required', 'string', 'max:120'];
        } else {
            $rules['name'] = ['sometimes', 'nullable', 'string', 'max:120'];
        }

        $data = $request->validate($rules);

        if (! $settings->contact_form_show_name) {
            $data['name'] = $data['email'];
        }

        $message = ContactMessage::fromArray($data);

        if (Modules::enabled('inquiries') && class_exists(StoreInquiry::class)) {
            StoreInquiry::run($message);
        }

        if ($settings->contact_form_send_admin_email && $settings->contact_email) {
            Mail::to($settings->contact_email)->send(new ContactMessageReceived($message));
        }

        if ($settings->contact_form_send_confirmation_email) {
            Mail::to($message->email)->send(new ContactMessageConfirmation($message));
        }

        return redirect()
            ->route('contact')
            ->with('status', Modules::enabled('inquiries')
                ? 'Votre message a bien été enregistré. Nous répondrons dans les meilleurs délais.'
                : 'Votre message a bien été envoyé.');
    }
}
