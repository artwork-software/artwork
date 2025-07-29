<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserFilterRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserFilterRequest;
use Artwork\Modules\User\Models\UserFilter;

class UserFilterController extends Controller
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
    public function store(StoreUserFilterRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserFilter $userFilter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserFilter $userFilter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserFilterRequest $request, UserFilter $userFilter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFilter $userFilter)
    {
        //
    }
}
