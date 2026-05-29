<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditField;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditSchema;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditSection;
use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the field schema an external person sees when editing their own CRM record.
 *
 * Two section modes:
 * - STAGED  : source-entity columns (Freelancer/ServiceProvider) — go through approval.
 * - DIRECT  : non-confidential CRM property values — written immediately.
 *
 * Source-entity field lists are hardcoded per type (deliberate security decision, NOT
 * auto-discovered via getCrmFields). Salary/work/shift fields are intentionally excluded.
 */
class ExternalSelfEditFieldResolver
{
    private const STAGED_SOURCE_ENTITY_TYPES = [
        Freelancer::class,
        ServiceProvider::class,
    ];

    public function resolveFor(ExternalAccess $external): SelfEditSchema
    {
        $contact = $external->crmContact;
        $contactType = $contact->contactType;
        $entity = $contact->getSourceEntity();

        $sections = [];

        if ($entity !== null && in_array($entity::class, self::STAGED_SOURCE_ENTITY_TYPES, true)) {
            $sections[] = $this->buildSourceEntitySection($entity);
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
            ->get();

        $byGroup = $properties->groupBy(fn (CrmProperty $p) => $p->group->id);

        $sections = [];
        foreach ($byGroup as $groupProperties) {
            $group = $groupProperties->first()->group;

            $fields = $groupProperties->map(fn (CrmProperty $property) => new SelfEditField(
                key: 'crm_property:' . $property->id,
                label: $property->name,
                inputType: $this->mapCrmPropertyType($property->type?->value),
                required: (bool) ($property->contactTypes->first()?->pivot->is_required ?? false),
                value: $property->values->first()?->value,
            ))->values()->all();

            $sections[] = new SelfEditSection(
                key: 'crm_group_' . $group->id,
                label: $group->name,
                mode: SelfEditSectionMode::DIRECT,
                targetType: $contact->getMorphClass(),
                targetId: $contact->id,
                fields: $fields,
            );
        }

        // Stable ordering by the group's own sort order.
        usort($sections, fn (SelfEditSection $a, SelfEditSection $b) => $a->key <=> $b->key);

        return $sections;
    }

    private function mapCrmPropertyType(?string $crmType): string
    {
        return match ($crmType) {
            'textarea' => 'textarea',
            'checkbox' => 'checkbox',
            'date' => 'date',
            'number' => 'number',
            'link' => 'url',
            default => 'text',
        };
    }
}
