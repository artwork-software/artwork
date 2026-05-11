<?php

namespace Tests\Unit\Policies;

use App\Policies\ChatUserPolicy;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatUser;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChatUserPolicyTest extends TestCase
{
    private ChatUserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ChatUserPolicy::class);
    }

    private function createChatUser(): ChatUser
    {
        $user = User::factory()->create();
        $chat = Chat::create([
            'name' => 'Test',
            'is_group' => false,
            'created_by' => $user->id,
        ]);
        $chat->users()->attach($user->id);

        $pivot = new ChatUser();
        $pivot->setTable('chat_users');
        $pivot->forceFill([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
        ]);
        $pivot->exists = true;

        return $pivot;
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
    public function user_can_view_existing_chat_user(): void
    {
        $chatUser = $this->createChatUser();
        $this->assertTrue($this->policy->view(User::factory()->create(), $chatUser));
    }

    #[Test]
    public function user_cannot_view_unsaved_chat_user(): void
    {
        $this->assertFalse($this->policy->view(User::factory()->create(), new ChatUser()));
    }

    #[Test]
    public function user_can_create(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_can_update_existing_chat_user(): void
    {
        $chatUser = $this->createChatUser();
        $this->assertTrue($this->policy->update(User::factory()->create(), $chatUser));
    }

    #[Test]
    public function user_can_delete_existing_chat_user(): void
    {
        $chatUser = $this->createChatUser();
        $this->assertTrue($this->policy->delete(User::factory()->create(), $chatUser));
    }

    #[Test]
    public function user_can_restore_existing_chat_user(): void
    {
        $chatUser = $this->createChatUser();
        $this->assertTrue($this->policy->restore(User::factory()->create(), $chatUser));
    }

    #[Test]
    public function user_can_force_delete_existing_chat_user(): void
    {
        $chatUser = $this->createChatUser();
        $this->assertTrue($this->policy->forceDelete(User::factory()->create(), $chatUser));
    }
}
