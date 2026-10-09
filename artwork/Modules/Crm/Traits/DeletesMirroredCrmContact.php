<?php

namespace Artwork\Modules\Crm\Traits;

use Artwork\Modules\Crm\Models\CrmContact;

/**
 * Für Entitäten, deren CRM-Kontakt nur ein Spiegel ist (Nutzer*innen, Freelancer, Dienstleister).
 * Der Kontakt ist im CRM schreibgeschützt und dort nicht löschbar — wird die Entität gelöscht,
 * wird er deshalb samt Eigenschaftswerten (DB-Cascade) endgültig mitgelöscht. Ein Papierkorb-
 * Eintrag wäre sinnlos: ohne Quell-Entität ließe er sich weder wiederherstellen noch pflegen.
 */
trait DeletesMirroredCrmContact
{
    public static function bootDeletesMirroredCrmContact(): void
    {
        static::deleted(static function (self $entity): void {
            if (method_exists($entity, 'isForceDeleting') && !$entity->isForceDeleting()) {
                return;
            }

            $entity->deleteMirroredCrmContact();
        });
    }

    public function deleteMirroredCrmContact(): void
    {
        // Einzeln über Eloquent, damit CrmContact::deleting offene externe Zugänge widerruft
        CrmContact::withTrashed()
            ->where('entity_type', $this->getMorphClass())
            ->where('entity_id', $this->getKey())
            ->get()
            ->each(fn (CrmContact $contact) => $contact->forceDelete());
    }
}
