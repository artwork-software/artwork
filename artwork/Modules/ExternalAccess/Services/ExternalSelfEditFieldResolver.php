<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\Artist;
use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditField;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditSchema;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditSection;
use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the field schema an external person sees when editing their own CRM record.
 *
 * Every section is STAGED: all changes (source-entity columns AND non-confidential CRM
 * property values) go through one pending submission that the inviting person reviews.
 *
 * Source-entity field lists are hardcoded per type (deliberate security decision, NOT
 * auto-discovered via getCrmFields). Salary/work/shift fields are intentionally excluded.
 * Contacts without a source entity (freely created contact types) may edit their display name.
 */
class ExternalSelfEditFieldResolver
{
    /** Field key for the display name of a standalone CRM contact (no source entity). */
    public const CONTACT_DISPLAY_NAME_FIELD = 'display_name';

    /**
     * Editable source-entity columns per entity class; single source of truth shared with the
     * approval service (which re-validates against this list before applying a change).
     *
     * @var array<class-string, list<string>>
     */
    public const ALLOWED_ENTITY_FIELDS = [
        Freelancer::class => ['first_name', 'last_name', 'email', 'phone_number', 'street', 'zip_code', 'location'],
        ServiceProvider::class => ['provider_name', 'email', 'phone_number', 'street', 'zip_code', 'location'],
        Artist::class => ['name', 'first_name', 'last_name', 'email', 'phone_number', 'position'],
        Manufacturer::class => ['name', 'contact_person', 'email', 'phone', 'website', 'address'],
        Accommodation::class => ['name', 'email', 'phone_number', 'street', 'zip_code', 'location'],
    ];

    /**
     * @return list<string>
     */
    public static function allowedFieldsFor(string $targetClass): array
    {
        if ($targetClass === CrmContact::class) {
            return [self::CONTACT_DISPLAY_NAME_FIELD];
        }

        return self::ALLOWED_ENTITY_FIELDS[$targetClass] ?? [];
    }

    public function resolveFor(ExternalAccess $external): SelfEditSchema
    {
        $contact = $external->crmContact;
        $contactType = $contact->contactType;
        $entity = $contact->getSourceEntity();

        $sections = [];

        if ($entity !== null && array_key_exists($entity::class, self::ALLOWED_ENTITY_FIELDS)) {
            $sections[] = $this->buildSourceEntitySection($entity);
        } elseif ($entity === null) {
            $sections[] = $this->buildStandaloneContactSection($contact);
        }

        foreach ($this->buildCrmPropertySections($contact, (int) $contactType->id) as $section) {
            $sections[] = $section;
        }

        return new SelfEditSchema($sections);
    }

    private function buildSourceEntitySection(CrmEntity $entity): SelfEditSection
    {
        return match ($entity::class) {
            Freelancer::class => $this->buildFreelancerSection($entity),
            ServiceProvider::class => $this->buildServiceProviderSection($entity),
            Artist::class => $this->buildArtistSection($entity),
            Manufacturer::class => $this->buildManufacturerSection($entity),
            Accommodation::class => $this->buildAccommodationSection($entity),
        };
    }

