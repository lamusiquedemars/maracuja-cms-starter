<?php

namespace App\Http\Controllers;

use App\Modules\Articles\Models\Article;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\ContentSlots;
use App\Support\Modules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        abort_unless(Modules::enabled('articles'), 404);

        return view('site.articles.index', [
            'settings' => SiteSetting::current(),
            'label' => ContentSlots::value('articles.public_label', 'Articles'),
            'subtitle' => ContentSlots::value('articles.index.subtitle', 'Articles éditoriaux publiés sur le site.'),
            'posts' => Article::query()->forListing()->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        abort_unless(Modules::enabled('articles'), 404);

        $post = Article::query()
            ->where('slug', $slug)
            ->visible()
            ->firstOrFail();

        return view('site.articles.show', [
            'settings' => SiteSetting::current(),
            'label' => ContentSlots::value('articles.public_label', 'Articles'),
            'post' => $post,
        ]);
    }

    public function legacy(Request $request): RedirectResponse
    {
        $slug = (string) $request->query('slug');
        abort_if($slug === '', 404);

        return redirect()->route('articles.show', $slug, 301);
    }
}
