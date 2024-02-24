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

    public function store(Request $request, ColumnCell $columnCell): RedirectResponse
    {
        $columnCell->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        return Redirect::back();
    }

    public function destroy(CellComment $cellComment): RedirectResponse
    {
        $this->cellCommentService->delete($cellComment);

        return Redirect::back();
    }
}
