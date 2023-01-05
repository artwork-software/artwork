<?php

namespace App\Http\Controllers;

use App\Models\CellComments;
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $cell = ColumnCell::find($request->cellId);
        $cell->comments()->create([
           'user' => Auth::user(),
            'description' => $request->description
        ]);
        return back()->with('success');
    }

    public function get(Request $request){
        $comments = CellComments::where('cell_id', $request->cellId)->get();
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
     * @param  \App\Models\CellComments  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function show(CellComments $cellComments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CellComments  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function edit(CellComments $cellComments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CellComments  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CellComments $cellComments)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CellComments  $cellComments
     * @return \Illuminate\Http\Response
     */
    public function destroy(CellComments $cellComments)
    {
        //
    }
}