    private function buildFreelancerSection(Freelancer $freelancer): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Personal data'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $freelancer->getMorphClass(),
            targetId: $freelancer->id,
            fields: [
                new SelfEditField('first_name', __('First name'), 'text', true, $freelancer->first_name),
                new SelfEditField('last_name', __('Last name'), 'text', true, $freelancer->last_name),
                new SelfEditField('email', __('Email'), 'email', true, $freelancer->email),
                new SelfEditField('phone_number', __('Phone'), 'tel', false, $freelancer->phone_number),
                new SelfEditField('street', __('Street'), 'text', false, $freelancer->street),
                new SelfEditField('zip_code', __('ZIP'), 'text', false, $freelancer->zip_code),
                new SelfEditField('location', __('City'), 'text', false, $freelancer->location),
            ],
        );
    }

    private function buildServiceProviderSection(ServiceProvider $sp): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Company data'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $sp->getMorphClass(),
            targetId: $sp->id,
            fields: [
                new SelfEditField('provider_name', __('Provider name'), 'text', true, $sp->provider_name),
                new SelfEditField('email', __('Email'), 'email', true, $sp->email),
                new SelfEditField('phone_number', __('Phone'), 'tel', false, $sp->phone_number),
                new SelfEditField('street', __('Street'), 'text', false, $sp->street),
                new SelfEditField('zip_code', __('ZIP'), 'text', false, $sp->zip_code),
                new SelfEditField('location', __('City'), 'text', false, $sp->location),
            ],
        );
    }

    private function buildArtistSection(Artist $artist): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Artist data'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $artist->getMorphClass(),
            targetId: $artist->id,
            fields: [
                new SelfEditField('name', __('Artist name'), 'text', true, $artist->name),
                new SelfEditField('first_name', __('First name'), 'text', false, $artist->first_name),
                new SelfEditField('last_name', __('Last name'), 'text', false, $artist->last_name),
                new SelfEditField('email', __('Email'), 'email', true, $artist->email),
                new SelfEditField('phone_number', __('Phone'), 'tel', false, $artist->phone_number),
                new SelfEditField('position', __('Position'), 'text', false, $artist->position),
            ],
        );
    }

    private function buildManufacturerSection(Manufacturer $manufacturer): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Company data'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $manufacturer->getMorphClass(),
            targetId: $manufacturer->id,
            fields: [
                new SelfEditField('name', __('Name'), 'text', true, $manufacturer->name),
                new SelfEditField('contact_person', __('Contact person'), 'text', false, $manufacturer->contact_person),
                new SelfEditField('email', __('Email'), 'email', true, $manufacturer->email),
                new SelfEditField('phone', __('Phone'), 'tel', false, $manufacturer->phone),
                new SelfEditField('website', __('Website'), 'url', false, $manufacturer->website),
                new SelfEditField('address', __('Address'), 'text', false, $manufacturer->address),
            ],
        );
    }

    private function buildAccommodationSection(Accommodation $accommodation): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Company data'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $accommodation->getMorphClass(),
            targetId: $accommodation->id,
            fields: [
                new SelfEditField('name', __('Name'), 'text', true, $accommodation->name),
                new SelfEditField('email', __('Email'), 'email', true, $accommodation->email),
                new SelfEditField('phone_number', __('Phone'), 'tel', false, $accommodation->phone_number),
                new SelfEditField('street', __('Street'), 'text', false, $accommodation->street),
                new SelfEditField('zip_code', __('ZIP'), 'text', false, $accommodation->zip_code),
                new SelfEditField('location', __('City'), 'text', false, $accommodation->location),
            ],
        );
    }

    /**
     * Freely created contact types have no source entity; the external person may propose a new
     * display name. Contact details live in the CRM property sections below.
     */
    private function buildStandaloneContactSection(CrmContact $contact): SelfEditSection
    {
        return new SelfEditSection(
            key: 'personal',
            label: __('Contact'),
            mode: SelfEditSectionMode::STAGED,
            targetType: $contact->getMorphClass(),
            targetId: $contact->id,
            fields: [
                new SelfEditField(self::CONTACT_DISPLAY_NAME_FIELD, __('Name'), 'text', true, $contact->display_name),
            ],
        );
    }

    /**
     * @return list<SelfEditSection>
     */
    private function buildCrmPropertySections(CrmContact $contact, int $contactTypeId): array
    {
        $properties = CrmProperty::query()
            ->whereHas('group', fn (Builder $g) => $g->where('is_confidential', false))
            ->whereHas('contactTypes', fn (Builder $ct) => $ct->where('crm_contact_types.id', $contactTypeId))
            ->with([
                'group',
                'contactTypes' => fn ($ct) => $ct->where('crm_contact_types.id', $contactTypeId),
                'values' => fn ($v) => $v->where('crm_contact_id', $contact->id),
            ])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (CrmProperty $property) => $property->type !== null
                && self::inputTypeFor($property->type) !== null);

        $byGroup = $properties->groupBy(fn (CrmProperty $p) => $p->group->id);

        $sections = [];
        foreach ($byGroup as $groupProperties) {
            $group = $groupProperties->first()->group;

            $fields = $groupProperties->map(fn (CrmProperty $property) => new SelfEditField(
                key: 'crm_property:' . $property->id,
                label: $property->name,
                inputType: self::inputTypeFor($property->type),
                required: (bool) ($property->contactTypes->first()?->pivot->is_required ?? false),
                value: $property->values->first()?->value,
                options: $property->type === CrmPropertyTypeEnum::SELECT ? self::selectOptionsOf($property) : [],
            ))->values()->all();

            $sections[] = new SelfEditSection(
                key: 'crm_group_' . $group->id,
                label: $group->name,
                mode: SelfEditSectionMode::STAGED,
                targetType: $contact->getMorphClass(),
                targetId: $contact->id,
                fields: $fields,
            );
        }

        // Stable ordering by the group's own sort order.
        usort($sections, fn (SelfEditSection $a, SelfEditSection $b) => $a->key <=> $b->key);

        return $sections;
    }

    /**
     * Eingabetyp der externen Maske je CRM-Eigenschaftstyp; muss dem internen CrmPropertyValueInput
     * entsprechen. Bewusst ohne default-Zweig: ein neuer Enum-Fall fällt hier sofort auf, statt still
     * als Freitextfeld zu erscheinen. null = extern nicht bearbeitbar (Uploads brauchen einen eigenen
     * Datei-Endpunkt; die CRM-Kontaktliste im Tab blendet sie ebenso aus).
     */
    public static function inputTypeFor(CrmPropertyTypeEnum $type): ?string
    {
        return match ($type) {
            CrmPropertyTypeEnum::TEXT => 'text',
            CrmPropertyTypeEnum::TEXTAREA => 'textarea',
            CrmPropertyTypeEnum::CHECKBOX => 'checkbox',
            CrmPropertyTypeEnum::DATE => 'date',
            CrmPropertyTypeEnum::NUMBER => 'number',
            CrmPropertyTypeEnum::LINK => 'url',
            CrmPropertyTypeEnum::SELECT => 'select',
            CrmPropertyTypeEnum::UPLOAD => null,
        };
    }

    /**
     * Auswahlwerte wie im internen Dropdown (leere Einträge aus den Einstellungen fallen weg).
     *
     * @return list<string>
     */
    public static function selectOptionsOf(CrmProperty $property): array
    {
        return array_values(array_filter(
            array_map(static fn ($option): string => (string) $option, $property->select_values ?? []),
            static fn (string $option): bool => $option !== '',
        ));
    }
}
