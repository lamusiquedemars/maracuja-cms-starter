<?php

namespace Tests\Unit;

use App\Support\Audience\CreateContactFromInquiry;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Contact\Enums\InquiryStatus;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateContactFromInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_contact_from_an_inquiry(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Lara',
            'email' => 'LARA@VEL.FR',
            'message' => 'Bonjour',
            'status' => InquiryStatus::New,
        ]);

        $result = CreateContactFromInquiry::run($inquiry);

        $this->assertTrue($result['created']);
        $this->assertSame('lara@vel.fr', $result['contact']->email);
        $this->assertSame('Lara', $result['contact']->first_name);
        $this->assertSame(1, AudienceContact::query()->count());
    }

    public function test_it_does_not_duplicate_contact_with_same_email(): void
    {
        AudienceContact::query()->create([
            'first_name' => 'Lara',
            'email' => 'lara@vel.fr',
            'accepts_email' => true,
        ]);

        $inquiry = Inquiry::query()->create([
            'name' => 'Lara Dup',
            'email' => 'LARA@VEL.FR',
            'message' => 'Bonjour',
            'status' => InquiryStatus::New,
        ]);

        $result = CreateContactFromInquiry::run($inquiry);

        $this->assertFalse($result['created']);
        $this->assertSame('lara@vel.fr', $result['contact']->email);
        $this->assertSame(1, AudienceContact::query()->count());
    }
}
