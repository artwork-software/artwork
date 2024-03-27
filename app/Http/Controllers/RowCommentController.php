<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Services\RowCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RowCommentController extends Controller
{
    public function __construct(
        private readonly RowCommentService $rowCommentService
    ) {
    }

    public function store(Request $request, SubPositionRow $row): RedirectResponse
    {
        $row->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        return back();
    }

    public function destroy(RowComment $rowComment): RedirectResponse
    {
        $this->rowCommentService->delete($rowComment);

        return Redirect::back();
    }
}
