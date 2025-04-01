<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Events\MessageRead;
use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatRequest;
use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Chat\Models\ChatMessage;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Inertia\Inertia;

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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chat $chat)
    {
        //
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
            ->get()
            ->map(function ($chat) {
                $chat->last_message = $chat->messages->first();
                unset($chat->messages);
                return $chat;
            });

        return response()->json([
            'chats' => $chats,
        ]);
    }

    public function getChatMessages(Chat $chat, Request $request)
    {
        /** @var User $user */
        $user = $this->auth->user();

        // Nachrichten + Sender + Reads paginiert holen (neueste zuerst)
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

        return response()->json([
            'chat' => $chat,
            'messages' => $messagesPaginator,
        ]);
    }

    public function sendMessage(Chat $chat, Request $request)
    {
        /** @var ChatMessage $message */
        $message = $chat->messages()->create([
            'sender_id' => auth()->id(),
            'cipher_for_sender' => $request->get('cipher_for_sender'),
            'ciphers_json' => $request->get('ciphers_json'),
        ]);


        // wichtig: Chat neu laden, um last_message zu aktualisieren
        $chat->load(['messages.reads', 'messages.reads.user', 'lastMessage.sender']);

        broadcast(new NewChatMessage($message))->toOthers();

        $chat->touch();

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


}
