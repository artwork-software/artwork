<?php

namespace App\Http\Controllers;

use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RowCommentController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request, SubPositionRow $row): RedirectResponse
    {
        $row->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        return back();
    }


    public function show(RowComment $rowComment): void
    {
    }


    public function edit(RowComment $rowComment): void
    {
    }


    public function update(Request $request, RowComment $rowComment): void
    {
    }

    public function destroy(RowComment $rowComment): RedirectResponse
    {
        $rowComment->delete();
        return back();
    }
}
