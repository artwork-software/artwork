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
use Illuminate\Http\Request;

class IndividualTimeController extends Controller
{

    protected Model $entity;

    public function __construct(
        private IndividualTimeService $individualTimeService,
        private ShiftPlanCommentService $shiftPlanCommentService
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
    public function store(Request $request): void
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
            if ($individualTime['id']) {
                $this->individualTimeService->updateForModel(
                    $modelInstance,
                    IndividualTime::find($individualTime['id']),
                    $individualTime['title'],
                    $individualTime['start_time'],
                    $individualTime['end_time'],
                    $individualTime['start_date'],
                );
            } else {
                $this->individualTimeService->createForModel(
                    $modelInstance,
                    $individualTime['title'],
                    $individualTime['start_time'],
                    $individualTime['end_time'],
                    $individualTime['start_date'],
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
     * Remove the specified resource from storage.
     */
    public function destroy(IndividualTime $individualTime): void
    {
        $individualTime->delete();
    }
}
