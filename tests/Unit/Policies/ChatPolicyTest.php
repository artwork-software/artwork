<?php

namespace Tests\Unit\Policies;

use App\Policies\ChatPolicy;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChatPolicyTest extends TestCase
{
    private ChatPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ChatPolicy::class);
    }

    private function createChat(?User $owner = null): Chat
    {
        $owner = $owner ?? User::factory()->create();
        $chat = Chat::create([
            'name' => 'Test Chat',
            'is_group' => false,
            'created_by' => $owner->id,
        ]);
        $chat->users()->attach($owner->id);
        return $chat;
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
    public function user_in_chat_can_view_chat(): void
    {
        $user = User::factory()->create();
        $chat = $this->createChat($user);

        $this->assertTrue($this->policy->view($user, $chat));
    }

    #[Test]
    public function user_not_in_chat_cannot_view_chat(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $chat = $this->createChat($owner);

        $this->assertFalse($this->policy->view($outsider, $chat));
    }

    #[Test]
    public function user_can_create_chat(): void
    {
        $this->assertTrue($this->policy->create(User::factory()->create()));
    }

    #[Test]
    public function user_in_chat_can_update_chat(): void
    {
        $user = User::factory()->create();
        $chat = $this->createChat($user);

        $this->assertTrue($this->policy->update($user, $chat));
    }

    #[Test]
    public function user_not_in_chat_cannot_update_chat(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $chat = $this->createChat($owner);

        $this->assertFalse($this->policy->update($outsider, $chat));
    }

    #[Test]
    public function user_in_chat_can_delete_chat(): void
    {
        $user = User::factory()->create();
        $chat = $this->createChat($user);

        $this->assertTrue($this->policy->delete($user, $chat));
    }

    #[Test]
    public function user_not_in_chat_cannot_delete_chat(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $chat = $this->createChat($owner);

        $this->assertFalse($this->policy->delete($outsider, $chat));
    }

    #[Test]
    public function user_in_chat_can_restore_chat(): void
    {
        $user = User::factory()->create();
        $chat = $this->createChat($user);

        $this->assertTrue($this->policy->restore($user, $chat));
    }

    #[Test]
    public function user_in_chat_can_force_delete_chat(): void
    {
        $user = User::factory()->create();
        $chat = $this->createChat($user);

        $this->assertTrue($this->policy->forceDelete($user, $chat));
    }

    #[Test]
    public function user_not_in_chat_cannot_force_delete_chat(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $chat = $this->createChat($owner);

        $this->assertFalse($this->policy->forceDelete($outsider, $chat));
    }
}
