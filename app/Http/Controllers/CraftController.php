<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;

class CraftController extends Controller
{
    public function __construct(private readonly CraftService $craftService)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CraftStoreRequest $craftStoreRequest
     * @return void
     */
    public function store(CraftStoreRequest $craftStoreRequest): void
    {
        $this->craftService->storeByRequest($craftStoreRequest);
    }

    /**
     * Display the specified resource.
     *
     * @param Craft $craft
     * @return void
     */
    public function show(Craft $craft)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Craft $craft
     * @return void
     */
    public function edit(Craft $craft)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CraftUpdateRequest $craftUpdateRequest
     * @param Craft $craft
     * @return void
     */
    public function update(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $this->craftService->updateByRequest($craftUpdateRequest, $craft);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Craft $craft
     * @return void
     */
    public function destroy(Craft $craft): void
    {
        $this->craftService->delete($craft);
    }
}
