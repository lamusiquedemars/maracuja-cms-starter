<?php

namespace App\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        abort_unless(Modules::enabled('pages'), 404);

        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        abort_if($page->isModule(), 404);

        if ($page->isText()) {
            $view = 'site.page';
        } else {
            $view = view()->exists("site.pages.{$page->template}")
                ? "site.pages.{$page->template}"
                : 'site.page';
        }

        return view($view, [
            'settings' => SiteSetting::current(),
            'page' => $page,
            'contactUrl' => Modules::enabled('contact_form') ? route('contact') : null,
        ]);
    }
}
