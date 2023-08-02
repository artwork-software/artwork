<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\FreelancerVacation;
use Illuminate\Http\Request;

class FreelancerVacationController extends Controller
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
    public function store(Request $request,Freelancer $freelancer)
    {
        $freelancer->vacations()->create($request->only(['from', 'until']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\Response
     */
    public function show(FreelancerVacation $freelancerVacation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\Response
     */
    public function edit(FreelancerVacation $freelancerVacation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FreelancerVacation $freelancerVacation)
    {
        $freelancerVacation->update($request->only(['from', 'until']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\Response
     */
    public function destroy(FreelancerVacation $freelancerVacation)
    {
        $freelancerVacation->delete();
    }
}
