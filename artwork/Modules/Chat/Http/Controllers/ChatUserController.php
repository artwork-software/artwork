<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatUserRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatUserRequest;
use Artwork\Modules\Chat\Models\ChatUser;

class ChatUserController extends Controller
{
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
    public function store(StoreChatUserRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ChatUser $chatUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChatUser $chatUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChatUserRequest $request, ChatUser $chatUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChatUser $chatUser)
    {
        //
    }
}
