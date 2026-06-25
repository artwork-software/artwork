<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ChatAuthorizationTest extends FeatureTestCase
{
    #[Test]
    public function non_member_cannot_read_chat_messages(): void
    {
        $member = User::factory()->create();
        $chat = new Chat(['is_group' => true]);
        $chat->created_by = $member->id;
        $chat->save();
        $chat->users()->attach($member->id);

        // Eingeloggter User ohne Rechte, der NICHT Mitglied des Chats ist.
        $this->actingAsUserWith([]);

        $this->getJson(route('chat-system.get-chat-messages', $chat))
            ->assertForbidden();
    }

    #[Test]
    public function non_member_cannot_send_message(): void
    {
        $member = User::factory()->create();
        $chat = new Chat(['is_group' => true]);
        $chat->created_by = $member->id;
        $chat->save();
        $chat->users()->attach($member->id);

        $this->actingAsUserWith([]);

        $this->postJson(route('chat-system.send-message', $chat), ['message' => 'leak'])
            ->assertForbidden();
    }

    #[Test]
    public function member_can_read_chat_messages(): void
    {
        $member = User::factory()->create();
        $chat = new Chat(['is_group' => true]);
        $chat->created_by = $member->id;
        $chat->save();
        $chat->users()->attach($member->id);

        $this->actingAs($member);

        $this->getJson(route('chat-system.get-chat-messages', $chat))
            ->assertOk();
    }
}
