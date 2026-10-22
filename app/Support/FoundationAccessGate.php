<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Maracuja\SiteCore\Foundation\Contracts\FoundationAccessGate as FoundationAccessGateContract;
use Maracuja\SiteCore\Foundation\Enums\FoundationProfile;

/** Maps the generic Foundation capabilities to this site's explicit user profile. */
final class FoundationAccessGate implements FoundationAccessGateContract
{
    public function allows(string $ability): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        if (! $user->canEditContent()) {
            return false;
        }

        if ($user->role === User::ROLE_SITE_MANAGER && FoundationProfile::SiteManager->allows($ability)) {
            return match ($ability) {
                'foundation.content.manage' => $user->canAccessContent('content_slots'),
                'foundation.notices.manage' => $user->canAccessContent('notices'),
                'foundation.pages.manage' => $user->canAccessContent('pages'),
                'media.manage' => $user->canAccessContent('media'),
                'gallery.manage' => $user->canAccessContent('gallery'),
                'publication.news.manage' => $user->canAccessContent('news'),
                'publication.articles.manage' => $user->canAccessContent('articles'),
                'events.manage', 'events.participants.manage' => $user->canAccessContent('events'),
                'events.venues.manage' => $user->canAccessContent('venues'),
                default => false,
            };
        }

        return match ($ability) {
            'foundation.content.manage' => $user->canAccessContent('content_slots'),
            'foundation.notices.manage' => $user->canAccessContent('notices'),
            'foundation.pages.manage' => $user->canAccessContent('pages'),
            'media.manage' => $user->canAccessContent('media'),
            'gallery.manage' => $user->canAccessContent('gallery'),
            'publication.news.manage' => $user->canAccessContent('news'),
            'publication.articles.manage' => $user->canAccessContent('articles'),
            'events.manage', 'events.participants.manage' => $user->canAccessContent('events'),
            'events.venues.manage' => $user->canAccessContent('venues'),
            // SEO and global settings stay reserved to the site administrator.
            'foundation.settings.manage', 'foundation.seo.manage' => false,
            default => false,
        };
    }
}
