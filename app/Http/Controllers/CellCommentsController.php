<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Services\BudgetCacheService;
use Artwork\Modules\Budget\Services\CellCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class CellCommentsController extends Controller
{
    public function __construct(
        private readonly CellCommentService $cellCommentService,
        private readonly BudgetCacheService $budgetCacheService
    ) {
    }

    public function store(Request $request, ColumnCell $columnCell)
    {
        $comment = $columnCell->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        // Lade User-Relation für die Response
        $comment->load('user');

        // Invalidiere Budget-Cache für das Projekt
        $project = $columnCell->column->table->project;
        if ($project) {
            $this->budgetCacheService->forgetForProjectGroup($project);
        }

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
        // Hole Projekt vor dem Löschen für Cache-Invalidierung
        $project = $cellComment->cell?->column?->table?->project;

        $this->cellCommentService->delete($cellComment);

        // Invalidiere Budget-Cache für das Projekt
        if ($project) {
            $this->budgetCacheService->forgetForProjectGroup($project);
        }

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
