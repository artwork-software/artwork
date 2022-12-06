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
        return inertia('MoneySources/MoneySourceManagement', [
            'moneySources' => MoneySource::all(),
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
        ]);
    }

    public function search(SearchRequest $request)
    {
        $filteredObjects = [];
        $this->authorize('viewAny', User::class);
        if ($request->input('type') === 'single') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    continue;
                }
                $filteredObjects[] = $moneySource;
            }
            return $filteredObjects;

        } else if ($request->input('type') === 'group') {
            $moneySources = MoneySource::search($request->input('query'))->get();
            foreach ($moneySources as $moneySource) {
                if ($moneySource->is_group === 1 || $moneySource->is_group === true) {
                    $filteredObjects[] = $moneySource;
                }
            }
            return $filteredObjects;
        } else {
            return MoneySource::search($request->input('query'))->get();
        }
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $inputArray = [];

        foreach ($request->users as $requestUser) {
            $user = User::find($requestUser);
            $inputArray[] = $user;
        }

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        // user => Auth()::user()
        $user = Auth::user();
        $source = $user->money_sources()->create([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'users' => json_encode($inputArray)
        ]);

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $source->id]);
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(MoneySource $moneySource)
    {


        return inertia('MoneySources/Show', [
            'moneySource' => [
                'id' => $moneySource->id,
                'creator' => User::find($moneySource->creator_id),
                'name' => $moneySource->name,
                'amount' => $moneySource->amount,
                'source_name' => $moneySource->source_name,
                'start_date' => $moneySource->start_date,
                'end_date' => $moneySource->end_date,
                'users' => json_decode($moneySource->users),
                'group_id' => $moneySource->group_id,
                'moneySourceGroup' => MoneySource::find($moneySource->group_id),
                'subMoneySources' => MoneySource::where('group_id', $moneySource->id)->get(),
                'description' => $moneySource->description,
                'is_group' => $moneySource->is_group,
                'created_at' => $moneySource->created_at,
                'updated_at' => $moneySource->updated_at,
            ],
            'moneySourceGroups' => MoneySource::where('is_group', true)->get(),
            'moneySources' => MoneySource::where('is_group', false)->get(),
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\Response
     */
    public function edit(MoneySource $moneySource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MoneySource $moneySource)
    {
        $inputArray = [];

        foreach ($request->users as $requestUser) {
            $user = User::find($requestUser);
            $inputArray[] = $user;
        }

        if (!empty($request->amount)) {
            $amount = str_replace(',', '.', $request->amount);
        } else {
            $amount = 0.00;
        }

        $beforeSubMoneySources = MoneySource::where('group_id', $moneySource->id)->get();
        foreach ($beforeSubMoneySources as $beforeSubMoneySource) {
            $beforeSubMoneySource->update(['group_id' => null]);
        }


        $moneySource->update([
            'name' => $request->name,
            'amount' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'source_name' => $request->source_name,
            'description' => $request->description,
            'is_group' => $request->is_group,
            'group_id' => $request->group_id,
            'users' => json_encode($inputArray)
        ]);

        if ($request->is_group) {
            foreach ($request->sub_money_source_ids as $sub_money_source_id) {
                $money_source = MoneySource::find($sub_money_source_id);
                $money_source->update(['group_id' => $moneySource->id]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\MoneySource $moneySource
     * @return \Illuminate\Http\Response
     */
    public function destroy(MoneySource $moneySource)
    {
        //
    }
}
