<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\FreelancerVacation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class FreelancerVacationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Freelancer $freelancer): \Illuminate\Http\Response
    {
        $freelancer->vacations()->create($request->only(['from', 'until']));
        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, FreelancerVacation $freelancerVacation): \Illuminate\Http\RedirectResponse
    {
        $freelancerVacation->update($request->only(['from', 'until']));
        return Redirect::back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FreelancerVacation  $freelancerVacation
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(FreelancerVacation $freelancerVacation): \Illuminate\Http\RedirectResponse
    {
        $freelancerVacation->delete();
        return Redirect::back()->with('success', 'Vacation deleted');
    }
}
