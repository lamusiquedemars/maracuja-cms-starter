<?php

namespace Tests\Unit;

use App\Support\Modules;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    public function test_enabled_uses_module_configuration(): void
    {
        config([
            'maracuja.modules.site_settings' => true,
            'maracuja.modules.pages' => true,
            'maracuja.modules.contact_form' => true,
            'maracuja.modules.inquiries' => false,
            'maracuja.modules.notices' => false,
            'maracuja.modules.content_slots' => false,
            'maracuja.modules.news' => false,
            'maracuja.modules.articles' => false,
            'maracuja.modules.gallery' => false,
        ]);

        $this->assertTrue(Modules::enabled('site_settings'));
        $this->assertTrue(Modules::enabled('pages'));
        $this->assertTrue(Modules::enabled('contact_form'));
        $this->assertFalse(Modules::enabled('inquiries'));
        $this->assertFalse(Modules::enabled('notices'));
        $this->assertFalse(Modules::enabled('content_slots'));
        $this->assertFalse(Modules::enabled('news'));
        $this->assertFalse(Modules::enabled('articles'));
        $this->assertFalse(Modules::enabled('gallery'));
    }

    public function test_module_can_be_disabled_explicitly(): void
    {
        config([
            'maracuja.modules.gallery' => false,
            'maracuja.modules.news' => true,
            'maracuja.modules.articles' => true,
            'maracuja.modules.inquiries' => true,
        ]);

        $this->assertFalse(Modules::enabled('gallery'));
        $this->assertTrue(Modules::enabled('news'));
        $this->assertTrue(Modules::enabled('articles'));
        $this->assertTrue(Modules::enabled('inquiries'));
    }

    public function test_extra_module_can_be_enabled_explicitly(): void
    {
        config([
            'maracuja.modules.contact_form' => true,
            'maracuja.modules.inquiries' => true,
        ]);

        $this->assertTrue(Modules::enabled('contact_form'));
        $this->assertTrue(Modules::enabled('inquiries'));
    }

    public function test_missing_module_directory_is_always_disabled(): void
    {
        config(['maracuja.modules.campaigns' => true]);

        $this->assertFalse(Modules::enabled('campaigns'));
    }

    public function test_installed_acquisition_module_can_be_enabled_explicitly(): void
    {
        config(['maracuja.modules.acquisition' => true]);

        $this->assertTrue(Modules::installed('acquisition'));
        $this->assertTrue(Modules::enabled('acquisition'));
    }

    public function test_developer_tool_visibility_is_separate_from_modules(): void
    {
        config([
            'maracuja.modules.pages' => true,
            'maracuja.developer_tools.pages_admin' => false,
        ]);

        $this->assertTrue(Modules::enabled('pages'));
        $this->assertFalse(Modules::developerToolEnabled('pages_admin'));

        config(['maracuja.developer_tools.pages_admin' => true]);

        $this->assertTrue(Modules::developerToolEnabled('pages_admin'));
    }
}
