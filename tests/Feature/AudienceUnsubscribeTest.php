<?php

namespace Tests\Feature;

use App\Modules\Audience\Models\AudienceContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_unsubscribe_from_segment_messages(): void
    {
        config(['maracuja.modules.audience' => true]);

        $contact = AudienceContact::query()->create([
            'email' => 'alice@example.test',
            'accepts_email' => true,
        ]);

        $this->get(route('audience.unsubscribe', ['token' => $contact->unsubscribe_token]))
            ->assertOk()
            ->assertSee('Désinscription confirmée');

        $this->assertFalse($contact->refresh()->accepts_email);
        $this->assertNotNull($contact->unsubscribed_at);
    }
}
