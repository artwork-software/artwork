<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class CraftController extends Controller
{
    public function __construct(private readonly CraftService $craftService)
    {
    }

    public function store(CraftStoreRequest $craftStoreRequest): RedirectResponse
    {
        $craft = Craft::create($craftStoreRequest->only(['name', 'abbreviation', 'color', 'notify_days']));
        if (!$craftStoreRequest->assignable_by_all) {
            $craft->update(['assignable_by_all' => false]);
            $craft->users()->sync($craftStoreRequest->users);
        }

        return Redirect::back();
    }

    public function update(CraftUpdateRequest $craftUpdateRequest, Craft $craft): RedirectResponse
    {
        $this->craftService->updateByRequest($craftUpdateRequest, $craft);

        return Redirect::back();
    }

    public function destroy(Craft $craft): RedirectResponse
    {
        $this->craftService->delete($craft);

        return Redirect::back();
    }
}
