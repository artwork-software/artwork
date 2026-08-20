<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Database\Seeders\Demo\Support\DemoDataPools;
use Database\Seeders\Demo\Support\DemoExtrasPools;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CRM-Befüllung: spiegelt die Demo-Entitäten (Künstler*innen, Unterkünfte,
 * Freelancer, Dienstleister, User) über den System-Mechanismus ins CRM und
 * legt kuratierte reine CRM-Kontakte an (Veranstalter, Sponsoren) — inkl.
 * Basiseigenschaften, damit Liste und Detailansicht gefüllt sind.
 */
class DemoCrmSeeder extends Seeder
{
    public function run(): void
    {
        if (!CrmContactType::query()->exists()) {
            $this->command?->warn('CRM: keine Kontakttypen vorhanden (artwork:update/migrate-to-crm fehlt) – übersprungen.');

            return;
        }

        $mirrored = $this->mirrorEntities();
        $curated = $this->seedCuratedContacts();

        $this->command?->info(sprintf('CRM: %d Entitäten gespiegelt, %d kuratierte Kontakte angelegt.', $mirrored, $curated));
    }

    /** Spiegelt alle Demo-Entitäten ohne CRM-Kontakt über den Trait-Mechanismus. */
    private function mirrorEntities(): int
    {
        $entities = collect()
            ->merge(Artist::query()->whereNull('crm_contact_id')->get())
            ->merge(Accommodation::query()->whereNull('crm_contact_id')->get())
            ->merge(Freelancer::query()->whereNull('crm_contact_id')->get())
            ->merge(ServiceProvider::query()->whereNull('crm_contact_id')->get())
            ->merge(
                User::query()
                    ->whereNull('crm_contact_id')
                    ->where('email', 'like', '%@' . DemoDataPools::EMAIL_DOMAIN)
                    ->get()
            );

        $mirrored = 0;
        foreach ($entities as $entity) {
            if (!method_exists($entity, 'createCrmContact')) {
                continue;
            }
            $entity->createCrmContact();
            if ($entity->crm_contact_id !== null) {
                if (method_exists($entity, 'syncToCrm')) {
                    $entity->syncToCrm();
                }
                $mirrored++;
            }
        }

        return $mirrored;
    }

    private function seedCuratedContacts(): int
    {
        $created = 0;
        $sortOrder = (int) CrmContactType::query()->max('sort_order');

        foreach (DemoExtrasPools::CRM_TYPES as $slug => $typeData) {
            $type = CrmContactType::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $typeData['name'],
                    'icon' => $typeData['icon'],
                    'color' => $typeData['color'],
                    'is_system' => false,
                    'is_active' => true,
                    'sort_order' => ++$sortOrder,
                ]
            );

            foreach (DemoExtrasPools::CRM_CONTACTS[$slug] ?? [] as $contactData) {
                $displayName = $contactData['display_name'];
                if (
                    CrmContact::query()
                        ->where('crm_contact_type_id', $type->id)
                        ->where('display_name', $displayName)
                        ->exists()
                ) {
                    continue;
                }

                $contact = CrmContact::create([
                    'crm_contact_type_id' => $type->id,
                    'display_name' => $displayName,
                    'is_active' => true,
                ]);
                $created++;

                unset($contactData['display_name']);
                $this->fillProperties($contact, $contactData);
            }
        }

        return $created;
    }

    /** @param array<string, string> $values Property-Name (Systemeigenschaft) => Wert */
    private function fillProperties(CrmContact $contact, array $values): void
    {
        foreach ($values as $propertyName => $value) {
            $property = CrmProperty::query()->where('name', $propertyName)->orderBy('id')->first();
            if ($property === null) {
                continue;
            }
            DB::table('crm_property_values')->updateOrInsert(
                ['crm_contact_id' => $contact->id, 'crm_property_id' => $property->id],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
