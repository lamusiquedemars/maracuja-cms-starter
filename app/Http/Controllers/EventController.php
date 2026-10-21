<?php

namespace App\Http\Controllers;

use App\Support\ContentSlots;
use App\Support\Modules;
use Illuminate\View\View;
use Maracuja\SiteCore\Events\Models\Event;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;

class EventController extends Controller
{
    public function index(): View
    {
        abort_unless(Modules::enabled('events'), 404);

        return view('site.events.index', [
            'settings' => SiteSetting::current(),
            'label' => ContentSlots::value('events.public_label', 'Événements'),
            'subtitle' => ContentSlots::value('events.index.subtitle', 'Dates à venir et rendez-vous publics.'),
            'events' => Event::query()
                ->with(['venue', 'participants'])
                ->upcoming()
                ->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        abort_unless(Modules::enabled('events'), 404);

        $event = Event::query()
            ->with(['venue', 'participants'])
            ->where('slug', $slug)
            ->visible()
            ->firstOrFail();

        return view('site.events.show', [
            'settings' => SiteSetting::current(),
            'label' => ContentSlots::value('events.public_label', 'Événements'),
            'event' => $event,
        ]);
    }
}
