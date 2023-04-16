<?php

namespace App\Http\Controllers;

use App\Models\Copyright;
use App\Models\CostCenter;
use App\Models\Project;
use App\Support\Services\NewHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CostCenterController extends Controller
{

    protected ?NewHistoryService $history = null;


    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\Project');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        CostCenter::create([
            'project_id' => $request->project_id,
            'name' => $request->name,
            'description' => $request->description
        ]);


    }



    /**
     * Display the specified resource.
     *
     * @param CostCenter $costCenter
     * @return Response
     */
    public function show(CostCenter $costCenter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param CostCenter $costCenter
     * @return Response
     */
    public function edit(CostCenter $costCenter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param CostCenter $costCenter
     * @return Response
     */
    public function update(Request $request, CostCenter $costCenter)
    {
        $oldCostCenter = $costCenter->name;
        $costCenter->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
        $newCostCenter = $costCenter->name;

        $this->checkProjectCostCenterChanges($costCenter->project_id, $oldCostCenter, $newCostCenter);
    }


    private function checkProjectCostCenterChanges($projectId, $oldCostCenter, $newCostCenter)
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

    /**
     * Remove the specified resource from storage.
     *
     * @param CostCenter $costCenter
     * @return Response
     */
    public function destroy(CostCenter $costCenter)
    {
        //
    }
}
