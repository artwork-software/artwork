<?php

namespace Tests\Feature\Modules\Chat;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Name eines Gruppenchats hat beim Anlegen und beim Umbenennen dieselbe Obergrenze (55 Zeichen).
 */
final class ChatGroupNameLengthTest extends FeatureTestCase
{
    private function groupChatFor(User $member): Chat
    {
        $chat = new Chat(['is_group' => true, 'name' => 'Technik']);
        $chat->created_by = $member->id;
        $chat->save();
        $chat->users()->attach($member->id);

        return $chat;
    }

    #[Test]
    public function renaming_accepts_a_name_of_55_characters(): void
    {
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChatFor($member);
        $name = str_repeat('a', 55);

        $this->patchJson(route('chat-system.update-chat', $chat), ['name' => $name])->assertOk();

        $this->assertSame($name, $chat->fresh()->name);
    }

    #[Test]
    public function renaming_rejects_a_name_longer_than_55_characters_like_creating_does(): void
    {
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChatFor($member);
        $tooLong = str_repeat('a', 56);

        $this->patchJson(route('chat-system.update-chat', $chat), ['name' => $tooLong])
            ->assertJsonValidationErrors('name');
        $this->postJson(route('chat.store'), [
            'name' => $tooLong,
            'users' => [User::factory()->create()->id, User::factory()->create()->id],
        ])->assertJsonValidationErrors('name');

        $this->assertSame('Technik', $chat->fresh()->name);
    }
}
