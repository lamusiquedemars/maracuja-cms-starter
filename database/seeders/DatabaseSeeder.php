<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Modules\Gallery\Models\Gallery;
use App\Modules\Gallery\Models\GalleryImage;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'admin@maracuja.test',
        ], [
            'name' => 'Maracuja Admin',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        SiteSetting::query()->updateOrCreate(['id' => 1], [
            'site_name' => 'Maracuja CMS',
            'baseline' => 'Sites vitrines administrables, sobres et sur mesure.',
            'default_seo_title' => 'Maracuja CMS',
            'default_seo_description' => 'Un starter Laravel + Filament pour créer des sites vitrines administrables sans surcharge.',
            'default_og_image_path' => '/demo/theme-system.svg',
            'contact_email' => 'contact@maracuja.test',
            'social_links' => [
                'Instagram' => 'https://instagram.com',
                'LinkedIn' => 'https://linkedin.com',
            ],
        ]);

        collect([
            [
                'key' => 'home.hero.cta_label',
                'label' => 'CTA principal home',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Présenter un projet',
                'help_text' => 'Texte court du bouton principal de la home.',
            ],
            [
                'key' => 'home.hero.secondary_cta_label',
                'label' => 'CTA secondaire home',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Voir les services',
                'help_text' => 'Texte court du bouton secondaire de la home.',
            ],
            [
                'key' => 'home.intro.title',
                'label' => 'Titre introduction home',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Le socle des offres Essence et Signature',
                'help_text' => 'Titre court de la section introduction.',
            ],
            [
                'key' => 'home.intro.text',
                'label' => 'Texte introduction home',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Un site vitrine administré, sans surcharge, avec les modules utiles au client et une base front propre.',
                'help_text' => 'Texte court. Eviter les paragraphes longs.',
            ],
            [
                'key' => 'home.offer.essence.text',
                'label' => 'Carte Essence',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Un site vitrine clair, rapide a produire, avec pages structurées, contact et SEO de base.',
                'help_text' => 'Texte court de la carte Essence sur la home.',
            ],
            [
                'key' => 'home.offer.signature.text',
                'label' => 'Carte Signature',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Une présence plus complète avec actualités, galerie, contenus plus riches et thème affirmé.',
                'help_text' => 'Texte court de la carte Signature sur la home.',
            ],
            [
                'key' => 'home.offer.univers.text',
                'label' => 'Carte Univers',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Un module métier est ajouté seulement quand le client a un vrai besoin spécifique.',
                'help_text' => 'Texte court de la carte Univers sur la home.',
            ],
            [
                'key' => 'home.admin.title',
                'label' => 'Titre section admin',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Une admin simple',
                'help_text' => 'Titre de la section qui présente la logique admin.',
            ],
            [
                'key' => 'home.admin.intro',
                'label' => 'Introduction section admin',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Le client voit ses contenus, pas un cockpit inutile.',
                'help_text' => 'Phrase courte sous le titre de la section admin.',
            ],
            [
                'key' => 'home.admin.quote',
                'label' => 'Citation principe produit',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Moins d’options visibles, plus de structure derrière.',
                'help_text' => 'Citation courte affichée dans la section admin.',
            ],
            [
                'key' => 'home.admin.modules.text',
                'label' => 'Carte modules activables',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Pages, Actualités, Galerie, Contact et Paramètres s’affichent seulement si le projet en a besoin.',
                'help_text' => 'Texte de la carte Modules activables.',
            ],
            [
                'key' => 'home.admin.pages.text',
                'label' => 'Carte pages cadrées',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Le développeur garde la structure en Blade. Le client modifie uniquement les contenus prévus.',
                'help_text' => 'Texte de la carte Pages cadrées.',
            ],
            [
                'key' => 'home.news.title',
                'label' => 'Titre section actualités',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Actualités démo',
                'help_text' => 'Titre de la section actualités sur la home.',
            ],
            [
                'key' => 'home.news.intro',
                'label' => 'Introduction section actualités',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Un module contenu récurrent pour animer le site.',
                'help_text' => 'Phrase courte sous le titre de la section actualités.',
            ],
            [
                'key' => 'home.cta.title',
                'label' => 'Titre CTA final home',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Prêt pour une démo client',
                'help_text' => 'Titre du CTA final de la home.',
            ],
            [
                'key' => 'home.cta.text',
                'label' => 'Texte CTA final home',
                'group' => 'Accueil',
                'type' => 'textarea',
                'value' => 'Cette installation montre le socle Essence / Signature: contenu administrable, front system, media system et admin modulee.',
                'help_text' => 'Texte court du CTA final de la home.',
            ],
            [
                'key' => 'home.cta.label',
                'label' => 'Bouton CTA final home',
                'group' => 'Accueil',
                'type' => 'text',
                'value' => 'Demander une démo',
                'help_text' => 'Libellé du bouton final de la home.',
            ],
            [
                'key' => 'gallery.title',
                'label' => 'Titre galerie',
                'group' => 'Galerie',
                'type' => 'text',
                'value' => 'Galerie demo',
                'help_text' => 'Titre de secours de la section galerie si la galerie n’a pas de titre.',
            ],
            [
                'key' => 'gallery.intro',
                'label' => 'Introduction galerie',
                'group' => 'Galerie',
                'type' => 'textarea',
                'value' => 'Le Media System gere alt, legende, credit, dimensions et lightbox.',
                'help_text' => 'Introduction de secours de la section galerie si la galerie n’a pas d’intro.',
            ],
            [
                'key' => 'articles.public_label',
                'label' => 'Libellé public articles',
                'group' => 'Articles',
                'type' => 'text',
                'value' => 'Articles',
                'help_text' => 'Nom public du module Articles, utilisé dans la navigation et les pages.',
            ],
            [
                'key' => 'articles.index.subtitle',
                'label' => 'Sous-titre page articles',
                'group' => 'Articles',
                'type' => 'textarea',
                'value' => 'Articles éditoriaux publiés sur le site.',
                'help_text' => 'Sous-titre et description SEO de la liste des articles.',
            ],
            [
                'key' => 'services.hero.cta_label',
                'label' => 'CTA hero services',
                'group' => 'Services',
                'type' => 'text',
                'value' => 'Parler du projet',
                'help_text' => 'Libellé du bouton dans le hero Services.',
            ],
            [
                'key' => 'services.offers.title',
                'label' => 'Titre section offres',
                'group' => 'Services',
                'type' => 'text',
                'value' => 'Trois niveaux, un même socle',
                'help_text' => 'Titre de la section des trois offres.',
            ],
            [
                'key' => 'services.offers.intro',
                'label' => 'Introduction section offres',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'La différence se joue sur la richesse du contenu, les modules actifs et le degré de personnalisation.',
                'help_text' => 'Phrase courte sous le titre des offres.',
            ],
            [
                'key' => 'services.essence.price',
                'label' => 'Prix Essence',
                'group' => 'Services',
                'type' => 'price',
                'value' => 'À partir de 1500',
                'help_text' => 'Prix ou mention courte affichée sur la carte Essence.',
            ],
            [
                'key' => 'services.essence.description',
                'label' => 'Description Essence',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Pages essentielles, contact, SEO de base, thème simple et administration limitée aux contenus utiles.',
                'help_text' => 'Texte de la carte Essence.',
            ],
            [
                'key' => 'services.signature.price',
                'label' => 'Prix Signature',
                'group' => 'Services',
                'type' => 'price',
                'value' => 'Sur devis cadre',
                'help_text' => 'Prix ou mention courte affichée sur la carte Signature.',
            ],
            [
                'key' => 'services.signature.description',
                'label' => 'Description Signature',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Structure plus riche, actualités, galerie, sections de preuve, CTA, media system et finitions thème.',
                'help_text' => 'Texte de la carte Signature.',
            ],
            [
                'key' => 'services.univers.price',
                'label' => 'Prix Univers',
                'group' => 'Services',
                'type' => 'price',
                'value' => 'Sur devis métier',
                'help_text' => 'Prix ou mention courte affichée sur la carte Univers.',
            ],
            [
                'key' => 'services.univers.description',
                'label' => 'Description Univers',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Module métier client, catalogue (avec ou sans paiement), workflow spécifique ou intégration externe selon le besoin.',
                'help_text' => 'Texte de la carte Univers.',
            ],
            [
                'key' => 'services.common.title',
                'label' => 'Titre section commun',
                'group' => 'Services',
                'type' => 'text',
                'value' => 'Ce qui reste commun',
                'help_text' => 'Titre de la section des socles communs.',
            ],
            [
                'key' => 'services.common.tech.text',
                'label' => 'Texte socle technique',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Laravel, Filament, modules activables, migrations, seeders, tests et conventions de livraison.',
                'help_text' => 'Texte de la carte Socle technique.',
            ],
            [
                'key' => 'services.common.front.text',
                'label' => 'Texte socle front',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Composants Blade, CSS maison, JS progressif, media system et thèmes clients.',
                'help_text' => 'Texte de la carte Socle front.',
            ],
            [
                'key' => 'services.cta.title',
                'label' => 'Titre CTA services',
                'group' => 'Services',
                'type' => 'text',
                'value' => 'Une offre simple à expliquer',
                'help_text' => 'Titre du CTA de la page Services.',
            ],
            [
                'key' => 'services.cta.text',
                'label' => 'Texte CTA services',
                'group' => 'Services',
                'type' => 'textarea',
                'value' => 'Le client choisit un niveau de site. Le développeur garde un socle commun versionné.',
                'help_text' => 'Texte du CTA de la page Services.',
            ],
            [
                'key' => 'services.cta.label',
                'label' => 'Bouton CTA services',
                'group' => 'Services',
                'type' => 'text',
                'value' => 'Présenter un projet',
                'help_text' => 'Libellé du bouton CTA de la page Services.',
            ],
        ])->each(fn (array $slot) => ContentSlot::query()->updateOrCreate(
            ['key' => $slot['key']],
            $slot + ['is_locked' => true],
        ));

        Page::query()->updateOrCreate(['slug' => 'accueil'], [
            'title' => 'Accueil',
            'template' => 'landing',
            'type' => Page::TYPE_SYSTEM,
            'excerpt' => 'Une démo du starter Maracuja CMS.',
            'hero_title' => 'Un site clair. Une administration simple.',
            'hero_subtitle' => 'Maracuja CMS fournit un socle Laravel + Filament pour créer des sites vitrines administrables, sans tableau de bord inutile.',
            'content' => null,
            'seo_title' => 'Maracuja CMS - Starter vitrine administrable',
            'seo_description' => 'Starter Laravel + Filament pour sites vitrines administrables.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Page::query()->where('slug', 'methode')->delete();

        Page::query()->updateOrCreate(['slug' => 'mentions-legales'], [
            'title' => 'Mentions légales',
            'template' => 'default',
            'type' => Page::TYPE_TEXT,
            'excerpt' => 'Informations légales de démonstration.',
            'hero_title' => 'Mentions légales',
            'hero_subtitle' => 'Un exemple de page texte cadrée, éditable sans page builder.',
            'content' => '<p>Ce site est une démonstration du starter Maracuja CMS.</p><p>Éditeur: Maracuja CMS. Contact: contact@maracuja.test.</p>',
            'seo_title' => 'Mentions légales - Maracuja CMS',
            'seo_description' => 'Mentions légales de démonstration du starter Maracuja CMS.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Page::query()->updateOrCreate(['slug' => 'services'], [
            'title' => 'Services',
            'template' => 'services',
            'type' => Page::TYPE_SYSTEM,
            'excerpt' => 'Les offres type portées par le starter.',
            'hero_title' => 'Des sites vitrines administrables, sans usine à gaz',
            'hero_subtitle' => 'Essence pour aller vite et bien. Signature pour une présence plus complète. Univers couvre les besoins métier cadrés.',
            'content' => null,
            'seo_title' => 'Services - Maracuja CMS',
            'seo_description' => 'Offres Essence, Signature et Univers pour sites vitrines administrables.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Page::query()->updateOrCreate(['slug' => 'contact'], [
            'title' => 'Contact',
            'template' => 'contact',
            'type' => Page::TYPE_SYSTEM,
            'excerpt' => 'Un formulaire simple pour envoyer un message.',
            'hero_title' => 'Contact',
            'hero_subtitle' => 'Un formulaire simple pour envoyer un message.',
            'content' => null,
            'seo_title' => 'Contact - Maracuja CMS',
            'seo_description' => 'Contacter Maracuja CMS.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Page::query()->updateOrCreate(['slug' => 'actualites'], [
            'title' => 'Actualités',
            'template' => 'module',
            'type' => Page::TYPE_MODULE,
            'excerpt' => 'Les contenus récurrents publiés depuis l’admin.',
            'hero_title' => 'Actualités',
            'hero_subtitle' => 'Les contenus récurrents publiés depuis l’admin.',
            'content' => null,
            'seo_title' => 'Actualités - Maracuja CMS',
            'seo_description' => 'Dernières actualités publiées.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        NewsPost::query()->updateOrCreate(['slug' => 'maracuja-cms-v1'], [
            'title' => 'Maracuja CMS V1',
            'excerpt' => 'Le starter prend forme avec Pages, Actualités, Galerie et Contact.',
            'content' => '<p>Cette première version sert de base aux sites vitrines administrables.</p>',
            'is_published' => true,
            'is_pinned' => true,
            'has_detail_page' => true,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        NewsPost::query()->updateOrCreate(['slug' => 'front-system'], [
            'title' => 'Un front system maison',
            'excerpt' => 'Le starter embarque ses composants CSS, Blade et JS sans framework visible.',
            'content' => '<p>Le socle front permet de composer des pages propres sans repartir d’un CSS spécifique à chaque client.</p>',
            'is_published' => true,
            'is_pinned' => false,
            'has_detail_page' => true,
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDays(29),
        ]);

        NewsPost::query()->updateOrCreate(['slug' => 'admin-simplifiee'], [
            'title' => 'Une admin limitée aux modules utiles',
            'excerpt' => 'Filament affiche seulement les sections activées pour le projet client.',
            'content' => '<p>Le client garde un tableau de bord lisible, orienté contenu et sans surcharge inutile.</p>',
            'is_published' => true,
            'is_pinned' => false,
            'has_detail_page' => false,
            'published_at' => now()->subDays(2),
            'expires_at' => now()->addDays(28),
        ]);

        SiteNotice::query()->updateOrCreate(['title' => 'Annonce démo'], [
            'message' => 'Ce bloc est une annonce courte indépendante des pages. Si aucune annonce active n’existe, rien ne s’affiche.',
            'link_label' => 'Contacter',
            'link_url' => '/contact',
            'placement' => 'home',
            'tone' => 'info',
            'is_published' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(15),
        ]);

        $homeGallery = Gallery::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Galerie principale',
            'intro' => 'Quelques images du projet.',
            'position' => 1,
            'is_published' => true,
        ]);

        GalleryImage::query()->updateOrCreate(['title' => 'Admin simple'], [
            'gallery_id' => $homeGallery->id,
            'caption' => 'Une administration limitée aux modules activés.',
            'alt_text' => 'Interface d’administration simple limitée aux modules utiles.',
            'credit' => 'Maracuja CMS',
            'image_path' => '/demo/admin-simple.svg',
            'width' => 1200,
            'height' => 800,
            'position' => 1,
            'is_published' => true,
        ]);

        GalleryImage::query()->updateOrCreate(['title' => 'Composants front'], [
            'gallery_id' => $homeGallery->id,
            'caption' => 'Des sections, cartes, CTA, galeries et variantes réutilisables.',
            'alt_text' => 'Exemple abstrait de composants front organisés.',
            'credit' => 'Maracuja CMS',
            'image_path' => '/demo/front-system.svg',
            'width' => 1200,
            'height' => 800,
            'position' => 2,
            'is_published' => true,
        ]);

        GalleryImage::query()->updateOrCreate(['title' => 'Thèmes clients'], [
            'gallery_id' => $homeGallery->id,
            'caption' => 'Une structure commune peut prendre plusieurs ambiances.',
            'alt_text' => 'Variantes de thèmes pour sites clients.',
            'credit' => 'Maracuja CMS',
            'image_path' => '/demo/theme-system.svg',
            'width' => 1200,
            'height' => 800,
            'position' => 3,
            'is_published' => true,
        ]);

    }
}
