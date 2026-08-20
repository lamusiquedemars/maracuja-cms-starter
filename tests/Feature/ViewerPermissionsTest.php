<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\ContentSlots\ContentSlotResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\NewsPosts\NewsPostResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\SiteNotices\SiteNoticeResource;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Resources\Venues\VenueResource;
use App\Models\User;
use App\Modules\Appointments\Filament\Resources\AppointmentSettings\AppointmentSettingResource;
use App\Modules\Audience\Filament\Resources\AudienceBrevoSettings\AudienceBrevoSettingResource;
use App\Modules\Audience\Filament\Resources\AudienceContacts\AudienceContactResource;
use App\Modules\Audience\Filament\Resources\AudienceSegments\AudienceSegmentResource;
use App\Modules\Audience\Filament\Resources\SegmentMessages\SegmentMessageResource;
use App\Modules\Contacts\Filament\Resources\Contacts\ContactResource;
use App\Modules\Conversations\Filament\Resources\Conversations\ConversationResource;
use App\Modules\Conversations\Filament\Resources\ConversationSettings\ConversationSettingResource;
use App\Modules\Inquiries\Filament\Resources\Inquiries\InquiryResource;
use App\Modules\Media\Filament\Resources\MediaAssets\MediaAssetResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ViewerPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_is_denied_every_write_operation_for_every_resource(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_admin' => false,
        ]);

        foreach ($this->resources() as $resource) {
            $model = $resource::getModel();

            foreach (['create', 'update', 'delete', 'restore', 'forceDelete', 'replicate'] as $ability) {
                $this->assertFalse(
                    Gate::forUser($viewer)->allows($ability, $model),
                    "A viewer must not be able to {$ability} {$model}.",
                );
            }
        }
    }

    /** @return array<class-string> */
    private function resources(): array
    {
        return [
            ArticleResource::class,
            ContentSlotResource::class,
            EventResource::class,
            GalleryResource::class,
            NewsPostResource::class,
            PageResource::class,
            SiteNoticeResource::class,
            SiteSettingResource::class,
            VenueResource::class,
            AppointmentSettingResource::class,
            AudienceBrevoSettingResource::class,
            AudienceContactResource::class,
            AudienceSegmentResource::class,
            SegmentMessageResource::class,
            ContactResource::class,
            ConversationResource::class,
            ConversationSettingResource::class,
            InquiryResource::class,
            MediaAssetResource::class,
        ];
    }
}
