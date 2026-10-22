<?php

namespace Tests\Feature;

use App\Models\User;
use Maracuja\SiteCore\Contact\Models\Contact;
use Maracuja\SiteCore\Publication\Models\NewsPost;
use Maracuja\SiteCore\Foundation\Models\SiteNotice;
use Maracuja\SiteCore\Foundation\Support\FoundationAccess;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_imported_bcrypt_password_can_log_in_through_filament(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_admin' => true]);
        $hash = password_hash('imported-password', PASSWORD_BCRYPT, ['cost' => 4]);
        $user->getConnection()->table($user->getTable())->where('id', $user->id)->update([
            'password' => '$2b$'.substr($hash, 4),
        ]);

        $this->get('/admin/login')->assertOk();
        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'imported-password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
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

    public function test_site_manager_can_manage_editorial_content_but_not_global_settings_or_seo(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_SITE_MANAGER, 'is_admin' => false]);

        $this->actingAs($manager);

        $this->assertTrue(FoundationAccess::allows('foundation.pages.manage'));
        $this->assertTrue(FoundationAccess::allows('publication.articles.manage'));
        $this->assertFalse(FoundationAccess::allows('foundation.settings.manage'));
        $this->assertFalse(FoundationAccess::allows('foundation.seo.manage'));
    }

    public function test_restricted_editor_only_accesses_explicit_content_capabilities(): void
    {
        $editor = User::factory()->create([
            'role' => User::ROLE_EDITOR,
            'is_admin' => false,
            'content_permissions' => ['notices', 'inquiries', 'contacts'],
        ]);

        $this->assertTrue(Gate::forUser($editor)->allows('create', SiteNotice::class));
        $this->assertFalse(Gate::forUser($editor)->allows('viewAny', NewsPost::class));
        $this->actingAs($editor)->get('/admin/news-posts')->assertForbidden();
        $this->actingAs($editor)->get('/admin/site-notices')->assertOk();
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
