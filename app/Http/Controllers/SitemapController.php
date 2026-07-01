<?php

namespace App\Http\Controllers;

use App\Modules\News\Models\NewsPost;
use App\Modules\Pages\Models\Page;
use App\Support\Modules;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now(),
                'priority' => '1.0',
            ],
        ];

        if (Modules::enabled('pages')) {
            Page::query()
                ->where('is_published', true)
                ->get(['slug', 'updated_at'])
                ->each(function (Page $page) use (&$urls): void {
                    if ($page->slug === 'accueil') {
                        return;
                    }

                    if ($page->slug === 'actualites') {
                        return;
                    }

                    if ($page->slug === 'contact' && ! Modules::enabled('contact_form')) {
                        return;
                    }

                    $url = $page->publicUrl();

                    if (! $url) {
                        return;
                    }

                    $urls[] = [
                        'loc' => $url,
                        'lastmod' => $page->updated_at,
                        'priority' => '0.8',
                    ];
                });
        }

        if (Modules::enabled('news')) {
            $urls[] = [
                'loc' => route('news.index'),
                'lastmod' => now(),
                'priority' => '0.6',
            ];

            NewsPost::query()
                ->where('is_published', true)
                ->get(['slug', 'updated_at'])
                ->each(function (NewsPost $post) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('news.show', $post->slug),
                        'lastmod' => $post->updated_at,
                        'priority' => '0.5',
                    ];
                });
        }

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
