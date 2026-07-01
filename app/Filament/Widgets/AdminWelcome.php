<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\ContentSlots\ContentSlotResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Galleries\GalleryResource;
use App\Filament\Resources\NewsPosts\NewsPostResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\SiteNotices\SiteNoticeResource;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Modules\Inquiries\Filament\Resources\Inquiries\InquiryResource;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class AdminWelcome extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.admin-welcome';

    protected function getViewData(): array
    {
        $settings = SiteSetting::current();

        return [
            'siteName' => $settings->site_name ?: 'Maracuja CMS',
            'baseline' => $settings->baseline,
            'primaryActions' => $this->primaryActions(),
            'secondaryActions' => $this->secondaryActions(),
        ];
    }

    private function primaryActions(): array
    {
        return array_values(array_filter([
            $this->moduleAction('pages', PageResource::class, 'Mettre à jour une page', 'Titres, textes principaux et SEO.'),
            $this->moduleAction('content_slots', ContentSlotResource::class, 'Modifier un texte court', 'Accroches, phrases de section et micro-contenus.'),
            $this->moduleAction('inquiries', InquiryResource::class, 'Traiter les demandes', 'Messages entrants, réponses et suivi client.', 'inquiries'),
            $this->moduleAction('site_settings', SiteSettingResource::class, 'Régler les informations du site', 'Nom, contact, réseaux, SEO et visuels par défaut.'),
        ]));
    }

    private function secondaryActions(): array
    {
        return array_values(array_filter([
            $this->moduleAction('news', NewsPostResource::class, 'Actualités', 'Publier une annonce ou une information courte.'),
            $this->moduleAction('articles', ArticleResource::class, 'Articles', 'Préparer des contenus longs et structurés.'),
            $this->moduleAction('notices', SiteNoticeResource::class, 'Annonce courte', 'Afficher un message temporaire sur le site.'),
            $this->moduleAction('gallery', GalleryResource::class, 'Galeries', 'Organiser les images visibles en ligne.'),
            $this->moduleAction('events', EventResource::class, 'Événements', 'Mettre à jour les dates, lieux et programmations.'),
        ]));
    }

    /**
     * @param  class-string  $resource
     */
    private function moduleAction(string $module, string $resource, string $label, string $description, ?string $table = null): ?array
    {
        if (! Modules::enabled($module)) {
            return null;
        }

        if ($table !== null && ! Schema::hasTable($table)) {
            return null;
        }

        if (! method_exists($resource, 'getUrl') || ! $resource::canAccess()) {
            return null;
        }

        return [
            'label' => $label,
            'description' => $description,
            'url' => $resource::getUrl(),
            'external' => false,
        ];
    }
}
