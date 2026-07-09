<?php

namespace Tests\Unit;

use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Services\BrevoAudienceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncAudienceSegmentToBrevoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_folder_list_and_syncs_eligible_contacts_to_brevo(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts/folders*' => Http::sequence()
                ->push(['folders' => []])
                ->push(['id' => 12], 201),
            'https://api.brevo.com/v3/contacts/lists*' => Http::sequence()
                ->push(['lists' => []])
                ->push(['id' => 34], 201),
            'https://api.brevo.com/v3/contacts' => Http::response(['id' => 1], 201),
        ]);

        $setting = AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
        ]);

        $segment = AudienceSegment::query()->create(['name' => 'Clients atelier']);

        $eligible = AudienceContact::query()->create([
            'first_name' => 'Ivo',
            'last_name' => 'Correia',
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $unsubscribed = AudienceContact::query()->create([
            'email' => 'ana@example.test',
            'accepts_email' => true,
            'unsubscribed_at' => now(),
        ]);

        $segment->contacts()->attach([$eligible->id, $unsubscribed->id]);

        $stats = app(BrevoAudienceService::class)->syncSegment($segment, $setting);

        $this->assertSame([
            'targeted' => 2,
            'synced' => 1,
            'failed' => 0,
            'excluded' => 1,
            'list_id' => 34,
        ], $stats);

        $this->assertSame(12, $setting->refresh()->default_folder_id);
        $this->assertSame(34, $segment->refresh()->brevo_list_id);
        $this->assertSame('synced', $segment->brevo_sync_status);
        $this->assertSame('synced', $eligible->refresh()->brevo_sync_status);
        $this->assertNull($unsubscribed->refresh()->brevo_sync_status);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.brevo.com/v3/contacts'
                && $request['email'] === 'ivo@example.test'
                && $request['listIds'] === [34]
                && $request['attributes'] === [
                    'FNAME' => 'Ivo',
                    'LNAME' => 'Correia',
                ];
        });
    }

    public function test_it_updates_existing_brevo_contact_when_creation_reports_a_duplicate(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/contacts' => Http::response([
                'code' => 'duplicate_parameter',
                'message' => 'Contact already exist',
            ], 400),
            'https://api.brevo.com/v3/contacts/ivo%40example.test' => Http::response(null, 204),
        ]);

        $setting = AudienceBrevoSetting::query()->create([
            'is_enabled' => true,
            'api_key_encrypted' => 'xkeysib-secret',
            'default_folder_id' => 12,
        ]);

        $segment = AudienceSegment::query()->create([
            'name' => 'Clients',
            'brevo_list_id' => 34,
        ]);

        $contact = AudienceContact::query()->create([
            'email' => 'ivo@example.test',
            'accepts_email' => true,
        ]);

        $segment->contacts()->attach($contact);

        $stats = app(BrevoAudienceService::class)->syncSegment($segment, $setting);

        $this->assertSame(1, $stats['synced']);
        $this->assertSame('synced', $contact->refresh()->brevo_sync_status);

        Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.brevo.com/v3/contacts/ivo%40example.test'
            && $request['listIds'] === [34]);
    }
}
