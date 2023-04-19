<?php

namespace App\Http\Controllers;

use App\Models\MoneySource;
use App\Models\MoneySourceTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoneySourceTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(MoneySourceTask::where('money_source_id', $request->money_source_id)->get());
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
        $moneySource = MoneySource::find($request->money_source);
        $task = $moneySource->money_source_tasks()->create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'creator' => 1
        ]);

        $task->money_source_task_users()->sync($moneySource->users()->get());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MoneySourceTask  $moneySourceTask
     * @return \Illuminate\Http\Response
     */
    public function show(MoneySourceTask $moneySourceTask)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MoneySourceTask  $moneySourceTask
     * @return \Illuminate\Http\Response
     */
    public function edit(MoneySourceTask $moneySourceTask)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MoneySourceTask  $moneySourceTask
     * @return \Illuminate\Http\Response
     */
    public function markAsDone(MoneySourceTask $moneySourceTask)
    {
        $moneySourceTask->update(['done' => true]);
    }

    public function markAsUnDone(MoneySourceTask $moneySourceTask)
    {
        $moneySourceTask->update(['done' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MoneySourceTask  $moneySourceTask
     * @return \Illuminate\Http\Response
     */
    public function destroy(MoneySourceTask $moneySourceTask)
    {
        $moneySourceTask->delete();
    }
}
