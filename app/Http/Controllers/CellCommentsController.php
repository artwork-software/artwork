<?php

namespace App\Http\Controllers;

use App\Models\CellComment;
use App\Models\ColumnCell;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CellCommentsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param ColumnCell $columnCell
     * @return RedirectResponse
     */
    public function store(Request $request, ColumnCell $columnCell): RedirectResponse
    {
        $columnCell->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CellComment $cellComment
     * @return RedirectResponse
     */
    public function destroy(CellComment $cellComment): RedirectResponse
    {
        $cellComment->delete();
        return back();
    }
}
