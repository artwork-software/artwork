<?php

namespace App\Http\Controllers;

use App\Models\MoneySource;
use App\Models\MoneySourceTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MoneySourceTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(MoneySourceTask::where('money_source_id', $request->money_source_id)->get());
    }

    public function create(): void
    {
    }

    public function store(Request $request): Response
    {
        $moneySource = MoneySource::find($request->money_source);
        $task = $moneySource->moneySourceTasks()->create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'creator' => 1
        ]);

        $task->money_source_task_users()->sync($moneySource->competent()->get());
    }

    public function show(MoneySourceTask $moneySourceTask): void
    {
    }

    public function edit(MoneySourceTask $moneySourceTask): void
    {
    }

    public function markAsDone(MoneySourceTask $moneySourceTask): Response
    {
        $moneySourceTask->update(['done' => true]);
    }

    public function markAsUnDone(MoneySourceTask $moneySourceTask): void
    {
        $moneySourceTask->update(['done' => false]);
    }

    public function destroy(MoneySourceTask $moneySourceTask): Response
    {
        $moneySourceTask->delete();
    }
}
