<?php

namespace App\Http\Controllers;

use App\Models\MoneySource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoneySourceController extends Controller
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $inputArray = [];
        // anpassen frontend
        $requestUsers = explode(',', $request->users);
        foreach ($requestUsers as $requestUser){
            $user = User::find($requestUser);
            $inputArray[$user->id] = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'profile_photo_url' => $user->profile_photo_url
            ];
        }

        // user => Auth()::user()
        $user = User::find(1);
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
            // anpassen frontend
            $sub_money_source_ids = explode(',', $request->sub_money_source_ids);
            foreach ($sub_money_source_ids as $sub_money_source_id){
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $source->id]);
            }
        }

        return response()->json($source);
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
