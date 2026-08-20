<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_user_can_access_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_viewer_can_access_panel_in_read_only_mode(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_editor_can_create_contacts(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR, 'is_admin' => false]);

        $this->assertTrue(Gate::forUser($editor)->allows('create', Contact::class));
    }

    public function test_viewer_cannot_create_contacts(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER, 'is_admin' => false]);

        $this->assertFalse(Gate::forUser($viewer)->allows('create', Contact::class));
    }

    public function test_ivo_is_an_administrator_even_if_his_stored_role_is_viewer(): void
    {
        $ivo = User::factory()->create([
            'email' => 'ivo@maracujadigital.fr',
            'role' => User::ROLE_VIEWER,
            'is_admin' => false,
        ]);

        $this->assertTrue($ivo->isAdministrator());
        $this->assertTrue(Gate::forUser($ivo)->allows('create', Contact::class));

        $this->actingAs($ivo)
            ->get('/admin')
            ->assertOk();
    }
}
