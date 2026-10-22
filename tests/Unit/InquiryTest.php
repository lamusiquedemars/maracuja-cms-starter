<?php

namespace Tests\Unit;

use Maracuja\SiteCore\Contact\Enums\InquiryStatus;
use App\Modules\Inquiries\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_read_sets_read_date_once(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'message' => 'Bonjour.',
            'status' => InquiryStatus::New,
        ]);

        $inquiry->markRead();
        $firstReadDate = $inquiry->refresh()->read_at;

        $inquiry->markRead();

        $this->assertNotNull($firstReadDate);
        $this->assertTrue($firstReadDate->equalTo($inquiry->refresh()->read_at));
    }

    public function test_move_to_handled_sets_status_read_date_and_handled_date(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'message' => 'Bonjour.',
            'status' => InquiryStatus::New,
        ]);

        $inquiry->moveTo(InquiryStatus::Handled);
        $inquiry->refresh();

        $this->assertSame(InquiryStatus::Handled, $inquiry->status);
        $this->assertNotNull($inquiry->read_at);
        $this->assertNotNull($inquiry->handled_at);
        $this->assertNull($inquiry->archived_at);
    }

    public function test_move_to_archived_sets_archive_date(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'message' => 'Bonjour.',
            'status' => InquiryStatus::ToHandle,
        ]);

        $inquiry->moveTo(InquiryStatus::Archived);
        $inquiry->refresh();

        $this->assertSame(InquiryStatus::Archived, $inquiry->status);
        $this->assertNotNull($inquiry->read_at);
        $this->assertNotNull($inquiry->archived_at);
    }

    public function test_mark_waiting_customer_sets_status_and_read_date(): void
    {
        $inquiry = Inquiry::query()->create([
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'message' => 'Bonjour.',
            'status' => InquiryStatus::New,
        ]);

        $inquiry->markWaitingCustomer();
        $inquiry->refresh();

        $this->assertSame(InquiryStatus::WaitingCustomer, $inquiry->status);
        $this->assertNotNull($inquiry->read_at);
    }
}
