<?php

namespace Artwork\Modules\MaterialSet\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Artwork\Modules\MaterialSet\Http\Requests\StoreMaterialSetRequest;
use Artwork\Modules\MaterialSet\Http\Requests\UpdateMaterialSetRequest;
use Artwork\Modules\MaterialSet\Services\MaterialSetService;
use Inertia\Inertia;

class MaterialSetController extends Controller
{
    public function __construct(protected MaterialSetService $service) {}

    public function index(): \Inertia\Response
    {
        $materialSets = MaterialSet::with('items.article', 'items.article.category', 'items.article.subCategory')->get();


        return Inertia::render('MaterialSet/Index', [
            'materialSets' => $materialSets
        ]);
    }

    public function store(StoreMaterialSetRequest $request): void
    {
        $this->service->store($request);
    }

    public function update(UpdateMaterialSetRequest $request, MaterialSet $set): void
    {
        $this->service->update($set, $request);
    }

    public function destroy(MaterialSet $set): void
    {
        $this->service->delete($set);
    }

}