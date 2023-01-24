<?php

namespace App\Http\Controllers;

use App\Models\CellComment;
use App\Models\ColumnCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CellCommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

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

    public function get(Request $request): bool|string
    {
        $comments = CellComment::where('cell_id', $request->cellId)->get();
        $output = [];
        foreach ($comments as $comment){
            $output[] = [
                'id' => $comment->id,
                'user' => $comment->user,
                'description' => $comment->description
            ];
        }
        return json_encode($output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CellComment  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function show(CellComment $cellComments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CellComment  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function edit(CellComment $cellComments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CellComment  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CellComment $cellComments)
    {
        //
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
