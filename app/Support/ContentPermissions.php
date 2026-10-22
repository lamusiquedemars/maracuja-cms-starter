<?php

namespace App\Support;

use App\Models\User;
use App\Modules\Appointments\Models\AppointmentSetting;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationSetting;
use App\Modules\Inquiries\Models\Inquiry;
use Maracuja\SiteCore\Media\Models\MediaAsset;
use Maracuja\SiteCore\Events\Models\Event;
use Maracuja\SiteCore\Events\Models\Participant;
use Maracuja\SiteCore\Events\Models\Venue;
use Maracuja\SiteCore\Foundation\Models\ContentSlot;
use Maracuja\SiteCore\Foundation\Models\Page;
use Maracuja\SiteCore\Foundation\Models\SiteNotice;
use Maracuja\SiteCore\Foundation\Models\SiteSetting;
use Maracuja\SiteCore\Gallery\Models\Gallery;
use Maracuja\SiteCore\Media\Models\MediaAsset as CoreMediaAsset;
use Maracuja\SiteCore\Publication\Models\Article;
use Maracuja\SiteCore\Publication\Models\NewsPost;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Audience\Models\AudienceSegment;
use Maracuja\SiteCore\Audience\Models\SegmentMessage;
use Maracuja\SiteCore\Brevo\Models\AudienceBrevoSetting;

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
            Participant::class => 'events',
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
