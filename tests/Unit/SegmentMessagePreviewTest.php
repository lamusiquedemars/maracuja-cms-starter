<?php

namespace Tests\Unit;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SegmentMessagePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_render_a_real_unsubscribe_link(): void
    {
        $segment = AudienceSegment::query()->create(['name' => 'Clients en location']);

        $contact = AudienceContact::query()->create([
            'email' => 'alice@example.test',
            'accepts_email' => true,
        ]);

        $segment->contacts()->attach($contact);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Information',
            'body' => '<p>Bonjour les amis,</p>',
        ]);

        $html = view('filament.audience.segment-message-preview', [
            'segmentMessage' => $message,
        ])->render();

        $this->assertStringContainsString('Lien de désinscription masqué dans l’aperçu.', $html);
        $this->assertStringNotContainsString($contact->unsubscribe_token, $html);
    }

    public function test_preview_rewrites_public_storage_images_without_reuploading(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('campaigns/summer.jpg', 'image-bytes');

        $segment = AudienceSegment::query()->create(['name' => 'Clients']);

        $message = SegmentMessage::query()->create([
            'audience_segment_id' => $segment->id,
            'subject' => 'Information',
            'body' => '<p>Bonjour</p><img src="http://example.local/storage/campaigns/summer.jpg" alt="Atelier">',
        ]);

        $html = view('filament.audience.segment-message-preview', [
            'segmentMessage' => $message,
        ])->render();

        $this->assertStringContainsString(url('/storage/campaigns/summer.jpg'), $html);
        $this->assertSame(['campaigns/summer.jpg'], Storage::disk('public')->allFiles());
    }
}
