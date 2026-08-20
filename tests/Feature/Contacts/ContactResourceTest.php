<?php

namespace Tests\Feature\Contacts;

use App\Models\User;
use App\Modules\Contacts\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Modules\Contacts\Models\Contact;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_contact(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageContacts::class)
            ->callAction('create', [
                'first_name' => 'Ana',
                'last_name' => 'Silva',
                'email' => 'ana@example.test',
                'phone' => '+55 11 99999-9999',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas(Contact::class, [
            'first_name' => 'Ana',
            'last_name' => 'Silva',
            'email' => 'ana@example.test',
        ]);
    }
}
