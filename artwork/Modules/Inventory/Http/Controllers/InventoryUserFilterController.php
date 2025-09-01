<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use Artwork\Modules\Inventory\Http\Requests\InventoryUserFilterRequest;
use Artwork\Modules\Inventory\Repositories\InventoryUserFilterRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class InventoryUserFilterController extends Controller
{
    protected InventoryUserFilterRepository $filterRepo;

    public function __construct(InventoryUserFilterRepository $filterRepo)
    {
        $this->filterRepo = $filterRepo;
    }

    /**
     * Zeigt die aktuellen Filter des Users
     */
    public function show(Request $request)
    {
        $user = Auth::user();
        $filter = $this->filterRepo->getByUser($user);
        return response()->json($filter);
    }

    /**
     * Speichert die Filter-Einstellungen des Users
     */
    public function store(InventoryUserFilterRequest $request)
    {
        $user = Auth::user();
        $data = $request->only(['category_ids', 'sub_category_ids', 'property_filters']);
        $filter = $this->filterRepo->saveForUser($user, $data);
        //return response()->json($filter);
    }
}
