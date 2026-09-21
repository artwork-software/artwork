<?php

namespace App\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;

/**
 * Legt einen leeren Kontakt (Contacts-Modul) am Dienstleister an; Bearbeiten/Löschen laufen über ArtworkSingleContact.
 */
class ServiceProviderContactsController extends Controller
{
    public function store(ServiceProvider $serviceProvider): void
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->contacts()->create([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone_number' => ''
        ]);
    }
}
