<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommittedShiftChangeRequest;
use App\Http\Requests\UpdateCommittedShiftChangeRequest;
use Artwork\Modules\Shift\Models\CommittedShiftChange;

class CommittedShiftChangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommittedShiftChangeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CommittedShiftChange $committedShiftChange)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommittedShiftChange $committedShiftChange)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommittedShiftChangeRequest $request, CommittedShiftChange $committedShiftChange)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommittedShiftChange $committedShiftChange)
    {
        //
    }
}
