<?php

namespace Tests\Unit;

use App\Modules\Audience\Models\AudienceContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceContactSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resubscribing_a_contact_clears_unsubscribed_at(): void
    {
        $contact = AudienceContact::query()->create([
            'email' => 'alice@example.test',
            'accepts_email' => true,
        ]);

        $contact->unsubscribe();

        $this->assertFalse($contact->refresh()->accepts_email);
        $this->assertNotNull($contact->unsubscribed_at);

        $contact->forceFill(['accepts_email' => true])->save();

        $this->assertTrue($contact->refresh()->accepts_email);
        $this->assertNull($contact->unsubscribed_at);
    }

    public function test_contact_with_hard_bounce_cannot_receive_segment_email(): void
    {
        $contact = AudienceContact::query()->create([
            'email' => 'alice@example.test',
            'accepts_email' => true,
            'hard_bounced_at' => now(),
        ]);

        $this->assertFalse($contact->canReceiveSegmentEmail());
    }
}
