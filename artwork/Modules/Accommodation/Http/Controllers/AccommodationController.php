<?php

namespace Artwork\Modules\Accommodation\Http\Controllers;


use App\Http\Controllers\Controller;
use Artwork\Modules\Accommodation\Http\Requests\StoreAccommodationRequest;
use Artwork\Modules\Accommodation\Http\Requests\UpdateAccommodationRequest;
use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\Accommodation\Services\AccommodationService;
use Inertia\Inertia;

class AccommodationController extends Controller
{

    public function __construct(
        protected AccommodationService $accommodationService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Accommodation/index', [
            'accommodations' => Accommodation::with(['roomTypes'])->get(),
            'roomTypes' => AccommodationRoomType::all(),
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
        $accommodation = $this->accommodationService->store($request->validated());

        // Handle room types with costs
        $roomTypes = $request->input('room_types', []);
        $roomTypeCosts = $request->input('room_type_costs', []);

        $syncData = [];
        foreach ($roomTypes as $roomTypeId) {
            $syncData[$roomTypeId] = [
                'cost_per_night' => $roomTypeCosts[$roomTypeId] ?? 0.00
            ];
        }

        $accommodation->roomTypes()->sync($syncData);

        return redirect()->route('accommodation.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation)
    {
        return Inertia::render('Accommodation/Show', [
            'accommodation' => $accommodation->load(['contacts', 'roomTypes' => function($query) {
                $query->withPivot('cost_per_night');
            }]),
            'roomTypes' => AccommodationRoomType::all(),
        ]);
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
        $accommodation = $this->accommodationService->update($accommodation, $request->validated());

        // Handle room types with costs
        $roomTypes = $request->input('room_types', []);
        $roomTypeCosts = $request->input('room_type_costs', []);

        $syncData = [];
        foreach ($roomTypes as $roomTypeId) {
            $syncData[$roomTypeId] = [
                'cost_per_night' => $roomTypeCosts[$roomTypeId] ?? 0.00
            ];
        }

        $accommodation->roomTypes()->sync($syncData);


        return redirect()->route('accommodation.show', $accommodation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accommodation $accommodation)
    {
        $this->accommodationService->destroy($accommodation);

        return redirect()->route('accommodation.index');
    }
}
