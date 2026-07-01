<?php

namespace Tests\Feature;

use App\Modules\News\Models\NewsPost;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_page_outputs_core_seo_tags(): void
    {
        SiteSetting::query()->create([
            'site_name' => 'Maracuja CMS',
            'default_seo_title' => 'Maracuja default',
            'default_seo_description' => 'Description par défaut du starter.',
            'default_og_image_path' => '/demo/theme-system.svg',
        ]);

        Page::query()->create([
            'title' => 'Mentions légales',
            'slug' => 'mentions-legales',
            'type' => Page::TYPE_TEXT,
            'content' => '<p>Informations légales.</p>',
            'seo_title' => 'Mentions SEO',
            'seo_description' => 'Une description SEO claire.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/mentions-legales')
            ->assertOk()
            ->assertSee('<title>Mentions SEO</title>', false)
            ->assertSee('<meta name="description" content="Une description SEO claire.">', false)
            ->assertSee('<link rel="canonical" href="'.url('/mentions-legales').'">', false)
            ->assertSee('<meta property="og:title" content="Mentions SEO">', false)
            ->assertSee('<meta property="og:image" content="'.url('/demo/theme-system.svg').'">', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_blocks_indexing_by_default(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');
    }

    public function test_sitemap_lists_public_content(): void
    {
        SiteSetting::current();

        Page::query()->create([
            'title' => 'Services',
            'slug' => 'services',
            'is_published' => true,
            'published_at' => now(),
        ]);

        NewsPost::query()->create([
            'title' => 'Demo',
            'slug' => 'demo',
            'is_published' => true,
            'has_detail_page' => true,
            'published_at' => now(),
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml')
            ->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.url('/services').'</loc>', false)
            ->assertSee('<loc>'.url('/actualites/demo').'</loc>', false);
    }
}
