<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CostCenterController extends Controller
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
     * @param CostCenter $costCenter
     * @return Response
     */
    public function show(CostCenter $costCenter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param CostCenter $costCenter
     * @return Response
     */
    public function edit(CostCenter $costCenter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CostCenter $costCenter
     * @return Response
     */
    public function update(Request $request, CostCenter $costCenter)
    {
        CostCenter::where('id', $costCenter->id)->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CostCenter $costCenter
     * @return Response
     */
    public function destroy(CostCenter $costCenter)
    {
        //
    }
}
