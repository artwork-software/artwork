<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Http\Requests\StoreShiftCommitWorkflowUserRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftCommitWorkflowUserRequest;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;

class ShiftCommitWorkflowUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftCommitWorkflowUserRequest $request)
    {
        $raw = $request->input('users', []);

        //dd($request->all());


        // 1) Normalisieren: Map (id => bool) oder Liste (ids) oder "1,2,3"
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)));
        }

        // Wenn es eine Map ist (z.B. [5 => true, 9 => false]), nur die "true" keys nehmen
        if (is_array($raw) && array_keys($raw) !== range(0, count($raw) - 1)) {
            $raw = array_keys(array_filter($raw, fn ($v) => filter_var($v, FILTER_VALIDATE_BOOL)));
        }

        // 2) IDs bereinigen (int, >0, unique)
        $userIds = collect($raw)
            ->flatten()
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            return redirect()->back()->withErrors(['users' => 'Mindestens ein Benutzer muss ausgewählt werden.']);
        }

        // 3) Nur existierende User übernehmen
        $validUserIds = User::whereIn('id', $userIds)->pluck('id')->all();

        if (empty($validUserIds)) {
            return redirect()->back()->withErrors(['users' => 'Die ausgewählten Benutzer sind ungültig.']);
        }

        // 4) Doppelte Einträge verhindern (DB-seitig wäre unique Index ideal)
        $existingUserIds = ShiftCommitWorkflowUser::whereIn('user_id', $validUserIds)
            ->pluck('user_id')
            ->all();

        $newUserIds = array_values(array_diff($validUserIds, $existingUserIds));

        if (!empty($newUserIds)) {
            $newEntries = array_map(fn ($id) => ['user_id' => $id], $newUserIds);
            ShiftCommitWorkflowUser::insert($newEntries);
        }

        return redirect()->back()->with('success', 'Benutzer wurden erfolgreich zum Shift-Commit-Workflow hinzugefügt.');
    }




    /**
     * Display the specified resource.
     */
    public function show(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftCommitWorkflowUserRequest $request, ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftCommitWorkflowUser $shiftCommitWorkflowUser)
    {
        $shiftCommitWorkflowUser->delete();

        return redirect()->back()->with('success', 'User removed from shift commit workflow successfully.');
    }
}
