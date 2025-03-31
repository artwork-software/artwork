<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatRequest;
use Artwork\Modules\Chat\Models\Chat;
use Illuminate\Auth\AuthManager;

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
    public function store(StoreChatRequest $request)
    {
        $users = $request->collect('users');
        $chat = Chat::create([
            'name' => $request->name ?? 'no name',
            'created_by' => $this->auth->user()->id,
        ]);

        $chat->users()->attach($users->pluck('id'));
        // add the current user to the chat
        $chat->users()->attach($this->auth->user()->id);

        if ($users->count() === 1) {
            $chat->update([
                'name' => $users->first()['name'],
                'is_group' => false,
            ]);
        } else {
            $chat->update([
                'is_group' => true,
            ]);
        }
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
}
