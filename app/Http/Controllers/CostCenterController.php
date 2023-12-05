<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use App\Models\CostCenter;
use App\Models\Project;
use App\Support\Services\NewHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class CostCenterController extends Controller
{
    protected ?NewHistoryService $history = null;


    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Project');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        CostCenter::create([
            'project_id' => $request->project_id,
            'name' => $request->name,
            'description' => $request->description
        ]);
        $this->history->createHistory($request->project_id, 'Kostenträger hinzugefügt');
        return Redirect::back();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CostCenter $costCenter
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CostCenter $costCenter): \Illuminate\Http\RedirectResponse
    {
        $oldCostCenter = $costCenter->name;
        $costCenter->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
        $newCostCenter = $costCenter->name;

        $this->checkProjectCostCenterChanges($costCenter->project_id, $oldCostCenter, $newCostCenter);
        return Redirect::back();
    }


    private function checkProjectCostCenterChanges($projectId, $oldCostCenter, $newCostCenter): void
    {
        if ($newCostCenter === null && $oldCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger gelöscht');
        }
        if ($oldCostCenter === null && $newCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger hinzugefügt');
        }
        if ($oldCostCenter !== $newCostCenter && $oldCostCenter !== null && $newCostCenter !== null) {
            $this->history->createHistory($projectId, 'Kostenträger geändert');
        }
    }
}
