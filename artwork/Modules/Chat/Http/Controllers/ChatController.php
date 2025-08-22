<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Events\MessageRead;
use App\Events\NewChatMessage;
use App\Events\ChatCreated;
use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatRequest;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;
use Illuminate\Support\Facades\Crypt;

class ChatController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeChat(StoreChatRequest $request): \Illuminate\Http\JsonResponse
    {
        $currentUserId = $this->auth->id();

        // Akzeptiere Array aus reinen IDs
        $userIds = collect($request->input('users'))
            ->push($currentUserId)
            ->unique()
            ->sort()
            ->values();

        // Suche existierenden Chat mit exakt diesen Teilnehmern
        $existingChat = Chat::whereHas('users', function ($q) use ($userIds) {
            $q->whereIn('users.id', $userIds);
        }, '=', $userIds->count())
            ->with('users') // wichtig für .users->pluck() später
            ->withCount('users')
            ->get()
            ->filter(function ($chat) use ($userIds) {
                return $chat->users_count === $userIds->count() &&
                    $chat->users->pluck('id')->sort()->values()->toArray() === $userIds->toArray();
            })
            ->first();

        if ($existingChat) {
            return response()->json(['chat' => $existingChat]);
        }

        $chat = Chat::create([
            'name' => $request->name ?? 'Kein Name',
            'created_by' => $currentUserId,
        ]);

        $chat->users()->attach($userIds);

        if ($userIds->count() === 2) {
            // Einzelchat (1 anderer + currentUser)
            $otherUser = User::find($userIds->first() === $currentUserId ? $userIds[1] : $userIds[0]);
            $chat->update([
                'name' => $otherUser?->name ?? 'Privater Chat',
                'is_group' => false,
            ]);
        } else {
            $chat->update(['is_group' => true]);
        }

        // NEU: Nutzer laden und Broadcast senden
        $chat->load('users');
        broadcast(new ChatCreated($chat));

        return response()->json(['chat' => $chat]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Chat $chat)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chat $chat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChatRequest $request, Chat $chat)
    {
        // Authorization and validation are now handled by UpdateChatRequest
        $chat->update([
            'name' => $request->validated()['name'],
        ]);

        // Reload the chat with relationships
        $chat->load('users');

        return response()->json([
            'message' => 'Group chat renamed successfully',
            'chat' => $chat,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chat $chat)
    {
        /** @var User $user */
        $user = $this->auth->user();

        // Check if user is a member of the chat
        if (!$chat->users()->where('users.id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow deleting group chats
        if (!$chat->is_group) {
            return response()->json(['error' => 'Can only delete group chats'], 400);
        }

        // Store chat ID for response
        $chatId = $chat->id;

        // Delete related data first (cascade)
        // Delete message reads
        \DB::table('chat_message_reads')
            ->whereIn('message_id', function($query) use ($chatId) {
                $query->select('id')
                    ->from('chat_messages')
                    ->where('chat_id', $chatId);
            })
            ->delete();

        // Delete messages
        $chat->messages()->delete();

        // Delete chat-user relationships
        $chat->users()->detach();

        // Delete the chat itself
        $chat->delete();

        return response()->json([
            'message' => 'Group chat deleted successfully',
            'chat_id' => $chatId,
        ]);
    }

    public function setPublicKey(Request $request)
    {
        /** @var User $user */
        $user = $this->auth->user();

        $user->update([
            'chat_public_key' => $request->get('public_key'),
        ]);

        return response()->json([
            'message' => 'Public key updated successfully',
        ]);
    }

    public function getChats()
    {
        /** @var User $user */
        $user = $this->auth->user();

        $chats = $user->chats()
            ->with(['users', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->whereDoesntHave('reads', fn($sub) => $sub->where('user_id', $user->id))
                    ->where('sender_id', '!=', $user->id);
            }])
            ->orderByDesc('updated_at')
            ->get();

        // KORREKT: letzte Nachricht entschlüsseln, als Relation setzen und messages-Relation entfernen
        foreach ($chats as $chat) {
            $last = $chat->messages->first();
            if ($last) {
                $last->setAttribute('message', $this->decryptMessage($last->message));
                $chat->setRelation('last_message', $last);
            } else {
                $chat->setRelation('last_message', null);
            }
            $chat->unsetRelation('messages');
        }

        return response()->json([
            'chats' => $chats,
        ]);
    }

    public function getChatMessages(Chat $chat, Request $request)
    {
        /** @var User $user */
        $user = $this->auth->user();

        $messagesPaginator = $chat->messages()
            ->with(['sender', 'reads.user'])
            ->orderByDesc('created_at')
            ->paginate(15);

        // Gelesen markieren (aber NUR was geladen wurde!)
        foreach ($messagesPaginator->items() as $message) {
            if ($message->sender_id !== $user->id && !$message->reads->where('user_id', $user->id)->first()) {
                $message->reads()->create([
                    'user_id' => $user->id,
                    'read_at' => now(),
                ]);
            }
        }

        // Chat ohne große Messages laden
        $chat->load('users');

        // Entschlüsselte Inhalte für die aktuelle Seite bereitstellen
        $messagesPaginator->getCollection()->transform(function ($message) {
            $message->message = $this->decryptMessage($message->message);
            return $message;
        });

        return response()->json([
            'chat' => $chat,
            'messages' => $messagesPaginator,
        ]);
    }

    public function sendMessage(Chat $chat, Request $request)
    {
        // Ursprünglichen Text (mit aktuellem nl2br-Verhalten) holen
        $plain = nl2br($request->get('message'));

        /** @var ChatMessage $message */
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            // Verschlüsselt ablegen
            'message' => $this->encryptMessage($plain),
        ]);

        // Chat neu laden (Preview/Reads)
        $chat->load(['messages.reads', 'messages.reads.user', 'lastMessage.sender']);

        // updated_at aktualisieren, damit Sortierung stimmt
        $chat->touch();

        // Für Broadcast/Response Klartext in-memory setzen (DB bleibt verschlüsselt)
        $message->setAttribute('message', $plain);

        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json([
            'message' => $message,
            'chat' => $chat,
        ]);
    }

    public function markAsRead(ChatMessage $message)
    {
        /** @var User $user */
        $user = $this->auth->user();

        if (!$message->reads()->where('user_id', $user->id)->exists() && $message->sender_id !== $user->id) {
            $read = $message->reads()->create([
                'user_id' => $user->id,
                'read_at' => now(),
            ]);

            broadcast(new MessageRead($message, $user->id, $read->read_at));
        }

        return response()->noContent();
    }

    public function markMultipleAsRead(Request $request)
    {
        /** @var User $user */
        $user = $this->auth->user();

        $messageIds = $request->input('message_ids', []);
        $readAt = now();

        $messages = ChatMessage::whereIn('id', $messageIds)
            ->where('sender_id', '!=', $user->id)
            ->with('reads')
            ->get();

        foreach ($messages as $message) {
            $alreadyRead = $message->reads->firstWhere('user_id', $user->id);
            if (!$alreadyRead) {
                $message->reads()->create([
                    'user_id' => $user->id,
                    'read_at' => $readAt,
                ]);

                broadcast(new MessageRead($message, $user->id, $readAt));
            }
        }

        return response()->noContent();
    }


    // --- Verschlüsselungs-Helfer ---
    private function encryptMessage(?string $text): string
    {
        return Crypt::encryptString($text ?? '');
    }

    private function decryptMessage(?string $ciphertext): string
    {
        if ($ciphertext === null) {
            return '';
        }
        try {
            return Crypt::decryptString($ciphertext);
        } catch (\Throwable $e) {
            // Fallback für Altbestände (bereits Klartext)
            return $ciphertext;
        }
    }
}
