<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\ContentSlots\ContentSlotResource;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\NewsPosts\NewsPostResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\SiteNotices\SiteNoticeResource;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Models\User;
use App\Modules\Media\Filament\Resources\MediaAssets\MediaAssetResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Maracuja\SiteCore\Events\Filament\Resources\Events\EventResource;
use Maracuja\SiteCore\Events\Filament\Resources\Venues\VenueResource;
use Maracuja\SiteCore\Contact\Filament\Resources\Inquiries\InquiryResource;
use Maracuja\SiteCore\Audience\Filament\Resources\AudienceContacts\AudienceContactResource;
use Maracuja\SiteCore\Audience\Filament\Resources\AudienceSegments\AudienceSegmentResource;
use Maracuja\SiteCore\Audience\Filament\Resources\SegmentMessages\SegmentMessageResource;
use Maracuja\SiteCore\Brevo\Filament\Resources\AudienceBrevoSettings\AudienceBrevoSettingResource;
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
            AudienceBrevoSettingResource::class,
            AudienceContactResource::class,
            AudienceSegmentResource::class,
            SegmentMessageResource::class,
            InquiryResource::class,
            MediaAssetResource::class,
        ];
    }
}
