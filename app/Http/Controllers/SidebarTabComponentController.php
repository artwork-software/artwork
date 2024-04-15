<?php

namespace App\Http\Controllers;

use Artwork\Modules\ProjectTab\Models\SidebarTabComponent;
use Illuminate\Http\Request;

class SidebarTabComponentController extends Controller
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
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SidebarTabComponent $sidebarTabComponent): void
    {
        //
    }

    public function removeComponent(SidebarTabComponent $sidebarTabComponent): void
    {
        $sidebarTabComponent->delete();
    }
}
