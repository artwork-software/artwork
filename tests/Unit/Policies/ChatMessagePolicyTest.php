<?php

namespace Tests\Unit\Policies;

use App\Policies\ChatMessagePolicy;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChatMessagePolicyTest extends TestCase
{
    private ChatMessagePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ChatMessagePolicy::class);
    }

    private function createChatMessage(): ChatMessage
    {
        $sender = User::factory()->create();
        $chat = Chat::create([
            'name' => 'Test',
            'is_group' => false,
            'created_by' => $sender->id,
        ]);
        return ChatMessage::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
        ]);
    }

    #[Test]
    public function admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser()));
    }

    #[Test]
    public function authenticated_user_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny(User::factory()->create()));
    }

    #[Test]
    public function user_can_view_existing_message(): void
    {
        $message = $this->createChatMessage();
        $this->assertTrue($this->policy->view(User::factory()->create(), $message));
    }

    #[Test]
    public function user_cannot_view_unsaved_message(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new ChatMessage()));
    }

    #[Test]
    public function user_can_create_message(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_can_update_existing_message(): void
    {
        $message = $this->createChatMessage();
        $this->assertTrue($this->policy->update(User::factory()->create(), $message));
    }

    #[Test]
    public function user_can_delete_existing_message(): void
    {
        $message = $this->createChatMessage();
        $this->assertTrue($this->policy->delete(User::factory()->create(), $message));
    }

    #[Test]
    public function user_can_restore_existing_message(): void
    {
        $message = $this->createChatMessage();
        $this->assertTrue($this->policy->restore(User::factory()->create(), $message));
    }

    #[Test]
    public function user_can_force_delete_existing_message(): void
    {
        $message = $this->createChatMessage();
        $this->assertTrue($this->policy->forceDelete(User::factory()->create(), $message));
    }
}
