<?php

namespace Tests\Feature\Conversations;

use App\Modules\Conversations\Actions\AddMessage;
use App\Modules\Conversations\Actions\StartAnonymousConversation;
use App\Modules\Conversations\Enums\ConversationStatus;
use App\Modules\Conversations\Enums\MessageAuthorType;
use App\Modules\Conversations\Events\ConversationStarted;
use App\Modules\Conversations\Events\MessageAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConversationRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_events_are_dispatched_without_exposing_module_internals(): void
    {
        Event::fake([ConversationStarted::class, MessageAdded::class]);

        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'Bonjour.', MessageAuthorType::Visitor);

        Event::assertDispatched(ConversationStarted::class);
        Event::assertDispatched(MessageAdded::class);
    }

    public function test_prune_removes_only_old_closed_threads_and_their_messages(): void
    {
        config()->set('maracuja.conversations.retention_days', 30);

        $old = StartAnonymousConversation::run()->conversation;
        AddMessage::run($old, 'Ancien message.', MessageAuthorType::Visitor);
        $old->forceFill([
            'status' => ConversationStatus::Closed,
            'updated_at' => now()->subDays(31),
        ])->saveQuietly();

        $active = StartAnonymousConversation::run()->conversation;
        AddMessage::run($active, 'Conversation active.', MessageAuthorType::Visitor);
        $active->forceFill(['updated_at' => now()->subDays(60)])->saveQuietly();

        $this->artisan('conversations:prune')->assertSuccessful();

        $this->assertDatabaseMissing('conversations', ['id' => $old->id]);
        $this->assertDatabaseMissing('conversation_messages', ['conversation_id' => $old->id]);
        $this->assertDatabaseHas('conversations', ['id' => $active->id]);
    }

    public function test_dry_run_does_not_delete_anything(): void
    {
        $conversation = StartAnonymousConversation::run()->conversation;
        $conversation->forceFill([
            'status' => ConversationStatus::Archived,
            'updated_at' => now()->subYear(),
        ])->saveQuietly();

        $this->artisan('conversations:prune --dry-run')->assertSuccessful();

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
    }

    public function test_inactive_unqualified_conversations_are_archived_without_being_deleted(): void
    {
        config()->set('maracuja.conversations.archive_inactive_after_hours', 48);
        $conversation = StartAnonymousConversation::run()->conversation;
        AddMessage::run($conversation, 'Conversation abandonnée.', MessageAuthorType::Visitor);
        $conversation->forceFill([
            'status' => ConversationStatus::AiActive,
            'last_message_at' => now()->subHours(49),
            'updated_at' => now()->subHours(49),
        ])->saveQuietly();

        $this->artisan('conversations:prune')->assertSuccessful();

        $conversation->refresh();
        $this->assertSame(ConversationStatus::Archived, $conversation->status);
        $this->assertFalse($conversation->ai_enabled);
        $this->assertNotNull($conversation->closed_at);
    }
}
