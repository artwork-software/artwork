<?php

namespace Artwork\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Chat\Http\Requests\StoreChatMessageReadRequest;
use Artwork\Modules\Chat\Http\Requests\UpdateChatMessageReadRequest;
use Artwork\Modules\Chat\Models\ChatMessageRead;

class ChatMessageReadController extends Controller
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
    public function store(StoreChatMessageReadRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ChatMessageRead $chatMessageRead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChatMessageRead $chatMessageRead)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChatMessageReadRequest $request, ChatMessageRead $chatMessageRead)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChatMessageRead $chatMessageRead)
    {
        //
    }
}
