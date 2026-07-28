<?php

namespace Tests\Feature\Conversations;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Actions\StartAnonymousConversation;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ConversationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('maracuja.modules.conversations', true);
    }

    public function test_admin_can_open_the_inbox_and_conversation_timeline(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'Message visible dans la boîte.', MessageAuthorType::Visitor);

        $this->actingAs($admin)
            ->get('/admin/conversations')
            ->assertOk()
            ->assertSee($conversation->public_reference);

        $this->actingAs($admin)
            ->get("/admin/conversations/{$conversation->id}")
            ->assertOk()
            ->assertSee('Message visible dans la boîte.')
            ->assertSee('Prendre en charge')
            ->assertSee('Ajouter une note interne')
            ->assertDontSee('Répondre')
            ->assertDontSee('Contacter sur WhatsApp');

        $contact = Contact::query()->create([
            'display_name' => 'Personne de test',
            'phone' => '+33 6 12 34 56 78',
        ]);
        $conversation->update(['contact_id' => $contact->id]);

        $this->actingAs($admin)
            ->get("/admin/conversations/{$conversation->id}")
            ->assertOk()
            ->assertSee('Contacter sur WhatsApp');
    }

    public function test_non_admin_cannot_access_conversation_records(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $conversation = StartAnonymousConversation::run()->conversation;

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Conversation::class));
        $this->assertFalse(Gate::forUser($user)->allows('view', $conversation));

        $this->actingAs($user)
            ->get('/admin/conversations')
            ->assertForbidden();
    }

    public function test_conversations_cannot_be_deleted_from_the_inbox(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conversation = StartAnonymousConversation::run()->conversation;

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $conversation));
    }
}
