<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageTest extends TestCase
{
    public function test_it_serves_a_public_upload_when_the_web_server_delegates_storage_to_laravel(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('mail/example.jpg', 'image-content');

        $this->get('/storage/mail/example.jpg')
            ->assertOk()
            ->assertHeader('cache-control', 'immutable, max-age=31536000, public')
            ->assertStreamedContent('image-content');
    }

    public function test_it_does_not_expose_missing_or_traversing_storage_paths(): void
    {
        Storage::fake('public');

        $this->get('/storage/missing.jpg')->assertNotFound();
        $this->get('/storage/../.env')->assertNotFound();
    }
}
