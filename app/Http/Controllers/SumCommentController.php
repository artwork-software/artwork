<?php

namespace App\Http\Controllers;

use App\Models\SumComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SumCommentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        SumComment::create([
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'commentable_id' => $request->commentable_id,
            'commentable_type' => $request->commentable_type
        ]);

        return back();
    }

    public function destroy(SumComment $comment): RedirectResponse
    {
        $comment->delete();
        return back();
    }
}
