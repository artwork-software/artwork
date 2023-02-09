<?php

namespace App\Http\Controllers;

use App\Models\CellCalculations;
use Illuminate\Http\Request;

class CellCalculationsController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CellCalculations  $cellCalculations
     * @return \Illuminate\Http\Response
     */
    public function show(CellCalculations $cellCalculations)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CellCalculations  $cellCalculations
     * @return \Illuminate\Http\Response
     */
    public function edit(CellCalculations $cellCalculations)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CellCalculations  $cellCalculations
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CellCalculations $cellCalculations)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CellCalculations  $cellCalculations
     * @return \Illuminate\Http\Response
     */
    public function destroy(CellCalculations $cellCalculation): \Illuminate\Http\RedirectResponse
    {
        $cellCalculation->delete();
        return back();
    }
}
