<?php

declare(strict_types=1);

namespace App\Support;

use Maracuja\SiteCore\Foundation\Contracts\PublicPageUrlGenerator;
use Maracuja\SiteCore\Foundation\Models\Page;

final class FoundationPublicPageUrlGenerator implements PublicPageUrlGenerator
{
    public function url(Page $page): ?string
    {
        return match ($page->slug) {
            'accueil' => route('home'),
            'actualites' => route('news.index'),
            'contact' => Modules::enabled('contact_form') ? route('contact') : null,
            default => route('pages.show', $page->slug),
        };
    }
}
