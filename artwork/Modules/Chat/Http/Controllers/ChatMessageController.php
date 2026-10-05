<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatMessageRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatMessageRequest;
use Artwork\Modules\Chat\Models\ChatMessage;
use App\Events\NewChatMessage;

class ChatMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChatMessageRequest $request)
    {
        $data = $request->validated();

        $message = ChatMessage::create([
            'chat_id'   => $data['chat_id'],
            'sender_id' => auth()->id(),
            'message'   => $data['message'], // Plaintext
        ])->load(['sender', 'reads']);

        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChatMessage $chatMessage): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChatMessage $chatMessage): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChatMessageRequest $request, ChatMessage $chatMessage): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChatMessage $chatMessage): void
    {
        //
    }
}
