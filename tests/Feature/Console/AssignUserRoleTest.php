<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssignUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_promotes_an_existing_user_without_changing_their_password(): void
    {
        $password = Hash::make('unchanged-password');

        $user = User::factory()->create([
            'email' => 'ivo@maracujadigital.fr',
            'password' => $password,
            'role' => User::ROLE_VIEWER,
            'is_admin' => false,
        ]);

        $this->artisan('maracuja:assign-role ivo@maracujadigital.fr admin')
            ->expectsOutput('Rôle mis à jour : ivo@maracujadigital.fr (admin)')
            ->assertSuccessful();

        $user->refresh();

        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertTrue($user->is_admin);
        $this->assertSame($password, $user->getRawOriginal('password'));
    }
}
