<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CraftController extends Controller
{
    public function __construct(private readonly CraftService $craftService)
    {
    }

    public function store(CraftStoreRequest $craftStoreRequest): RedirectResponse
    {

        $craft = Craft::create($craftStoreRequest->only([
            'name',
            'abbreviation',
            'color',
            'notify_days',
            'universally_applicable',
            'inventory_planned_by_all'
        ]));

        if (!$craftStoreRequest->assignable_by_all) {
            $craft->update([
                'assignable_by_all' => false,
            ]);
            $craft->craftShiftPlaner()->sync($craftStoreRequest->users);
        }

        if (!$craftStoreRequest->inventory_planned_by_all) {
            $craft->update([
                'inventory_planned_by_all' => false,
            ]);
            $craft->craftInventoryPlaner()->sync($craftStoreRequest->users_for_inventory);
        }


        if ($craftStoreRequest->has('qualifications')) {
            // Der Request liefert Qualifikationen als Array von Objekten
            // (mit je einem "id"-Feld). Für belongsToMany::sync() benötigen
            // wir jedoch ein reines Array von IDs. Andernfalls versucht Eloquent,
            // die Objekt-Felder als Pivot-Attribute zu speichern, was zu
            // SQL-Fehlern (z. B. unbekannte Spalten wie "available") führt.

            $qualificationIds = collect($craftStoreRequest->input('qualifications', []))
                ->map(function ($qualification) {
                    // Erwartet wird ein Array mit ['id' => int]. Falls doch
                    // nur eine ID geliefert wird, übernehmen wir diese.
                    if (is_array($qualification)) {
                        return $qualification['id'] ?? null;
                    }
                    return $qualification; // Fallback: bereits eine ID
                })
                ->filter(fn ($id) => !is_null($id))
                ->values()
                ->all();

            $craft->qualifications()->sync($qualificationIds);
        }

        $craft->update([
            'position' => Craft::max('position') + 1
        ]);

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

    public function reorder(Request $request): void
    {
        $crafts = $request->get('crafts');
        $this->craftService->reorder($crafts);
    }
}
