<?php

namespace Tests\Unit;

use Maracuja\SiteCore\Foundation\Contracts\FoundationAccessGate;
use Maracuja\SiteCore\Foundation\Filament\Resources\ContentSlots\ContentSlotResource;
use Tests\TestCase;

class ContentSlotResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(FoundationAccessGate::class, fn () => new class implements FoundationAccessGate {
            public function allows(string $ability): bool { return true; }
        });
    }
    public function test_navigation_is_visible_when_content_slots_module_is_enabled(): void
    {
        config(['maracuja.modules.content_slots' => true]);

        $this->assertTrue(ContentSlotResource::shouldRegisterNavigation());
    }

    public function test_navigation_is_hidden_when_content_slots_module_is_disabled(): void
    {
        config(['maracuja.modules.content_slots' => false]);

        $this->assertFalse(ContentSlotResource::shouldRegisterNavigation());
    }
}
