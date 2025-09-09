<?php

namespace Artwork\Modules\Accommodation\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Accommodation\Http\Requests\StoreAccommodationRoomTypeRequest;
use Artwork\Modules\Accommodation\Http\Requests\UpdateAccommodationRoomTypeRequest;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;

class AccommodationRoomTypeController extends Controller
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
    public function store(StoreAccommodationRoomTypeRequest $request)
    {
        $roomType = AccommodationRoomType::create($request->validated());

        return back()->with([
            'success' => true,
            'room_type' => $roomType,
            'message' => 'Room type created successfully.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AccommodationRoomType $accommodationRoomType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AccommodationRoomType $accommodationRoomType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationRoomTypeRequest $request, AccommodationRoomType $accommodationRoomType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AccommodationRoomType $accommodationRoomType)
    {
        //
    }
}
