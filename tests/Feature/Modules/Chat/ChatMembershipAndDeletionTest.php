<?php

namespace Tests\Feature\Modules\Chat;

use Artwork\Modules\Chat\Events\ChatMemberLeft;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Im Chat zählt ausschließlich echte Mitgliedschaft, auch für Admins (keine Policy, kein Gate::before).
 * Gruppenchats löscht nur die anlegende Person, alle anderen Mitglieder können den Chat verlassen.
 */
final class ChatMembershipAndDeletionTest extends FeatureTestCase
{
    /**
     * @param array<int, User> $furtherMembers
     */
    private function groupChat(User $creator, array $furtherMembers = [], bool $isGroup = true): Chat
    {
        $chat = new Chat(['is_group' => $isGroup, 'name' => 'Technik']);
        $chat->created_by = $creator->id;
        $chat->save();
        $chat->users()->attach(collect([$creator, ...$furtherMembers])->pluck('id')->all());

        return $chat;
    }

    private function messageIn(Chat $chat, User $sender): ChatMessage
    {
        return ChatMessage::factory()->create(['chat_id' => $chat->id, 'sender_id' => $sender->id]);
    }

    #[Test]
    public function admin_without_membership_cannot_read_messages(): void
    {
        $creator = User::factory()->create();
        $chat = $this->groupChat($creator);
        $message = $this->messageIn($chat, $creator);
        $admin = $this->actingAsAdmin();

        $this->getJson(route('chat-system.get-chat-messages', $chat))->assertForbidden();

        $this->assertDatabaseMissing('chat_message_reads', ['message_id' => $message->id, 'user_id' => $admin->id]);
    }

    #[Test]
    public function admin_without_membership_cannot_send_a_message(): void
    {
        $chat = $this->groupChat(User::factory()->create());
        $this->actingAsAdmin();

        $this->postJson(route('chat-system.send-message', $chat), ['message' => 'fremd'])->assertForbidden();

        $this->assertSame(0, $chat->messages()->count());
    }

    #[Test]
    public function admin_without_membership_cannot_mark_messages_as_read(): void
    {
        $creator = User::factory()->create();
        $chat = $this->groupChat($creator);
        $message = $this->messageIn($chat, $creator);
        $admin = $this->actingAsAdmin();

        $this->postJson(route('chat-system.mark-as-read', $message))->assertForbidden();
        $this->postJson(route('chat-system.mark-multiple-as-read'), ['message_ids' => [$message->id]])
            ->assertNoContent();

        $this->assertDatabaseMissing('chat_message_reads', ['message_id' => $message->id, 'user_id' => $admin->id]);
    }

    #[Test]
    public function admin_without_membership_cannot_rename_delete_or_leave(): void
    {
        $chat = $this->groupChat(User::factory()->create());
        $this->actingAsAdmin();

        $this->patchJson(route('chat-system.update-chat', $chat), ['name' => 'Übernommen'])->assertForbidden();
        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertForbidden();
        $this->postJson(route('chat-system.leave-chat', $chat))->assertForbidden();

        $this->assertSame('Technik', $chat->fresh()->name);
    }

    #[Test]
    public function admin_does_not_see_foreign_chats_in_the_chat_list(): void
    {
        $this->groupChat(User::factory()->create());
        $this->actingAsAdmin();

        $this->getJson(route('chat-system.get-chats'))->assertOk()->assertJsonCount(0, 'chats');
    }

    #[Test]
    public function admin_who_is_a_member_can_read_send_and_mark_as_read(): void
    {
        $creator = User::factory()->create();
        $admin = $this->actingAsAdmin();
        $chat = $this->groupChat($creator, [$admin]);
        $message = $this->messageIn($chat, $creator);

        $this->postJson(route('chat-system.mark-as-read', $message))->assertNoContent();
        $this->getJson(route('chat-system.get-chat-messages', $chat))->assertOk();
        $this->postJson(route('chat-system.send-message', $chat), ['message' => 'Hallo'])->assertOk();

        $this->assertDatabaseHas('chat_message_reads', ['message_id' => $message->id, 'user_id' => $admin->id]);
    }

    #[Test]
    public function member_can_read_send_mark_as_read_and_rename(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);
        $message = $this->messageIn($chat, $creator);

        $this->postJson(route('chat-system.mark-as-read', $message))->assertNoContent();
        $this->getJson(route('chat-system.get-chat-messages', $chat))
            ->assertOk()
            ->assertJsonPath('chat.created_by', $creator->id);
        $this->postJson(route('chat-system.send-message', $chat), ['message' => 'Hallo'])->assertOk();
        $this->patchJson(route('chat-system.update-chat', $chat), ['name' => 'Ton'])->assertOk();

