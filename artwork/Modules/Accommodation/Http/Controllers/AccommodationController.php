<?php

namespace Artwork\Modules\Accommodation\Http\Controllers;


use App\Http\Controllers\Controller;
use Artwork\Modules\Accommodation\Http\Requests\StoreAccommodationRequest;
use Artwork\Modules\Accommodation\Http\Requests\UpdateAccommodationRequest;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Inertia\Inertia;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Accommodation/index', [
            'accommodations' => Accommodation::all(),
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
    public function store(StoreAccommodationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Accommodation $accommodation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accommodation $accommodation)
    {
        //
    }
}
