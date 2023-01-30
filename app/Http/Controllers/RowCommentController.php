<?php

namespace App\Http\Controllers;

use App\Models\RowComment;
use App\Models\SubPositionRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RowCommentController extends Controller
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, SubPositionRow $row)
    {

        $row->comments()->create([
            'user_id' => Auth::id(),
            'description' => $request->description
        ]);

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RowComment  $rowComment
     * @return \Illuminate\Http\Response
     */
    public function show(RowComment $rowComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RowComment  $rowComment
     * @return \Illuminate\Http\Response
     */
    public function edit(RowComment $rowComment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RowComment  $rowComment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RowComment $rowComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RowComment  $rowComment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RowComment $rowComment)
    {
        $rowComment->delete();
        return back();
    }
}
