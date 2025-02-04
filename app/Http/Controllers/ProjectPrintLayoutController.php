<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectPrintLayout\Http\Requests\StoreProjectPrintLayoutRequest;
use Artwork\Modules\ProjectPrintLayout\Services\ProjectPrintLayoutService;

use Artwork\Modules\ProjectTab\Models\Component;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectPrintLayoutController extends Controller
{
    public function __construct(private readonly ProjectPrintLayoutService $projectService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Settings/ProjectPrintLayout/Index', [
            'components' => Component::notSpecial()->get()->groupBy('type'),
            'componentsSpecial' => Component::isSpecial()->get(),
            'layouts' => $this->projectService->getProjectPrintLayouts(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectPrintLayoutRequest $request): void
    {
        if ($request->validated()) {
            $this->projectService->storeProjectPrintLayout($request->all());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
