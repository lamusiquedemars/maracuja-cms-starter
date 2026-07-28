<?php

namespace Tests\Feature;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Contacts\Actions\ResolveContact;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_same_contact_from_audience_and_an_inquiry(): void
    {
        $audienceContact = AudienceContact::query()->create([
            'first_name' => 'Lara',
            'email' => 'LARA@example.test',
            'accepts_email' => true,
        ]);

        $inquiry = Inquiry::query()->create([
            'name' => 'Lara Martin',
            'email' => 'lara@EXAMPLE.test',
            'phone' => '+33 6 12 34 56 78',
            'message' => 'Bonjour',
        ]);

        $this->assertSame($audienceContact->contact_id, $inquiry->contact_id);
        $this->assertSame(1, Contact::query()->count());
        $this->assertSame('+33612345678', $inquiry->contact->normalized_phone);
    }

    public function test_it_can_identify_a_contact_by_normalized_phone_without_email(): void
    {
        $first = ResolveContact::run([
            'display_name' => 'Camille',
            'phone' => '+33 (0)6 12 34 56 78',
            'source' => 'conversation',
        ]);

        $second = ResolveContact::run([
            'phone' => '+330612345678',
            'email' => 'camille@example.test',
            'source' => 'inquiry',
        ]);

        $this->assertTrue($first->is($second));
        $this->assertSame('camille@example.test', $second->refresh()->normalized_email);
    }

    public function test_marketing_consent_remains_owned_by_audience(): void
    {
        $audienceContact = AudienceContact::query()->create([
            'email' => 'no-marketing@example.test',
            'accepts_email' => false,
        ]);

        $this->assertFalse($audienceContact->accepts_email);
        $this->assertArrayNotHasKey('accepts_email', $audienceContact->contact->getAttributes());
    }
}
