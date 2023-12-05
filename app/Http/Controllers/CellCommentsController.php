<?php

namespace App\Http\Controllers;

use App\Models\CellComment;
use App\Models\ColumnCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CellCommentsController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request, ColumnCell $columnCell): \Illuminate\Http\RedirectResponse
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
     * @param  \App\Models\CellComment  $cellComments
     */
    public function destroy(CellComment $cellComment): \Illuminate\Http\RedirectResponse
    {
        $cellComment->delete();
        return back();
    }
}
