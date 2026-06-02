<?php

namespace Artwork\Modules\Inventory\Models\Traits;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasInventoryProperties
{
    /**
     * Gibt alle Property-Werte für das Model zurück (z. B. für Kategorien, Subkategorien, Artikel).
     */
    public function propertyValues(): MorphMany
    {
        return $this->morphMany(InventoryPropertyValue::class, 'inventory_propertyable');
    }

    /**
     * Gibt alle Eigenschaften mit Werten zurück.
     */
    public function properties(): MorphToMany
    {
        return $this->morphToMany(
            InventoryArticleProperties::class,
            'inventory_propertyable',
            'inventory_property_values',
            'inventory_propertyable_id',
            'inventory_article_property_id'
        )->withPivot('value')
            // Ref 1.41: globale Eigenschafts-Reihenfolge systemweit anwenden.
            ->orderBy('inventory_article_properties.order')
            ->orderBy('inventory_article_properties.id');
    }

}
