<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\MoneySource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoneySourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('MoneySources/Show', [
            'moneySources' => MoneySource::all(),
            'moneySourceGroups' => MOneySource::where('is_group',true)->get(),
        ]);
    }

    public function search(SearchRequest $request) {

        $this->authorize('viewAny',User::class);

        return MoneySource::search($request->input('query'))->get();
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
    public function store(Request $request)
    {
        $inputArray = [];

        foreach ($request->users as $requestUser){
            $user = User::find($requestUser);
            $inputArray[$user->id] = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'profile_photo_url' => $user->profile_photo_url
            ];
        }

        // user => Auth()::user()
        $user = Auth::user();
        $source = $user->money_sources()->create([
            'name' => $request->name,
            'amount' => str_replace(',', '.', $request->amount),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'users' => json_encode($inputArray)
        ]);

        if($request->is_group){
            foreach ($request->sub_money_source_ids as $sub_money_source_id){
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $source->id]);
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MoneySource  $moneySource
     * @return \Illuminate\Http\Response
     */
    public function show(MoneySource $moneySource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MoneySource  $moneySource
     * @return \Illuminate\Http\Response
     */
    public function edit(MoneySource $moneySource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MoneySource  $moneySource
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MoneySource $moneySource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MoneySource  $moneySource
     * @return \Illuminate\Http\Response
     */
    public function destroy(MoneySource $moneySource)
    {
        //
    }
}
