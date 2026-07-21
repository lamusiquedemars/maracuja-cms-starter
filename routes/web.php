<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AudienceUnsubscribeController;
use App\Http\Controllers\BrevoAudienceWebhookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Support\Modules;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Fallback for hosts such as LWS that expose the project root instead of public/.
Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('public-storage');

Route::get('/', HomeController::class)->name('home');

Route::get('/audience/desinscription/{token}', AudienceUnsubscribeController::class)->name('audience.unsubscribe');
Route::post('/webhooks/brevo/audience/{secret}', BrevoAudienceWebhookController::class)->name('webhooks.brevo.audience');

Route::get('/actualites', [NewsController::class, 'index'])->name('news.index');
Route::get('/actualites/{slug}', [NewsController::class, 'show'])->name('news.show');

if (Modules::enabled('articles')) {
    Route::get('/article.php', [ArticleController::class, 'legacy'])->name('articles.legacy');
    Route::get('/'.config('maracuja.articles.public_path', 'articles'), [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/'.config('maracuja.articles.public_path', 'articles').'/{slug}', [ArticleController::class, 'show'])->name('articles.show');
}

if (Modules::enabled('events')) {
    Route::get('/'.config('maracuja.events.public_path', 'evenements'), [EventController::class, 'index'])->name('events.index');
    Route::get('/'.config('maracuja.events.public_path', 'evenements').'/{slug}', [EventController::class, 'show'])->name('events.show');
}

if (Modules::enabled('contact_form')) {
    Route::get('/contact', [ContactController::class, 'create'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
}

Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show');
