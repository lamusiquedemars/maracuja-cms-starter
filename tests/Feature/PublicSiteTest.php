<?php

namespace Tests\Feature;

use App\Modules\ContactForm\Mail\ContactMessageConfirmation;
use App\Modules\ContactForm\Mail\ContactMessageReceived;
use App\Modules\Articles\Models\Article;
use App\Modules\Inquiries\Models\Inquiry;
use App\Modules\ContentSlots\Models\ContentSlot;
use App\Modules\Gallery\Models\Gallery;
use App\Modules\Gallery\Models\GalleryImage;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_page_renders_the_starter_pitch(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'hero_title' => 'Un site clair',
            'hero_subtitle' => 'Une administration simple',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Un site clair')
            ->assertSee('Essence');
    }

    public function test_services_page_uses_dedicated_demo_template(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'template' => 'services',
            'hero_title' => 'Des sites vitrines administrables',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/services')
            ->assertOk()
            ->assertSee('Trois niveaux')
            ->assertSee('À partir de 1500');
    }

    public function test_services_page_uses_content_slot_for_price(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'template' => 'services',
            'hero_title' => 'Des sites vitrines administrables',
            'is_published' => true,
            'published_at' => now(),
        ]);

        ContentSlot::query()->create([
            'key' => 'services.essence.price',
            'label' => 'Prix Essence',
            'group' => 'Services',
            'type' => 'price',
            'value' => 'À partir de 1800',
            'is_locked' => true,
        ]);

        $this->get('/services')
            ->assertOk()
            ->assertSee('À partir de 1800')
            ->assertDontSee('À partir de 1500');
    }

    public function test_home_hides_contact_and_services_ctas_when_targets_are_unavailable(): void
    {
        config(['maracuja.modules.contact_form' => false]);

        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'hero_title' => 'Un site clair',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('href="http://localhost/contact"', false)
            ->assertDontSee('href="http://localhost/services"', false);
    }

    public function test_services_page_hides_contact_ctas_when_contact_module_is_disabled(): void
    {
        config(['maracuja.modules.contact_form' => false]);

        SiteSetting::current();

        Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'template' => 'services',
            'type' => Page::TYPE_SYSTEM,
            'hero_title' => 'Des sites vitrines administrables',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/services')
            ->assertOk()
            ->assertDontSee('href="http://localhost/contact"', false);
    }

    public function test_home_gallery_uses_configured_layout(): void
    {
        config(['maracuja.gallery.layout' => 'featured']);

        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $gallery = Gallery::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Galerie home',
            'is_published' => true,
        ]);

        GalleryImage::query()->create([
            'title' => 'Image démo',
            'gallery_id' => $gallery->id,
            'caption' => 'Légende démo',
            'image_path' => '/demo/admin-simple.svg',
            'width' => 1200,
            'height' => 800,
            'position' => 1,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('showcase--featured')
            ->assertSee('/demo/admin-simple.svg');
    }

    public function test_home_uses_only_the_configured_gallery(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $homeGallery = Gallery::query()->updateOrCreate(['slug' => 'home'], [
            'title' => 'Galerie home',
            'is_published' => true,
        ]);

        $otherGallery = Gallery::query()->create([
            'title' => 'Autre galerie',
            'slug' => 'autre',
            'is_published' => true,
        ]);

        GalleryImage::query()->create([
            'title' => 'Image home',
            'gallery_id' => $homeGallery->id,
            'image_path' => '/demo/admin-simple.svg',
            'position' => 1,
            'is_published' => true,
        ]);

        GalleryImage::query()->create([
            'title' => 'Image autre',
            'gallery_id' => $otherGallery->id,
            'image_path' => '/demo/theme-system.svg',
            'position' => 1,
            'is_published' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('/demo/admin-simple.svg')
            ->assertDontSee('Image autre');
    }

    public function test_home_renders_active_notice_only(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'is_published' => true,
            'published_at' => now(),
        ]);

        SiteNotice::query()->create([
            'title' => 'Horaires d’été',
            'message' => 'Ouverture exceptionnelle sur rendez-vous.',
            'placement' => 'home',
            'tone' => 'warning',
            'is_published' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        SiteNotice::query()->create([
            'title' => 'Ancienne annonce',
            'message' => 'Message expire.',
            'placement' => 'home',
            'tone' => 'info',
            'is_published' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Horaires d’été')
            ->assertSee('Ouverture exceptionnelle')
            ->assertDontSee('Ancienne annonce');
    }

    public function test_home_news_list_hides_expired_posts(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'is_published' => true,
            'published_at' => now(),
        ]);

        NewsPost::query()->create([
            'title' => 'Actualité active',
            'slug' => 'actualite-active',
            'excerpt' => 'Visible maintenant.',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'expires_at' => now()->addDay(),
        ]);

        NewsPost::query()->create([
            'title' => 'Actualité expirée',
            'slug' => 'actualite-expiree',
            'excerpt' => 'Invisible maintenant.',
            'is_published' => true,
            'published_at' => now()->subDays(5),
            'expires_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Actualité active')
            ->assertDontSee('Actualité expirée');
    }

    public function test_text_page_is_available_by_slug(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Mentions légales',
            'slug' => 'mentions-legales',
            'type' => Page::TYPE_TEXT,
            'hero_title' => 'Mentions légales',
            'content' => '<p>Éditeur du site: Maracuja CMS.</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/mentions-legales')
            ->assertOk()
            ->assertSee('Fil d Ariane')
            ->assertSee('Mentions légales')
            ->assertSee('Éditeur du site: Maracuja CMS')
            ->assertSee('Retour à l&#039;accueil', false);
    }

    public function test_contact_page_uses_page_registry_metadata(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'type' => Page::TYPE_SYSTEM,
            'template' => 'contact',
            'hero_title' => 'Parlons du projet',
            'hero_subtitle' => 'Un message suffit pour démarrer.',
            'seo_title' => 'Contact SEO',
            'seo_description' => 'Contacter le studio.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('<title>Contact SEO</title>', false)
            ->assertSee('Parlons du projet')
            ->assertSee('Un message suffit pour démarrer.');
    }

    public function test_contact_form_stores_inquiry_and_sends_mail_when_inquiries_module_is_enabled(): void
    {
        Mail::fake();

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@maracuja.test',
        ]);

        $this->post('/contact', [
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'subject' => 'Projet',
            'message' => 'Bonjour depuis le formulaire.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas(Inquiry::class, [
            'email' => 'ivo@example.test',
        ]);

        Mail::assertSent(ContactMessageReceived::class);
    }

    public function test_contact_form_sends_mail_without_storing_inquiry_when_inquiries_module_is_not_enabled(): void
    {
        config(['maracuja.modules.inquiries' => false]);

        Mail::fake();

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@maracuja.test',
        ]);

        $this->post('/contact', [
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'subject' => 'Projet',
            'message' => 'Bonjour depuis le formulaire.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseCount(Inquiry::class, 0);

        Mail::assertSent(ContactMessageReceived::class);
    }

    public function test_contact_form_rejects_invalid_email_without_top_level_domain(): void
    {
        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@maracuja.test',
        ]);

        $this->post('/contact', [
            'name' => 'Ivo',
            'email' => 'ivo@mail',
            'subject' => 'Projet',
            'message' => 'Bonjour depuis le formulaire.',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount(Inquiry::class, 0);
    }

    public function test_contact_form_stores_inquiry_when_admin_email_is_not_configured(): void
    {
        Mail::fake();

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => null,
        ]);

        $this->post('/contact', [
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'subject' => 'Projet',
            'message' => 'Bonjour depuis le formulaire.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas(Inquiry::class, [
            'email' => 'ivo@example.test',
        ]);

        Mail::assertNothingSent();
    }

    public function test_contact_form_sends_user_confirmation_if_enabled(): void
    {
        Mail::fake();

        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'contact_email' => 'contact@maracuja.test',
            'contact_form_send_confirmation_email' => true,
        ]);

        $this->post('/contact', [
            'name' => 'Ivo',
            'email' => 'ivo@example.test',
            'subject' => 'Projet',
            'message' => 'Bonjour depuis le formulaire.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas(Inquiry::class, [
            'email' => 'ivo@example.test',
        ]);

        Mail::assertSent(ContactMessageConfirmation::class);
    }

    public function test_disabled_news_module_returns_not_found(): void
    {
        config(['maracuja.modules.news' => false]);

        $this->get('/actualites')->assertNotFound();
    }

    public function test_essence_offer_hides_signature_modules_from_public_navigation(): void
    {
        config([
            'maracuja.modules.news' => false,
            'maracuja.modules.articles' => false,
            'maracuja.modules.gallery' => false,
        ]);

        SiteSetting::current();

        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('href="http://localhost/actualites"', false)
            ->assertDontSee('href="http://localhost/articles"', false)
            ->assertDontSee('Galerie démo');

        $this->get('/actualites')->assertNotFound();
        $this->get('/articles')->assertNotFound();
    }

    public function test_expired_news_post_detail_returns_not_found(): void
    {
        SiteSetting::current();

        NewsPost::query()->create([
            'title' => 'Actualité expirée',
            'slug' => 'actualite-expiree',
            'excerpt' => 'Invisible maintenant.',
            'content' => '<p>Archive non visible.</p>',
            'is_published' => true,
            'has_detail_page' => true,
            'published_at' => now()->subDays(5),
            'expires_at' => now()->subDay(),
        ]);

        $this->get('/actualites/actualite-expiree')->assertNotFound();
    }

    public function test_news_list_orders_pinned_posts_first(): void
    {
        SiteSetting::current();

        NewsPost::query()->create([
            'title' => 'Actualité récente',
            'slug' => 'actualite-recente',
            'excerpt' => 'Récente mais non épinglée.',
            'is_published' => true,
            'is_pinned' => false,
            'has_detail_page' => true,
            'published_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        NewsPost::query()->create([
            'title' => 'Actualité épinglée',
            'slug' => 'actualite-epinglee',
            'excerpt' => 'Prioritaire.',
            'is_published' => true,
            'is_pinned' => true,
            'has_detail_page' => true,
            'published_at' => now()->subDays(3),
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/actualites')
            ->assertOk()
            ->assertSeeInOrder(['Actualité épinglée', 'Actualité récente']);
    }

    public function test_news_without_detail_page_has_no_public_detail(): void
    {
        SiteSetting::current();

        NewsPost::query()->create([
            'title' => 'Annonce simple',
            'slug' => 'annonce-simple',
            'excerpt' => 'Visible dans la liste uniquement.',
            'content' => '<p>Ne doit pas être visible en page détail.</p>',
            'is_published' => true,
            'is_pinned' => false,
            'has_detail_page' => false,
            'published_at' => now()->subHour(),
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/actualites')
            ->assertOk()
            ->assertSee('Annonce simple')
            ->assertDontSee('href="http://localhost/actualites/annonce-simple"', false);

        $this->get('/actualites/annonce-simple')->assertNotFound();
    }

    public function test_news_detail_shows_breadcrumb_and_back_link(): void
    {
        SiteSetting::current();

        NewsPost::query()->create([
            'title' => 'Actualité active',
            'slug' => 'actualite-active',
            'excerpt' => 'Visible maintenant.',
            'content' => '<p>Détail de l actualité.</p>',
            'is_published' => true,
            'has_detail_page' => true,
            'published_at' => now()->subHour(),
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/actualites/actualite-active')
            ->assertOk()
            ->assertSee('Fil d Ariane')
            ->assertSee('Actualités')
            ->assertSee('Retour aux actualités');
    }

    public function test_articles_render_structured_blocks(): void
    {
        SiteSetting::current();

        Article::query()->create([
            'title' => 'Bois et geste',
            'slug' => 'bois-et-geste',
            'excerpt' => 'Une note d’atelier.',
            'body_blocks' => [
                [
                    'type' => 'heading',
                    'level' => '2',
                    'heading' => 'Une matière vivante',
                ],
                [
                    'type' => 'rich_text',
                    'text' => '<p>Le bois répond au geste.</p>',
                ],
                [
                    'type' => 'quote',
                    'quote' => 'Le geste confirme.',
                    'author' => 'Atelier',
                ],
                [
                    'type' => 'table',
                    'table_rows' => "Bois | Usage\nCumaru | Archet moderne",
                ],
            ],
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/articles')
            ->assertOk()
            ->assertSee('Bois et geste')
            ->assertSee('Une note d’atelier.');

        $this->get('/articles/bois-et-geste')
            ->assertOk()
            ->assertSee('Une matière vivante')
            ->assertSee('Le bois répond au geste.')
            ->assertSee('Le geste confirme.')
            ->assertSee('Cumaru')
            ->assertSee('Retour à articles');

        $this->get('/article.php?slug=bois-et-geste')
            ->assertRedirect('/articles/bois-et-geste');
    }
}
