<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\ServiceProviderContacts;
use Illuminate\Http\Request;

class ServiceProviderContactsController extends Controller
{
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
    public function store(Request $request, ServiceProvider $serviceProvider)
    {
        $serviceProvider->contacts()->create();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ServiceProviderContacts  $serviceProviderContacts
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceProviderContacts $serviceProviderContacts)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceProviderContacts  $serviceProviderContacts
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceProviderContacts $serviceProviderContacts)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceProviderContacts  $serviceProviderContacts
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceProviderContacts $serviceProviderContacts)
    {
        $serviceProviderContacts->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone_number'
        ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceProviderContacts  $serviceProviderContacts
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServiceProviderContacts $serviceProviderContacts)
    {
        $serviceProviderContacts->delete();
    }
}
