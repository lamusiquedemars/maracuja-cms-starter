<?php

namespace App\Http\Controllers;

use App\Modules\News\Models\NewsPost;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        abort_unless(Modules::enabled('news'), 404);

        return view('site.news.index', [
            'settings' => SiteSetting::current(),
            'page' => Modules::enabled('pages')
                ? Page::query()->where('slug', 'actualites')->where('is_published', true)->first()
                : null,
            'posts' => NewsPost::query()
                ->forListing()
                ->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        abort_unless(Modules::enabled('news'), 404);

        return view('site.news.show', [
            'settings' => SiteSetting::current(),
            'post' => NewsPost::query()
                ->where('slug', $slug)
                ->visible()
                ->where('has_detail_page', true)
                ->firstOrFail(),
        ]);
    }
}
