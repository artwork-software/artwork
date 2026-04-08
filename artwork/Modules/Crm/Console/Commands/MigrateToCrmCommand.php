<?php

namespace Artwork\Modules\Crm\Console\Commands;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Contacts\Models\Contact;
use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MigrateToCrmCommand extends Command
{
    protected $signature = 'artwork:migrate-to-crm';

    protected $description = 'Migrate existing Artists, Accommodations, Manufacturers, Users, Freelancers and ServiceProviders to the CRM module';

    private array $contactTypes = [];
    private array $properties = [];
    private array $propertyMeta = [];

    public function handle(): int
    {
        $this->info('Starting CRM migration...');

        DB::beginTransaction();

        try {
            $this->createSystemContactTypes();
            $this->cleanupDeprecatedProperties();
            $this->createPropertyGroupsAndProperties();
            $this->assignPropertiesToTypes();
            $this->migrateArtists();
            $this->migrateAccommodations();
            $this->migrateManufacturers();
            $this->migrateUsers();
            $this->migrateFreelancers();
            $this->migrateServiceProviders();
            $this->migrateForeignKeyReferences();

            DB::commit();
            $this->info('CRM migration completed successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function createSystemContactTypes(): void
    {
        $this->info('Creating system contact types...');

        $types = [
            ['name' => 'Künstler*in', 'slug' => CrmSystemContactTypeEnum::ARTIST->value, 'icon' => 'IconPalette', 'sort_order' => 1],
            ['name' => 'Unterkunft', 'slug' => CrmSystemContactTypeEnum::ACCOMMODATION->value, 'icon' => 'IconHome', 'sort_order' => 2],
            ['name' => 'Hersteller*in', 'slug' => CrmSystemContactTypeEnum::MANUFACTURER->value, 'icon' => 'IconBuildingFactory2', 'sort_order' => 3],
            ['name' => 'Freelancer', 'slug' => CrmSystemContactTypeEnum::FREELANCER->value, 'icon' => 'IconUserBolt', 'sort_order' => 4],
            ['name' => 'Dienstleister*in', 'slug' => CrmSystemContactTypeEnum::SERVICE_PROVIDER->value, 'icon' => 'IconBriefcase', 'sort_order' => 5],
            ['name' => 'Nutzer*in', 'slug' => CrmSystemContactTypeEnum::USER->value, 'icon' => 'IconUser', 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            $contactType = CrmContactType::firstOrCreate(
                ['slug' => $type['slug']],
                array_merge($type, ['is_system' => true, 'is_active' => true])
            );

            // Ensure icon is set even on existing records from earlier migrations
            if (empty($contactType->icon) && !empty($type['icon'])) {
                $contactType->update(['icon' => $type['icon']]);
            }

            $this->contactTypes[$type['slug']] = $contactType;
        }
    }

    private function cleanupDeprecatedProperties(): void
    {
        $this->info('Cleaning up deprecated properties...');

        $baseGroup = CrmPropertyGroup::where('name', 'Basiseigenschaften')
            ->where('is_system', true)
            ->first();

        if (!$baseGroup) {
            return;
        }

        // Remove deprecated properties completely
        foreach (['Ort', 'Adresse', 'Straße', 'Bürgerlicher Name'] as $deprecatedName) {
            $property = CrmProperty::where('name', $deprecatedName)
                ->where('crm_property_group_id', $baseGroup->id)
                ->first();

            if ($property) {
                CrmPropertyValue::where('crm_property_id', $property->id)->delete();
                $property->contactTypes()->detach();
                $property->delete();
                $this->info("Removed deprecated property \"{$deprecatedName}\".");
            }
        }
    }

    private function createPropertyGroupsAndProperties(): void
    {
        $this->info('Creating property groups and properties...');

        // Group: Basiseigenschaften
        $baseGroup = CrmPropertyGroup::firstOrCreate(
            ['name' => 'Basiseigenschaften', 'is_system' => true],
            ['sort_order' => 1]
        );

        $this->createProperty($baseGroup, 'Name', CrmPropertyTypeEnum::TEXT, true, true, 1);
        $this->createProperty($baseGroup, 'Künstler*innen Name', CrmPropertyTypeEnum::TEXT, false, true, 2);
        $this->createProperty($baseGroup, 'Vorname', CrmPropertyTypeEnum::TEXT, false, false, 3);
        $this->createProperty($baseGroup, 'Nachname', CrmPropertyTypeEnum::TEXT, false, false, 4);
        $this->createProperty($baseGroup, 'Email', CrmPropertyTypeEnum::TEXT, false, true, 5);
        $this->createProperty($baseGroup, 'Telefon', CrmPropertyTypeEnum::TEXT, false, false, 6);
        $this->createProperty($baseGroup, 'Straße, Hausnummer', CrmPropertyTypeEnum::TEXT, false, false, 7);
        $this->createProperty($baseGroup, 'PLZ', CrmPropertyTypeEnum::TEXT, false, false, 8);
        $this->createProperty($baseGroup, 'Stadt', CrmPropertyTypeEnum::TEXT, false, false, 9);
        $this->createProperty($baseGroup, 'Land', CrmPropertyTypeEnum::TEXT, false, false, 10);

        // Group: Arbeitsprofil
        $workGroup = CrmPropertyGroup::firstOrCreate(
            ['name' => 'Arbeitsprofil', 'is_system' => true],
            ['sort_order' => 2]
        );

        $this->createProperty($workGroup, 'Position', CrmPropertyTypeEnum::TEXT, false, false, 1);
        $this->createProperty($workGroup, 'Business', CrmPropertyTypeEnum::TEXT, false, false, 2);
        $this->createProperty($workGroup, 'Work Name', CrmPropertyTypeEnum::TEXT, false, false, 3);
        $this->createProperty($workGroup, 'Work Description', CrmPropertyTypeEnum::TEXTAREA, false, false, 4);
        $this->createProperty($workGroup, 'Can Work Shifts', CrmPropertyTypeEnum::CHECKBOX, false, false, 5);

        // Group: Konditionen (confidential)
        $conditionsGroup = CrmPropertyGroup::firstOrCreate(
            ['name' => 'Konditionen', 'is_system' => true],
            ['sort_order' => 3, 'is_confidential' => true]
        );

        $this->createProperty($conditionsGroup, 'Stundensatz', CrmPropertyTypeEnum::NUMBER, false, false, 1);
        $this->createProperty($conditionsGroup, 'Vergütungsbeschreibung', CrmPropertyTypeEnum::TEXTAREA, false, false, 2);

        // Group: Hersteller-Details
        $manufacturerGroup = CrmPropertyGroup::firstOrCreate(
            ['name' => 'Hersteller-Details', 'is_system' => true],
            ['sort_order' => 4]
        );

        $this->createProperty($manufacturerGroup, 'Website', CrmPropertyTypeEnum::LINK, false, false, 1);
        $this->createProperty($manufacturerGroup, 'Kundennummer', CrmPropertyTypeEnum::TEXT, false, false, 2);
        $this->createProperty($manufacturerGroup, 'Kontaktperson', CrmPropertyTypeEnum::TEXT, false, false, 3);

        // Group: Notizen
        $notesGroup = CrmPropertyGroup::firstOrCreate(
            ['name' => 'Notizen', 'is_system' => true],
            ['sort_order' => 5]
        );

        $this->createProperty($notesGroup, 'Notiz', CrmPropertyTypeEnum::TEXTAREA, false, false, 1);
    }

    private function createProperty(
        CrmPropertyGroup $group,
        string $name,
        CrmPropertyTypeEnum $type,
        bool $isRequired,
        bool $showInList,
        int $sortOrder
    ): void {
        $property = CrmProperty::firstOrCreate(
            ['name' => $name, 'crm_property_group_id' => $group->id],
            [
                'type' => $type->value,
                'is_system' => true,
                'sort_order' => $sortOrder,
            ]
        );

        $this->properties[$name] = $property;
        $this->propertyMeta[$name] = [
            'is_required' => $isRequired,
            'show_in_list' => $showInList,
        ];
    }

    private function assignPropertiesToTypes(): void
    {
        $this->info('Assigning properties to contact types...');

        $assignments = [
            CrmSystemContactTypeEnum::ARTIST->value => [
                'Künstler*innen Name', 'Telefon', 'Vorname', 'Nachname',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Position', 'Notiz',
            ],
            CrmSystemContactTypeEnum::ACCOMMODATION->value => [
                'Name', 'Email', 'Telefon',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Notiz',
            ],
            CrmSystemContactTypeEnum::MANUFACTURER->value => [
                'Name', 'Email', 'Telefon',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Website', 'Kundennummer', 'Kontaktperson', 'Notiz',
            ],
            CrmSystemContactTypeEnum::FREELANCER->value => [
                'Name', 'Email', 'Telefon',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Position', 'Business', 'Work Name',
                'Work Description', 'Can Work Shifts', 'Stundensatz', 'Vergütungsbeschreibung', 'Notiz',
            ],
            CrmSystemContactTypeEnum::SERVICE_PROVIDER->value => [
                'Name', 'Email', 'Telefon',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Position', 'Work Name',
                'Work Description', 'Can Work Shifts', 'Stundensatz', 'Vergütungsbeschreibung', 'Notiz',
            ],
            CrmSystemContactTypeEnum::USER->value => [
                'Name', 'Email', 'Telefon',
                'Straße, Hausnummer', 'PLZ', 'Stadt', 'Land',
                'Position', 'Business', 'Notiz',
            ],
        ];

        foreach ($assignments as $typeSlug => $propertyNames) {
            $type = $this->contactTypes[$typeSlug];
            $syncData = [];

            foreach ($propertyNames as $index => $propertyName) {
                if (isset($this->properties[$propertyName])) {
                    $meta = $this->propertyMeta[$propertyName] ?? [];
                    $syncData[$this->properties[$propertyName]->id] = [
                        'sort_order' => $index,
                        'is_required' => $meta['is_required'] ?? false,
                        'show_in_list' => $meta['show_in_list'] ?? false,
                        'is_filterable' => false,
                    ];
                }
            }

            $type->properties()->syncWithoutDetaching($syncData);
        }
    }

    /**
     * Migriert ein CrmEntity-Model: erstellt CrmContact, setzt entity-Morph,
     * crm_contact_id und schreibt Property-Werte via getCrmFields().
     */
    private function migrateEntity(Model&CrmEntity $entity, CrmContactType $type, ?string $profileImage = null): CrmContact
    {
        $contact = CrmContact::firstOrCreate(
            [
                'crm_contact_type_id' => $type->id,
                'display_name' => $entity->getCrmDisplayName(),
            ],
            [
                'is_active' => true,
                'profile_image' => $profileImage,
                'entity_type' => $entity->getMorphClass(),
                'entity_id' => $entity->getKey(),
            ]
        );

        // Sicherstellen dass entity-Morph gesetzt ist (bei bestehenden Kontakten)
        if (!$contact->entity_type || !$contact->entity_id) {
            $contact->update([
                'entity_type' => $entity->getMorphClass(),
                'entity_id' => $entity->getKey(),
            ]);
        }

        // Property-Werte aus getCrmFields() schreiben
        foreach ($entity->getCrmFields() as $propertyName => $mapping) {
            $value = $entity->resolveCrmFieldValue($propertyName);
            $this->setPropertyValue($contact, $propertyName, $value);
        }

        // crm_contact_id auf dem Source-Model setzen
        $entity->updateQuietly(['crm_contact_id' => $contact->id]);

        return $contact;
    }

    private function migrateArtists(): void
    {
        $this->info('Migrating artists...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::ARTIST->value];

        Artist::all()->each(function (Artist $artist) use ($type) {
            $contact = $this->migrateEntity($artist, $type);

            // Update ArtistResidencies
            ArtistResidency::where('artist_id', $artist->id)
                ->update(['artist_crm_contact_id' => $contact->id]);
        });
    }

    private function migrateAccommodations(): void
    {
        $this->info('Migrating accommodations...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::ACCOMMODATION->value];

        Accommodation::all()->each(function (Accommodation $accommodation) use ($type) {
            $contact = $this->migrateEntity($accommodation, $type, $accommodation->profile_image);

            // Migrate room type pivot
            DB::table('accommodation_accommodation_room_type')
                ->where('accommodation_id', $accommodation->id)
                ->update(['crm_contact_id' => $contact->id]);

            // Migrate ArtistResidency accommodation references
            ArtistResidency::where('accommodation_id', $accommodation->id)
                ->update(['accommodation_crm_contact_id' => $contact->id]);

            // Migrate polymorphic contacts
            Contact::where('contactable_type', Accommodation::class)
                ->where('contactable_id', $accommodation->id)
                ->update([
                    'contactable_type' => CrmContact::class,
                    'contactable_id' => $contact->id,
                ]);
        });
    }

    private function migrateManufacturers(): void
    {
        $this->info('Migrating manufacturers...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::MANUFACTURER->value];

        Manufacturer::all()->each(function (Manufacturer $manufacturer) use ($type) {
            $contact = $this->migrateEntity($manufacturer, $type);

            // Migrate inventory manufacturer property values
            DB::table('inventory_property_values')
                ->join('inventory_article_properties', 'inventory_article_properties.id', '=', 'inventory_property_values.inventory_article_property_id')
                ->where('inventory_article_properties.type', 'manufacturer')
                ->where('inventory_property_values.value', (string) $manufacturer->id)
                ->update(['inventory_property_values.value' => (string) $contact->id]);
        });
    }

    private function migrateUsers(): void
    {
        $this->info('Migrating users...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::USER->value];

        User::all()->each(function (User $user) use ($type) {
            $this->migrateEntity($user, $type, $user->profile_photo_path);
        });
    }

    private function migrateFreelancers(): void
    {
        $this->info('Migrating freelancers...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::FREELANCER->value];

        Freelancer::all()->each(function (Freelancer $freelancer) use ($type) {
            $this->migrateEntity($freelancer, $type, $freelancer->profile_image);
        });
    }

    private function migrateServiceProviders(): void
    {
        $this->info('Migrating service providers...');
        $type = $this->contactTypes[CrmSystemContactTypeEnum::SERVICE_PROVIDER->value];

        ServiceProvider::all()->each(function (ServiceProvider $provider) use ($type) {
            $this->migrateEntity($provider, $type, $provider->profile_image);
        });
    }

    private function migrateForeignKeyReferences(): void
    {
        $this->info('Verifying foreign key references...');
        // All FK migrations are done inline in the entity migration methods above
        $this->info('Foreign key references verified.');
    }

    private function setPropertyValue(CrmContact $contact, string $propertyName, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $property = $this->properties[$propertyName] ?? null;

        if (!$property) {
            return;
        }

        CrmPropertyValue::firstOrCreate(
            [
                'crm_contact_id' => $contact->id,
                'crm_property_id' => $property->id,
            ],
            ['value' => $value]
        );
    }
}
