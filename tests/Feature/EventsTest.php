<?php

namespace Tests\Feature;

use App\Modules\Events\Models\Event;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Modules\Venues\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_events_index_lists_upcoming_published_events_with_venue(): void
    {
        SiteSetting::current();

        $venue = Venue::query()->create([
            'name' => 'Théâtre des Lumières',
            'city' => 'Nantes',
            'country' => 'France',
        ]);

        Event::query()->create([
            'venue_id' => $venue->id,
            'title' => 'Concert de printemps',
            'starts_at' => now()->addWeek(),
            'excerpt' => 'Un rendez-vous public.',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Event::query()->create([
            'title' => 'Ancien rendez-vous',
            'starts_at' => now()->subWeek(),
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Event::query()->create([
            'title' => 'Brouillon discret',
            'starts_at' => now()->addWeeks(2),
            'is_published' => false,
        ]);

        $this->get('/evenements')
            ->assertOk()
            ->assertSee('Concert de printemps')
            ->assertSee('Théâtre des Lumières')
            ->assertSee('Nantes')
            ->assertDontSee('Ancien rendez-vous')
            ->assertDontSee('Brouillon discret');
    }

    public function test_event_detail_renders_venue_and_action_links(): void
    {
        SiteSetting::current();

        $venue = Venue::query()->create([
            'name' => 'Salle Atlantique',
            'address' => '12 rue du Port',
            'postal_code' => '44000',
            'city' => 'Nantes',
            'country' => 'France',
            'maps_url' => 'https://example.com/map',
        ]);

        $event = Event::query()->create([
            'venue_id' => $venue->id,
            'title' => 'Atelier public',
            'starts_at' => now()->addDays(10),
            'description' => '<p>Programme détaillé.</p>',
            'ticket_url' => 'https://example.com/tickets',
            'external_url' => 'https://example.com/info',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/evenements/'.$event->slug)
            ->assertOk()
            ->assertSee('Atelier public')
            ->assertSee('Salle Atlantique')
            ->assertSee('12 rue du Port')
            ->assertSee('Programme détaillé')
            ->assertSee('https://example.com/tickets')
            ->assertSee('https://example.com/map');
    }

    public function test_events_routes_are_disabled_when_module_is_off(): void
    {
        config(['maracuja.modules.events' => false]);

        $this->get('/evenements')
            ->assertNotFound();
    }
}
