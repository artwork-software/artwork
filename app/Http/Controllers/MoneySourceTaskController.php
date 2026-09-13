<?php

namespace App\Http\Controllers;

use Artwork\Modules\MoneySource\Http\Requests\StoreMoneySourceTaskRequest;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use Artwork\Modules\MoneySource\Services\MoneySourceTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MoneySourceTaskController extends Controller
{
    public function __construct(
        private readonly MoneySourceTaskService $moneySourceTaskService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $moneySource = MoneySource::query()->findOrFail($request->integer('money_source_id'));
        $this->authorize('viewAny', [MoneySourceTask::class, $moneySource]);

        return response()->json(
            $moneySource->moneySourceTasks()->with('money_source_task_users')->get()
        );
    }

    public function create(): void
    {
    }

    public function store(StoreMoneySourceTaskRequest $request): RedirectResponse
    {
        $moneySource = MoneySource::query()->findOrFail($request->integer('money_source'));
        $this->authorize('create', [MoneySourceTask::class, $moneySource]);

        $task = $moneySource->moneySourceTasks()->create([
            'name' => $request->string('name'),
            'description' => $request->input('description'),
            'deadline' => $request->input('deadline'),
            'creator' => $request->user()->id,
        ]);

        // Im Dialog ausgewählte Personen; ohne Auswahl die Zuständigen der Quelle.
        $assignees = collect($request->input('users', []))->filter()->unique()->values();
        if ($assignees->isEmpty()) {
            $assignees = $moneySource->competent()->pluck('users.id');
        }

        $task->money_source_task_users()->sync($assignees->all());

        return redirect()->back();
    }

    public function show(MoneySourceTask $moneySourceTask): void
    {
    }

    public function edit(MoneySourceTask $moneySourceTask): void
    {
    }

    public function markAsDone(MoneySourceTask $moneySourceTask): RedirectResponse
    {
        $this->authorize('complete', $moneySourceTask);

        $this->moneySourceTaskService->markAsDone($moneySourceTask);

        return redirect()->back();
    }

    public function markAsUnDone(MoneySourceTask $moneySourceTask): RedirectResponse
    {
        $this->authorize('complete', $moneySourceTask);

        $moneySourceTask->update(['done' => false]);

        return redirect()->back();
    }

    public function destroy(MoneySourceTask $moneySourceTask): RedirectResponse
    {
        $this->authorize('delete', $moneySourceTask);

        $moneySourceTask->money_source_task_users()->detach();
        $moneySourceTask->delete();

        return redirect()->back();
    }
}
