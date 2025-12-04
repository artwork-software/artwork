<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserContractAssignRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserContractAssignRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserContractAssignController extends Controller
{
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
    public function store(StoreUserContractAssignRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        // Nur freigegebene Felder übernehmen – niemals user_id vom Request
        $data = $request->safe()->except(['user_id']);

        try {
            DB::transaction(function () use ($user, $data): void {
                // Extract work time pattern related fields
                $workTimeFields = [
                    'id',
                    'work_time_pattern_id',
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday',
                    'valid_from',
                    'valid_until'
                ];

                // Don't filter work time data - we need empty strings too
                $workTimeData = collect($data)->only($workTimeFields)->all();
                $contractData = collect($data)->except($workTimeFields)->filter()->all();

                // Update or create user contract if there's contract data
                if (!empty($contractData)) {
                    $user->contract()->updateOrCreate([], $contractData);
                }

                // Check if any work time fields are present (excluding 'id')
                $hasWorkTimeData = collect($workTimeData)
                    ->except(['id'])
                    ->filter(fn($value) => !is_null($value))
                    ->isNotEmpty();

                // Update or create work time pattern if there's work time data
                if ($hasWorkTimeData) {
                    $workTimeId = $workTimeData['id'] ?? null;
                    unset($workTimeData['id']); // Remove id from data array
                    $workTimeData['user_id'] = $user->id;

                    // Set default date if not provided
                    if (!isset($workTimeData['valid_from']) || empty($workTimeData['valid_from'])) {
                        $workTimeData['valid_from'] = now()->toDateString();
                    }

                    // If id is provided, update that specific record
                    if ($workTimeId) {
                        $user->workTimes()->where('id', $workTimeId)->update($workTimeData);
                    } else {
                        // Otherwise, find existing work time based on user_id or create new one
                        $conditions = ['user_id' => $user->id];
                        if (isset($workTimeData['valid_from'])) {
                            $conditions['valid_from'] = $workTimeData['valid_from'];
                        }

                        $user->workTimes()->updateOrCreate($conditions, $workTimeData);
                    }
                }
            });

            return back()->with('success', __('User contract assigned successfully.'));
        } catch (\Throwable $e) {
            Log::error('Failed to assign user contract', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('Could not assign user contract. Please try again.'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserContractAssignRequest $request, UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserContractAssign $userContractAssign): void
    {
        //
    }
}
