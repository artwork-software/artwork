<?php

namespace App\Http\Controllers;

use App\Models\ProjectHeadline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ProjectHeadlineController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        ProjectHeadline::create([
            "name" => $request->name,
            'order' => ProjectHeadline::max('order') + 1,
        ]);

        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProjectHeadline  $projectHeadline
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ProjectHeadline $projectHeadline)
    {
        $projectHeadline->update([ "name" => $request->name]);

        return Redirect::back();
    }

    public function updateOrder(Request $request)
    {

        foreach ($request->headlines as $headline) {
            ProjectHeadline::findOrFail($headline['id'])->update(['order' => $headline['order']]);
        }

        return Redirect::back();
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProjectHeadline  $projectHeadline
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectHeadline $projectHeadline)
    {
        //
    }
}
