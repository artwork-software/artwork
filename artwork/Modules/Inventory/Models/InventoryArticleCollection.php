<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * Artikel-Collection, die vor dem Serialisieren die Raum-/Hersteller-Lookups der
 * $appends (`room`, `manufacturer`) für alle Artikel auf einmal lädt — die Accessoren
 * fragten sonst je unterschiedlichem Hersteller/Raum einzeln nach (Inventarübersicht:
 * 55 crm_contacts-Queries). Greift überall, wo Artikel als Collection oder Relation
 * serialisiert werden (Inertia-Props, Resources, JSON-Antworten) — inklusive der
 * geladenen Detail-Artikel (detailedArticleQuantities) mit denselben $appends.
 *
 * @extends Collection<int, InventoryArticle>
 */
class InventoryArticleCollection extends Collection
{
    public function toArray(): array
    {
        InventoryArticle::preloadPropertyLookupsDeep($this);

        return parent::toArray();
    }

    public function jsonSerialize(): array
    {
        InventoryArticle::preloadPropertyLookupsDeep($this);

        return parent::jsonSerialize();
    }
}
