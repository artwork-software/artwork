<?php

namespace App\Http\Controllers;

use App\Models\CellComment;
use App\Models\ColumnCell;
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

    public function destroy(CellComment $cellComment): RedirectResponse
    {
        $cellComment->delete();
        return back();
    }
}
