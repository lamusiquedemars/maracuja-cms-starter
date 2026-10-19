<?php

namespace App\Support;

use App\Models\User;
use App\Modules\Appointments\Models\AppointmentSetting;
use App\Modules\Articles\Models\Article;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Contacts\Models\Contact;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Events\Models\Event;
use App\Modules\Inquiries\Models\Inquiry;
use App\Modules\Media\Models\MediaAsset;
use Maracuja\SiteCore\Media\Models\MediaAsset as CoreMediaAsset;
use Maracuja\SiteCore\Gallery\Models\Gallery;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Modules\Venues\Models\Venue;

class ContentPermissions
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'pages' => 'Pages',
            'notices' => 'Annonces courtes',
            'content_slots' => 'Contenus courts',
            'news' => 'Actualités',
            'articles' => 'Articles',
            'inquiries' => 'Demandes reçues',
            'contacts' => 'Contacts',
            'media' => 'Médias',
            'gallery' => 'Galeries',
            'events' => 'Événements',
            'venues' => 'Lieux',
            'appointments' => 'Rendez-vous',
            'conversations' => 'Conversations',
            'audience' => 'Audience et campagnes',
        ];
    }

    public static function keyForModel(?string $model): ?string
    {
        return match ($model) {
            Page::class => 'pages',
            SiteNotice::class => 'notices',
            ContentSlot::class => 'content_slots',
            NewsPost::class => 'news',
            Article::class => 'articles',
            Inquiry::class => 'inquiries',
            Contact::class => 'contacts',
            MediaAsset::class, CoreMediaAsset::class => 'media',
            Gallery::class => 'gallery',
            Event::class => 'events',
            Venue::class => 'venues',
            AppointmentSetting::class => 'appointments',
            Conversation::class, ConversationSetting::class => 'conversations',
            AudienceContact::class, AudienceSegment::class, SegmentMessage::class, AudienceBrevoSetting::class => 'audience',
            SiteSetting::class => 'site_settings',
            default => null,
        };
    }

    public static function allows(User $user, ?string $model): bool
    {
        $permission = self::keyForModel($model);

        return $permission === null || $user->canAccessContent($permission);
    }
}
