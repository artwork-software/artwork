<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\SumComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SumCommentController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $comment = SumComment::create([
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'commentable_id' => $request->commentable_id,
            'commentable_type' => $request->commentable_type
        ]);

        // Load user relation for the response
        $comment->load('user');

        if ($request->wantsJson()) {
            return response()->json([
                'comment' => $comment
            ]);
        }

        return back();
    }

    public function destroy(SumComment $comment, Request $request): JsonResponse|RedirectResponse
    {
        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true
            ]);
        }

        return back();
    }
}
