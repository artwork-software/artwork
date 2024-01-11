<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\ColumnCell;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CellCommentsController extends Controller
{
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
     * @param  \Artwork\Modules\Budget\Models\CellComment  $cellComments
     */
    public function destroy(CellComment $cellComment): \Illuminate\Http\RedirectResponse
    {
        $cellComment->delete();
        return back();
    }
}
