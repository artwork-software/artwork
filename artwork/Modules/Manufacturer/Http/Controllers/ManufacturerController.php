<?php

namespace Artwork\Modules\Manufacturer\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Manufacturer\Http\Requests\StoreManufacturerRequest;
use Artwork\Modules\Manufacturer\Http\Requests\UpdateManufacturerRequest;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Inertia\Inertia;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Manufacturer/Index', [
            'manufacturers' => Manufacturer::all()
        ]);
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
    public function store(StoreManufacturerRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Manufacturer $manufacturer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Manufacturer $manufacturer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Manufacturer $manufacturer)
    {
        //
    }
}
