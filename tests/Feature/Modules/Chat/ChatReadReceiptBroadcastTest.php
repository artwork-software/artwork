<?php

namespace Tests\Feature\Modules\Chat;

use App\Events\MessageRead;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Lesebestätigungen kommen live wie nach dem Neuladen an: als übersetzter Anzeige-String (Cast an
 * ChatMessageRead::read_at). markMultipleAsRead sendete den ISO-Zeitstempel, das Frontend zeigte ihn roh.
 */
final class ChatReadReceiptBroadcastTest extends FeatureTestCase
{
    #[Test]
    public function marking_multiple_messages_as_read_broadcasts_the_display_timestamp(): void
    {
        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->users()->attach([$sender->id, $reader->id]);
        $message = ChatMessage::factory()->create(['chat_id' => $chat->id, 'sender_id' => $sender->id]);
        $this->actingAs($reader);

        $this->postJson(route('chat-system.mark-multiple-as-read'), ['message_ids' => [$message->id]])
            ->assertNoContent();

        $storedReadAt = $message->reads()->where('user_id', $reader->id)->first()->read_at;
        Bus::assertDispatched(
            BroadcastEvent::class,
            fn (BroadcastEvent $job) => $job->event instanceof MessageRead
                && $job->event->messageId === $message->id
                && $job->event->readAt === $storedReadAt
        );
    }
}
