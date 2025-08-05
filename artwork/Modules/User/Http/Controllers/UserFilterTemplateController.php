<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserFilterTemplateRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserFilterTemplateRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilterTemplate;

class UserFilterTemplateController extends Controller
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
    public function store(StoreUserFilterTemplateRequest $request, User $user): void
    {
        $user->userFilterTemplates()->create([
            'name' => $request->input('name'),
            'room_ids' => $this->nullableArray($request->collect('room_ids')),
            'area_ids' => $this->nullableArray($request->collect('area_ids')),
            'room_category_ids' => $this->nullableArray($request->collect('room_category_ids')),
            'room_attribute_ids' => $this->nullableArray($request->collect('room_attribute_ids')),
            'event_type_ids' => $this->nullableArray($request->collect('event_type_ids')),
            'event_property_ids' => $this->nullableArray($request->collect('event_property_ids')),
            'craft_ids' => $this->nullableArray($request->collect('craft_ids')),
            'filter_type' => $request->input('filter_type'),
        ]);
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
     * Display the specified resource.
     */
    public function show(UserFilterTemplate $userFilterTemplate): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserFilterTemplate $userFilterTemplate): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserFilterTemplateRequest $request, UserFilterTemplate $userFilterTemplate): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFilterTemplate $filter): void
    {
        $filter->delete();
    }
}
