<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Einmalige Übernahme der Legacy-Tabelle service_provider_contacts ins Contacts-Modul (per Query-Builder, es gibt kein Model).
 */
class UpdateServiceProviderContacts extends Command
{
    protected $signature = 'artwork:update-service-provider-contacts';
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        if (!Schema::hasTable('service_provider_contacts')) {
            $this->warn('Tabelle service_provider_contacts existiert nicht. Migration wird übersprungen.');
            return;
        }

        $oldContactsByProvider = DB::table('service_provider_contacts')
            ->get()
            ->groupBy('service_provider_id');

        ServiceProvider::query()->get()->each(function (ServiceProvider $serviceProvider) use ($oldContactsByProvider): void {
            try {
                $contacts = $oldContactsByProvider->get($serviceProvider->id, collect());

                if ($contacts->isEmpty()) {
                    Log::info("Keine alten Kontakte gefunden für ServiceProvider ID {$serviceProvider->id}");
                    return;
                }

                foreach ($contacts as $contact) {
                    if (!$contact->first_name && !$contact->last_name && !$contact->email && !$contact->phone_number) {
                        Log::warning("Leerer Kontakt übersprungen bei ServiceProvider ID {$serviceProvider->id}");
                        continue;
                    }

                    // Optional: Vermeide Duplikate
                    $alreadyExists = $serviceProvider->contacts()
                        ->where('email', $contact->email)
                        ->exists();

                    if ($alreadyExists) {
                        Log::info(
                            "Kontakt mit Email {$contact->email} existiert bereits "
                            . "für ServiceProvider ID {$serviceProvider->id}"
                        );
                        continue;
                    }

                    $serviceProvider->contacts()->create([
                        'name' => trim($contact->first_name . ' ' . $contact->last_name),
                        'email' => $contact->email,
                        'phone' => $contact->phone_number,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Fehler bei ServiceProvider ID {$serviceProvider->id}: " . $e->getMessage());
            }
        });

        $this->info('Migration abgeschlossen. Versuche, alte Tabelle zu löschen...');

        try {
            Schema::dropIfExists('service_provider_contacts');
            $this->info('Alte Kontakt-Tabelle erfolgreich gelöscht.');
        } catch (\Throwable $e) {
            Log::error('Fehler beim Löschen der alten Tabelle: ' . $e->getMessage());
            $this->error('Löschen der alten Kontakt-Tabelle fehlgeschlagen.');
        }
    }
}
