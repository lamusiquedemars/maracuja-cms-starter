<?php

use App\Http\Controllers\AudienceUnsubscribeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', HomeController::class)->name('home');

Route::get('/audience/desinscription/{token}', AudienceUnsubscribeController::class)->name('audience.unsubscribe');

Route::get('/actualites', [NewsController::class, 'index'])->name('news.index');
Route::get('/actualites/{slug}', [NewsController::class, 'show'])->name('news.show');

if (\App\Support\Modules::enabled('articles')) {
    Route::get('/article.php', [ArticleController::class, 'legacy'])->name('articles.legacy');
    Route::get('/' . config('maracuja.articles.public_path', 'articles'), [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/' . config('maracuja.articles.public_path', 'articles') . '/{slug}', [ArticleController::class, 'show'])->name('articles.show');
}

if (\App\Support\Modules::enabled('events')) {
    Route::get('/' . config('maracuja.events.public_path', 'evenements'), [EventController::class, 'index'])->name('events.index');
    Route::get('/' . config('maracuja.events.public_path', 'evenements') . '/{slug}', [EventController::class, 'show'])->name('events.show');
}

if (\App\Support\Modules::enabled('contact_form')) {
    Route::get('/contact', [ContactController::class, 'create'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
}

Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
