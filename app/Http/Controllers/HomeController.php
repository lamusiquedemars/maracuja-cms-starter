<?php

namespace App\Http\Controllers;

use App\Modules\Gallery\Models\Gallery;
use App\Modules\News\Models\NewsPost;
use App\Modules\Notices\Models\SiteNotice;
use App\Modules\Pages\Models\Page;
use App\Modules\SiteSettings\Models\SiteSetting;
use App\Support\Modules;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $settings = SiteSetting::current();
        $homePage = Modules::enabled('pages')
            ? Page::query()->where('slug', 'accueil')->where('is_published', true)->first()
            : null;
        $servicesPage = Modules::enabled('pages')
            ? Page::query()->where('slug', 'services')->where('is_published', true)->first()
            : null;
        $gallery = Modules::enabled('gallery')
            ? Gallery::query()
                ->where('slug', config('maracuja.gallery.slug', 'home'))
                ->where('is_published', true)
                ->first()
            : null;

        return view('site.home', [
            'settings' => $settings,
            'homePage' => $homePage,
            'contactUrl' => Modules::enabled('contact_form') ? route('contact') : null,
            'servicesUrl' => $servicesPage ? route('pages.show', $servicesPage->slug) : null,
            'homeNotice' => Modules::enabled('notices')
                ? SiteNotice::query()->visible()->forPlacement('home')->latest('starts_at')->first()
                : null,
            'newsPosts' => Modules::enabled('news')
                ? NewsPost::query()->forListing()->limit(3)->get()
                : collect(),
            'gallery' => $gallery,
            'galleryImages' => $gallery
                ? $gallery->images()->where('is_published', true)->limit(6)->get()
                : collect(),
        ]);
    }
}
