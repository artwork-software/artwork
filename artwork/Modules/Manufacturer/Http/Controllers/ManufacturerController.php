<?php

namespace Artwork\Modules\Manufacturer\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Manufacturer\Http\Requests\StoreManufacturerRequest;
use Artwork\Modules\Manufacturer\Http\Requests\UpdateManufacturerRequest;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Manufacturer\Services\ManufacturerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManufacturerController extends Controller
{
    public function __construct(protected ManufacturerService $service) {}

    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = $request->get('entitiesPerPage', 10);

        $manufacturers = $this->service->getAll($search, $perPage);

        return Inertia::render('Manufacturer/Index', [
            'manufacturers' => $manufacturers,
        ]);
    }

    public function store(StoreManufacturerRequest $request)
    {
        $this->service->store($request->validated());
    }

    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer)
    {
        $this->service->update($manufacturer, $request->validated());
    }

    public function destroy(Manufacturer $manufacturer)
    {
        $this->service->delete($manufacturer);
    }
}
