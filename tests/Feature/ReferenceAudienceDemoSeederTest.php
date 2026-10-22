<?php

namespace Tests\Feature;

use App\Modules\Contacts\Models\Contact;
use Database\Seeders\ReferenceAudienceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Audience\Models\AudienceSegment;
use Maracuja\SiteCore\Audience\Models\SegmentMessage;
use Tests\TestCase;

class ReferenceAudienceDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_contacts_with_optional_linked_subscriptions(): void
    {
        $this->seed(ReferenceAudienceDemoSeeder::class);

        $this->assertSame(3, Contact::query()->count());
        $this->assertSame(2, AudienceContact::query()->count());
        $this->assertSame(3, AudienceSegment::query()->count());
        $this->assertSame(1, SegmentMessage::query()->count());

        $subscriber = AudienceContact::query()->where('email', 'claire.durand@example.test')->firstOrFail();

        $this->assertNotNull($subscriber->contact_id);
        $this->assertSame('Claire', $subscriber->contact->first_name);
        $this->assertSame(['Clients', 'Newsletter générale'], $subscriber->segments()->orderBy('name')->pluck('name')->all());
        $this->assertSame(SegmentMessage::STATUS_DRAFT, SegmentMessage::query()->sole()->status);
    }
}
