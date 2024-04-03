<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\Component;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\ResponseFactory|\Inertia\Response
    {
        return inertia('Settings/ComponentManagement/Index', [
            'components' => Component::get()->groupBy('type'),
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Component $component): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Component $component): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Component $component): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Component $component): void
    {
        //
    }
}
