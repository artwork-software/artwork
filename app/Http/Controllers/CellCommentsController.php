<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Services\CellCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CellCommentsController extends Controller
{
    public function __construct(private readonly CellCommentService $cellCommentService)
    {
    }

    public function store(Request $request, ColumnCell $columnCell)
    {
        $comment = $columnCell->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        // Lade User-Relation für die Response
        $comment->load('user');

        // Wenn AJAX-Request: JSON-Response
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'comment' => $comment,
                'message' => 'Comment saved successfully'
            ]);
        }

        return Redirect::back();
    }

    public function destroy(CellComment $cellComment)
    {
        $this->cellCommentService->delete($cellComment);

        // Wenn AJAX-Request: JSON-Response
        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        }

        return Redirect::back();
    }
}
