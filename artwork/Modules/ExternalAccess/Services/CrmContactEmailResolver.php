<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Illuminate\Database\Eloquent\Builder;

/**
 * Die E-Mail eines CRM-Kontakts lebt je nach Kontaktart an unterschiedlichen Stellen: als Spalte
 * der Quell-Entität (Freelancer, Dienstleister, Künstler*in, …) oder — bei frei angelegten
 * Kontaktarten — als Wert der CRM-Eigenschaft "Email". Hier gibt es EINE Stelle zum Lesen und
 * zum Nachtragen (Einladung eines bestehenden Kontakts ohne hinterlegte Adresse).
 */
class CrmContactEmailResolver
{
    public const EMAIL_PROPERTY_NAME = 'Email';

    public function resolve(CrmContact $contact): ?string
    {
        $entity = $contact->getSourceEntity();
        if ($entity !== null && $this->entityHasEmailColumn($entity)) {
            $email = trim((string) ($entity->getAttribute('email') ?? ''));
            if ($email !== '') {
                return mb_strtolower($email);
            }
        }

        $value = $this->emailPropertyValue($contact)?->value;
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? mb_strtolower($value) : null;
    }

    /**
     * Hinterlegt die Adresse am Kontakt, falls dort noch keine steht (Quell-Entität bevorzugt,
     * sonst CRM-Eigenschaft "Email", sofern die Kontaktart sie führt).
     */
    public function storeIfMissing(CrmContact $contact, string $email): void
    {
        if ($this->resolve($contact) !== null) {
            return;
        }

        $entity = $contact->getSourceEntity();
        if ($entity !== null && $this->entityHasEmailColumn($entity)) {
            $entity->forceFill(['email' => $email])->save();
            $this->mirrorToEmailProperty($contact, $email);

            return;
        }

        $this->mirrorToEmailProperty($contact, $email);
    }

    private function mirrorToEmailProperty(CrmContact $contact, string $email): void
    {
        $property = CrmProperty::query()
            ->where('name', self::EMAIL_PROPERTY_NAME)
            ->whereHas(
                'contactTypes',
                fn (Builder $ct) => $ct->where('crm_contact_types.id', $contact->crm_contact_type_id),
            )
            ->first();

        if ($property === null) {
            return;
        }

        CrmPropertyValue::updateOrCreate(
            ['crm_contact_id' => $contact->id, 'crm_property_id' => $property->id],
            ['value' => $email],
        );
    }

    private function emailPropertyValue(CrmContact $contact): ?CrmPropertyValue
    {
        return CrmPropertyValue::query()
            ->where('crm_contact_id', $contact->id)
            ->whereHas('property', fn (Builder $p) => $p->where('name', self::EMAIL_PROPERTY_NAME))
            ->first();
    }

    private function entityHasEmailColumn(object $entity): bool
    {
        return method_exists($entity, 'getFillable') && in_array('email', $entity->getFillable(), true);
    }
}
