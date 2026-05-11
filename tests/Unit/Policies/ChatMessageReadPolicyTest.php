<?php

namespace Tests\Unit\Policies;

use App\Policies\ChatMessageReadPolicy;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\Chat\Models\ChatMessageRead;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChatMessageReadPolicyTest extends TestCase
{
    private ChatMessageReadPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ChatMessageReadPolicy::class);
    }

    private function createChatMessageRead(): ChatMessageRead
    {
        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $chat = Chat::create([
            'name' => 'Test',
            'is_group' => false,
            'created_by' => $sender->id,
        ]);
        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'sender_id' => $sender->id,
            'message' => 'Hello',
        ]);
        $read = new ChatMessageRead();
        $read->forceFill([
            'message_id' => $message->id,
            'user_id' => $reader->id,
            'read_at' => now(),
        ])->save();

        return $read;
    }

    #[Test]
    public function admin_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser()));
    }

    #[Test]
    public function user_can_view_any(): void
    {
        $this->assertTrue($this->policy->viewAny(User::factory()->create()));
    }

    #[Test]
    public function user_can_view_existing_read(): void
    {
        $read = $this->createChatMessageRead();
        $this->assertTrue($this->policy->view(User::factory()->create(), $read));
    }

    #[Test]
    public function user_cannot_view_unsaved_read(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new ChatMessageRead()));
    }

    #[Test]
    public function user_can_create_read(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_can_update_existing_read(): void
    {
        $read = $this->createChatMessageRead();
        $this->assertTrue($this->policy->update(User::factory()->create(), $read));
    }

    #[Test]
    public function user_can_delete_existing_read(): void
    {
        $read = $this->createChatMessageRead();
        $this->assertTrue($this->policy->delete(User::factory()->create(), $read));
    }

    #[Test]
    public function user_can_restore_existing_read(): void
    {
        $read = $this->createChatMessageRead();
        $this->assertTrue($this->policy->restore(User::factory()->create(), $read));
    }

    #[Test]
    public function user_can_force_delete_existing_read(): void
    {
        $read = $this->createChatMessageRead();
        $this->assertTrue($this->policy->forceDelete(User::factory()->create(), $read));
    }
}
