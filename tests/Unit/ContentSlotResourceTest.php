<?php

namespace Tests\Unit;

use App\Filament\Resources\ContentSlots\ContentSlotResource;
use Tests\TestCase;

class ContentSlotResourceTest extends TestCase
{
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
