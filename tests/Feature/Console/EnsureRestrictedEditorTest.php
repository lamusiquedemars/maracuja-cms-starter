<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureRestrictedEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_restricted_editor_with_the_requested_capabilities(): void
    {
        $this->artisan('maracuja:ensure-restricted-editor david@example.test --name=David --permission=notices --permission=inquiries --permission=contacts')
            ->expectsQuestion('Mot de passe (8 caractères minimum)', 'password')
            ->expectsOutput('Éditeur limité actif : david@example.test (notices, inquiries, contacts)')
            ->assertSuccessful();

        $user = User::query()->where('email', 'david@example.test')->firstOrFail();

        $this->assertSame(User::ROLE_EDITOR, $user->role);
        $this->assertFalse($user->is_admin);
        $this->assertSame(['notices', 'inquiries', 'contacts'], $user->content_permissions);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_it_rejects_unknown_capabilities(): void
    {
        $this->artisan('maracuja:ensure-restricted-editor david@example.test --permission=unknown')
            ->expectsOutput('Capacité invalide : unknown.')
            ->assertFailed();
    }
}