        $this->assertSame('Ton', $chat->fresh()->name);
    }

    #[Test]
    public function member_who_did_not_create_the_group_chat_cannot_delete_it(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);
        $this->messageIn($chat, $creator);

        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertForbidden();

        $this->assertNotNull($chat->fresh());
        $this->assertSame(1, $chat->messages()->count());
    }

    #[Test]
    public function creator_deletes_the_group_chat_with_its_history(): void
    {
        $creator = $this->actingAsUserWith([]);
        $member = User::factory()->create();
        $chat = $this->groupChat($creator, [$member]);
        $message = $this->messageIn($chat, $creator);
        $message->reads()->create(['user_id' => $member->id, 'read_at' => now()]);

        $this->deleteJson(route('chat-system.delete-chat', $chat))
            ->assertOk()
            ->assertJsonPath('chat_id', $chat->id);

        $this->assertNull($chat->fresh());
        $this->assertDatabaseMissing('chat_messages', ['chat_id' => $chat->id]);
        $this->assertDatabaseMissing('chat_message_reads', ['message_id' => $message->id]);
        $this->assertDatabaseMissing('chat_users', ['chat_id' => $chat->id]);
    }

    #[Test]
    public function member_can_leave_the_group_chat_and_the_others_keep_it(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);
        $this->messageIn($chat, $creator);

        $this->postJson(route('chat-system.leave-chat', $chat))
            ->assertOk()
            ->assertJsonPath('chat_deleted', false);

        $this->assertFalse($chat->hasMember($member));
        $this->assertTrue($chat->hasMember($creator));
        $this->assertSame(1, $chat->messages()->count());
        Bus::assertDispatched(
            BroadcastEvent::class,
            fn (BroadcastEvent $job) => $job->event instanceof ChatMemberLeft
                && $job->event->chatId === $chat->id
                && $job->event->userId === $member->id
        );

        $this->getJson(route('chat-system.get-chat-messages', $chat))->assertForbidden();
        $this->postJson(route('chat-system.send-message', $chat), ['message' => 'noch da?'])->assertForbidden();
    }

    /**
     * Eine Gruppe, die nach einem Austritt nur noch zwei Mitglieder hat, ist kein Direktchat der beiden.
     */
    #[Test]
    public function starting_a_direct_chat_does_not_return_a_group_that_shrank_to_two_members(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $group = $this->groupChat($creator, [$member]);

        $chatId = $this->postJson(route('chat.store'), ['users' => [$creator->id]])
            ->assertOk()
            ->json('chat.id');

        $this->assertNotSame($group->id, $chatId);
        $this->assertFalse(Chat::query()->findOrFail($chatId)->is_group);
        // ein zweiter Versuch landet wieder im selben Direktchat
        $this->postJson(route('chat.store'), ['users' => [$creator->id]])
            ->assertOk()
            ->assertJsonPath('chat.id', $chatId);
    }

    #[Test]
    public function chat_is_deleted_when_the_last_member_leaves(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);
        $chat->users()->detach($creator->id);
        $this->messageIn($chat, $member);

        $this->postJson(route('chat-system.leave-chat', $chat))
            ->assertOk()
            ->assertJsonPath('chat_deleted', true);

        $this->assertNull($chat->fresh());
        $this->assertDatabaseMissing('chat_messages', ['chat_id' => $chat->id]);
    }

    #[Test]
    public function any_member_can_delete_when_the_creator_is_deactivated(): void
    {
        $creator = User::factory()->create();
        $creator->forceFill(['deactivated_at' => now()])->save();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);

        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertOk();

        $this->assertNull($chat->fresh());
    }

    #[Test]
    public function any_member_can_delete_when_the_creator_has_left_the_chat(): void
    {
        $creator = User::factory()->create();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member, User::factory()->create()]);
        $chat->users()->detach($creator->id);

        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertOk();

        $this->assertNull($chat->fresh());
    }

    #[Test]
    public function creator_who_left_the_chat_can_no_longer_delete_it(): void
    {
        $creator = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [User::factory()->create()]);
        $chat->users()->detach($creator->id);

        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertForbidden();

        $this->assertNotNull($chat->fresh());
    }

    #[Test]
    public function private_chats_can_neither_be_deleted_nor_left(): void
    {
        $creator = $this->actingAsUserWith([]);
        $partner = User::factory()->create();
        $chat = $this->groupChat($creator, [$partner], false);

        $this->deleteJson(route('chat-system.delete-chat', $chat))->assertStatus(400);
        $this->postJson(route('chat-system.leave-chat', $chat))->assertStatus(400);

        $this->assertTrue($chat->hasMember($creator));
        $this->assertNotNull($chat->fresh());
    }

    #[Test]
    public function chat_list_carries_what_the_frontend_needs_to_decide_between_delete_and_leave(): void
    {
        $creator = User::factory()->create();
        $creator->forceFill(['deactivated_at' => now()])->save();
        $member = $this->actingAsUserWith([]);
        $chat = $this->groupChat($creator, [$member]);

        $response = $this->getJson(route('chat-system.get-chats'))->assertOk();

        $this->assertSame($creator->id, $response->json('chats.0.created_by'));
        $creatorPayload = collect($response->json('chats.0.users'))->firstWhere('id', $creator->id);
        $this->assertNotNull($creatorPayload['deactivated_at'] ?? null);
        $this->assertSame($chat->id, $response->json('chats.0.id'));
    }
}
