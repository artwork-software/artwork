<?php

namespace App\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProviderContacts\Models\ServiceProviderContacts;
use Illuminate\Http\Request;

class ServiceProviderContactsController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(ServiceProvider $serviceProvider): void
    {
        $serviceProvider->contacts()->create([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone_number' => ''
        ]);
    }

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(Request $request, ServiceProviderContacts $serviceProviderContacts): void
    {
        $serviceProviderContacts->update($request->only([
            'first_name',
            'last_name',
            'email',
            'phone_number'
        ]));
    }

    public function destroy(ServiceProviderContacts $serviceProviderContacts): void
    {
        $serviceProviderContacts->delete();
    }
}
