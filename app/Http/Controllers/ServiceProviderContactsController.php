<?php

namespace App\Http\Controllers;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;

/**
 * Legt einen leeren Kontakt (Contacts-Modul, HasContacts) am Dienstleister an; Bearbeiten/Löschen
 * laufen über ArtworkSingleContact. update/destroy auf dem Legacy-Model ServiceProviderContacts
 * (Tabelle existiert nicht mehr) wurden im Sicherheits-Audit 21.09.2026 entfernt.
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
