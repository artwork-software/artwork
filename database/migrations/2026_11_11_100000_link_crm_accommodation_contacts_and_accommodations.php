<?php

use Artwork\Modules\Accommodation\Models\Accommodation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Künstler*innenaufenthalte und Zimmertypen hängen am Unterkunfts-Datensatz (accommodations), gepflegt
 * werden Unterkünfte im CRM. Bis 1.10.1 bekam ein im CRM angelegter oder importierter Unterkunfts-Kontakt
 * keinen solchen Datensatz und eine auf /accommodation angelegte Unterkunft keinen CRM-Kontakt. Die Migration
 * zieht beide Richtungen einmalig nach, so wie artwork:migrate-to-crm den Altbestand verknüpft hat.
 *
 * Gleichnamige, noch unverknüpfte Paare werden verbunden statt verdoppelt. Kontakte im Papierkorb bleiben
 * unberührt. Idempotent: verknüpfte Datensätze werden übersprungen.
 */
return new class extends Migration
{
    private const ACCOMMODATION_TYPE_SLUG = 'accommodation';

    /**
     * CRM-Eigenschaft (Name) zur Spalte der Unterkunft, wie Accommodation::getCrmFields().
     */
    private const PROPERTY_COLUMNS = [
        'Email' => 'email',
        'Telefon' => 'phone_number',
        'Straße, Hausnummer' => 'street',
        'PLZ' => 'zip_code',
        'Stadt' => 'location',
        'Notiz' => 'note',
    ];

    public function up(): void
    {
        $typeId = DB::table('crm_contact_types')->where('slug', self::ACCOMMODATION_TYPE_SLUG)->value('id');
        if ($typeId === null) {
            return;
        }

        $morphClass = (new Accommodation())->getMorphClass();

        $this->linkContactsWithoutAccommodation((int) $typeId, $morphClass);
        $this->linkAccommodationsWithoutContact((int) $typeId, $morphClass);
    }

    public function down(): void
    {
        // Die nachgezogenen Datensätze sind von regulär angelegten nicht zu unterscheiden.
    }

    private function linkContactsWithoutAccommodation(int $typeId, string $morphClass): void
    {
        $contacts = DB::table('crm_contacts')
            ->where('crm_contact_type_id', $typeId)
            ->whereNull('deleted_at')
            ->whereNull('entity_id')
            ->orderBy('id')
            ->get(['id', 'display_name', 'profile_image']);

        foreach ($contacts as $contact) {
            $accommodationId = $this->unlinkedAccommodationIdByName($contact->display_name, $morphClass)
                ?? DB::table('accommodations')->insertGetId(array_merge(
                    $this->accommodationColumnsFromProperties((int) $contact->id),
                    [
                        'name' => $contact->display_name,
                        'profile_image' => $contact->profile_image,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ));

            $this->link((int) $contact->id, (int) $accommodationId, $morphClass);
        }
    }

    private function linkAccommodationsWithoutContact(int $typeId, string $morphClass): void
    {
        $accommodations = DB::table('accommodations')
            ->whereNull('crm_contact_id')
            ->orderBy('id')
            ->get();

        foreach ($accommodations as $accommodation) {
            $existingContact = DB::table('crm_contacts')
                ->where('entity_type', $morphClass)
                ->where('entity_id', $accommodation->id)
                ->orderBy('id')
                ->first(['id', 'deleted_at']);

            if ($existingContact !== null) {
                if ($existingContact->deleted_at === null) {
                    $this->link((int) $existingContact->id, (int) $accommodation->id, $morphClass);
                }
                continue;
            }

            $contactId = DB::table('crm_contacts')->insertGetId([
                'crm_contact_type_id' => $typeId,
                'display_name' => $accommodation->name,
                'profile_image' => $accommodation->profile_image,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->writePropertiesFromAccommodation((int) $contactId, $accommodation);
            $this->link((int) $contactId, (int) $accommodation->id, $morphClass);
        }
    }

    private function unlinkedAccommodationIdByName(string $name, string $morphClass): ?int
    {
        $id = DB::table('accommodations')
            ->where('name', $name)
            ->whereNull('crm_contact_id')
            ->whereNotExists(function ($query) use ($morphClass): void {
                $query->select(DB::raw(1))
                    ->from('crm_contacts')
                    ->where('crm_contacts.entity_type', $morphClass)
                    ->whereColumn('crm_contacts.entity_id', 'accommodations.id');
            })
            ->orderBy('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function link(int $contactId, int $accommodationId, string $morphClass): void
    {
        DB::table('crm_contacts')->where('id', $contactId)->update([
            'entity_type' => $morphClass,
            'entity_id' => $accommodationId,
        ]);
        DB::table('accommodations')->where('id', $accommodationId)->update(['crm_contact_id' => $contactId]);

        DB::table('accommodation_accommodation_room_type')
            ->where('accommodation_id', $accommodationId)
            ->whereNull('crm_contact_id')
            ->update(['crm_contact_id' => $contactId]);

        DB::table('artist_residencies')
            ->where('accommodation_id', $accommodationId)
            ->whereNull('accommodation_crm_contact_id')
            ->update(['accommodation_crm_contact_id' => $contactId]);
    }

    /**
     * @return array<string, string|null>
     */
    private function accommodationColumnsFromProperties(int $contactId): array
    {
        $values = DB::table('crm_property_values')
            ->join('crm_properties', 'crm_properties.id', '=', 'crm_property_values.crm_property_id')
            ->where('crm_property_values.crm_contact_id', $contactId)
            ->whereIn('crm_properties.name', array_keys(self::PROPERTY_COLUMNS))
            ->pluck('crm_property_values.value', 'crm_properties.name');

        $columns = [];
        foreach (self::PROPERTY_COLUMNS as $propertyName => $column) {
            $value = $values[$propertyName] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $columns[$column] = $column === 'note' ? $value : mb_substr($value, 0, 255);
        }

        return $columns;
    }

    private function writePropertiesFromAccommodation(int $contactId, object $accommodation): void
    {
        $propertyIds = DB::table('crm_properties')
            ->whereIn('name', array_keys(self::PROPERTY_COLUMNS))
            ->orderBy('id')
            ->get(['id', 'name'])
            ->unique('name')
            ->pluck('id', 'name');

        foreach (self::PROPERTY_COLUMNS as $propertyName => $column) {
            $value = $accommodation->{$column} ?? null;
            if (!isset($propertyIds[$propertyName]) || $value === null || $value === '') {
                continue;
            }

            DB::table('crm_property_values')->insert([
                'crm_contact_id' => $contactId,
                'crm_property_id' => $propertyIds[$propertyName],
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
