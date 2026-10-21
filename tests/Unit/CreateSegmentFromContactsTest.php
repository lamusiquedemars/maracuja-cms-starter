<?php

namespace Tests\Unit;

use Maracuja\SiteCore\Audience\Actions\CreateSegmentFromContacts;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Audience\Models\AudienceSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CreateSegmentFromContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_segment_and_attaches_selected_contacts(): void
    {
        $contactA = AudienceContact::query()->create([
            'email' => 'a@example.test',
            'accepts_email' => true,
        ]);

        $contactB = AudienceContact::query()->create([
            'email' => 'b@example.test',
            'accepts_email' => true,
        ]);

        $segment = CreateSegmentFromContacts::run(
            name: 'Clients violon',
            description: 'Location active',
            contactIds: new Collection([$contactA->id, $contactB->id, $contactB->id]),
        );

        $this->assertSame('Clients violon', $segment->name);
        $this->assertSame('Location active', $segment->description);
        $this->assertCount(2, $segment->contacts()->get());
    }

    public function test_contacts_can_be_added_to_and_removed_from_existing_segments_in_bulk(): void
    {
        $segment = AudienceSegment::query()->create([
            'name' => 'Clients atelier',
        ]);

        $contactA = AudienceContact::query()->create([
            'email' => 'a@example.test',
            'accepts_email' => true,
        ]);

        $contactB = AudienceContact::query()->create([
            'email' => 'b@example.test',
            'accepts_email' => false,
        ]);

        $segment->contacts()->syncWithoutDetaching([$contactA->id, $contactB->id]);

        $this->assertSame(2, $segment->contacts()->count());
        $this->assertSame(1, $segment->contacts()->where('accepts_email', true)->whereNull('unsubscribed_at')->count());

        $segment->contacts()->detach([$contactB->id]);

        $this->assertSame(1, $segment->contacts()->count());
        $this->assertTrue($segment->contacts()->whereKey($contactA->id)->exists());
        $this->assertFalse($segment->contacts()->whereKey($contactB->id)->exists());
    }
}
