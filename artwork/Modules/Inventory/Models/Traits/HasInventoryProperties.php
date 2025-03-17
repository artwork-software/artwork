<?php

namespace Artwork\Modules\Inventory\Models\Traits;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
    public function properties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            InventoryArticleProperties::class,
            'inventory_property_values',
            'inventory_propertyable_id',
            'inventory_article_property_id'
        )->withPivot('value');
    }

    /**
     * Fügt eine Property hinzu oder aktualisiert sie.
     */
    public function setProperty($propertyId, $value): \Illuminate\Database\Eloquent\Model
    {
        return $this->propertyValues()->updateOrCreate(
            ['inventory_article_property_id' => $propertyId],
            ['value' => $value]
        );
    }

    /**
     * Holt den Wert einer bestimmten Property.
     */
    public function getProperty($propertyId)
    {
        return $this->propertyValues()->where('inventory_article_property_id', $propertyId)->first()?->value;
    }
}
