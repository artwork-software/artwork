<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserFilterRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserFilterRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Illuminate\Http\Request;

class UserFilterController extends Controller
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
    public function store(StoreUserFilterRequest $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserFilter $userFilter): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserFilter $userFilter): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserFilterRequest $request, User $user): void
    {
        $user->userFilters()->updateOrCreate(
            ['filter_type' => $request->input('filter_type')],
            [
                'room_ids' => $this->nullableArray($request->collect('room_ids')),
                'area_ids' => $this->nullableArray($request->collect('area_ids')),
                'room_category_ids' => $this->nullableArray($request->collect('room_category_ids')),
                'room_attribute_ids' => $this->nullableArray($request->collect('room_attribute_ids')),
                'event_type_ids' => $this->nullableArray($request->collect('event_type_ids')),
                'event_property_ids' => $this->nullableArray($request->collect('event_property_ids')),
                'craft_ids' => $this->nullableArray($request->collect('craft_ids')),
                'project_state_ids' => $this->nullableArray($request->collect('project_state_ids')),
            ]
        );
    }

    /**
     * Schichtplan-Personenfilter "nur Personen mit offenen Regelverstößen". Eigener Endpunkt, damit
     * Zähler-Chip und Filter-Modal das Flag setzen können, ohne die übrigen Filterwerte anzufassen.
     */
    public function updateOpenViolationsFilter(Request $request, User $user): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $validated = $request->validate([
            'filter_type' => ['required', 'string', 'in:shift_filter,shift_daily_filter'],
            'show_only_users_with_open_violations' => ['required', 'boolean'],
        ]);

        $user->userFilters()->updateOrCreate(
            ['filter_type' => $validated['filter_type']],
            ['show_only_users_with_open_violations' => (bool) $validated['show_only_users_with_open_violations']]
        );
    }

    /**
     * Gibt ein Array zurück oder null, wenn leer.
     */
    private function nullableArray($collection): ?array
    {
        $array = $collection->filter()->all();
        return empty($array) ? null : array_values($array);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFilter $userFilter): void
    {
        //
    }
}
