<?php

namespace App\Http\Controllers;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Services\IndividualTimeService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Artwork\Modules\Shift\Services\ShiftPlanCommentService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Illuminate\Http\Request;

class IndividualTimeController extends Controller
{

    protected Model $entity;

    public function __construct(
        private IndividualTimeService $individualTimeService,
        private ShiftPlanCommentService $shiftPlanCommentService,
        private WorkingHourCacheService $workingHourCacheService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $modelClass = match ($request->integer('modelType')) {
            1 => Freelancer::class,
            2 => ServiceProvider::class,
            default => User::class,
        };

        // Find the specific model instance by ID
        $modelInstance = $modelClass::findOrFail($request->integer('modelId'));
        $individualTimes = $request->get('individualTimes');

        foreach ($individualTimes as $individualTime) {
            if (!empty($individualTime['id'])) {
                // Ensure the individual time belongs to the given model; if not found, create a new one
                $existing = $modelInstance->individualTimes()->find($individualTime['id']);

                if ($existing) {
                    $this->individualTimeService->updateForModel(
                        $modelInstance,
                        $existing,
                        $individualTime['title'] ?? null,
                        $individualTime['start_time'] ?? null,
                        $individualTime['end_time'] ?? null,
                        $individualTime['start_date'],
                        $individualTime['break_minutes'] ?? 0,
                    );
                } else {
                    $this->individualTimeService->createForModel(
                        $modelInstance,
                        $individualTime['title'] ?? null,
                        $individualTime['start_time'] ?? null,
                        $individualTime['end_time'] ?? null,
                        $individualTime['start_date'],
                        $individualTime['break_minutes'] ?? 0,
                    );
                }
            } else {
                $this->individualTimeService->createForModel(
                    $modelInstance,
                    $individualTime['title'] ?? null,
                    $individualTime['start_time'] ?? null,
                    $individualTime['end_time'] ?? null,
                    $individualTime['start_date'],
                    $individualTime['break_minutes'] ?? 0,
                );
            }
        }


        $shiftComment = $request->get('shift_comment');

        if ($shiftComment['comment'] !== null || !isset($shiftComment['id'])) {
            $this->shiftPlanCommentService->addOrUpdateShiftPlanComment(
                $modelInstance,
                $shiftComment['comment'],
                $shiftComment['date'],
                $shiftComment['id'] ?? null,
            );
        } elseif (isset($shiftComment['id'])) {
            $this->shiftPlanCommentService->addOrUpdateShiftPlanComment(
                $modelInstance,
                '',
                $shiftComment['date'],
                $shiftComment['id'] ?? null,
            );
        }

        return response()->json([
            'individual_times' => $modelInstance->individualTimes()->get(),
            'shift_comment' => $modelInstance->shiftPlanComments()
                ->where('date', $request->input('shift_comment.date'))->first(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(IndividualTime $individualTimes): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IndividualTime $individualTimes): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IndividualTime $individualTimes): void
    {
        //
    }

    /**
     * Update a single individual time for the current user from shift plan.
     */
    public function updateSingle(Request $request, IndividualTime $individualTime): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
        ]);

        $startTime = $validated['start_time'] ?? $individualTime->start_time;
        $endTime = $validated['end_time'] ?? $individualTime->end_time;
        $breakMinutes = $validated['break_minutes'] ?? $individualTime->break_minutes;

        $updateData = [
            'title' => $validated['title'] ?? $individualTime->title,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'break_minutes' => $breakMinutes,
        ];

        // Recalculate working_time_minutes when time or break changes
        if ($startTime && $endTime) {
            $start = \Carbon\Carbon::parse($individualTime->start_date . ' ' . $startTime);
            $end = \Carbon\Carbon::parse($individualTime->start_date . ' ' . $endTime);
            if ($end->lte($start)) {
                $end->addDay();
            }
            $updateData['working_time_minutes'] = max(0, $start->diffInMinutes($end) - ($breakMinutes ?? 0));
        }

        $individualTime->update($updateData);

        $owner = $individualTime->timeable;
        if ($owner) {
            $this->workingHourCacheService->forgetForEntity(
                WorkingHourCacheService::entityType($owner),
                $owner->id
            );
        }

        return redirect()->back()->with('success', 'Individual time updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IndividualTime $individualTime): void
    {
        $owner = $individualTime->timeable;
        $individualTime->delete();

        if ($owner) {
            $this->workingHourCacheService->forgetForEntity(
                WorkingHourCacheService::entityType($owner),
                $owner->id
            );
        }
    }
}
