<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserWorkTimePatternRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserWorkTimePatternRequest;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Services\UserWorkTimePatternService;
use Inertia\Inertia;

class UserWorkTimePatternController extends Controller
{
    public function __construct(
        protected UserWorkTimePatternService $userWorkTimePatternService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings/WorkTimePattern/Index', [
            'workTimePatterns' => UserWorkTimePattern::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserWorkTimePatternRequest $request): \Illuminate\Http\RedirectResponse
    {
        $this->userWorkTimePatternService->create($request->validated());

        return redirect()->route('shift.work-time-pattern')
            ->with('success', 'Work time pattern created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateUserWorkTimePatternRequest $request,
        UserWorkTimePattern $userWorkTimePattern
    ): \Illuminate\Http\RedirectResponse {
        $this->userWorkTimePatternService->update($userWorkTimePattern, $request->validated());

        return redirect()->route('shift.work-time-pattern')
            ->with('success', 'Work time pattern updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserWorkTimePattern $userWorkTimePattern): \Illuminate\Http\RedirectResponse
    {
        // Check if the work time pattern is in use by any user
        $userWorkTimePattern->userWorkTime()->update([
            'work_time_pattern_id' => null,
        ]);

        $userWorkTimePattern->delete();

        return redirect()->route('shift.work-time-pattern')
            ->with('success', 'Work time pattern deleted successfully.');
    }
}
