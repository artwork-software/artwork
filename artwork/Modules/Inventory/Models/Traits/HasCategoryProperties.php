<?php

namespace Artwork\Modules\Inventory\Models\Traits;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCategoryProperties
{
    /**
     * Definiert eine morphToMany Beziehung zu InventoryArticleProperties.
     */
    public function properties(): MorphToMany
    {
        return $this->morphToMany(
            InventoryArticleProperties::class,
            'inventory_category_propertyable', // Morph-Name für Kategorien/Subkategorien
            'inventory_category_property_values', // Eigene Pivot-Tabelle für Kategorien/Subkategorien
            'inventory_category_propertyable_id', // Das Fremdschlüsselfeld für die Kategorie oder SubKategorie
            'inventory_article_property_id' // Das Fremdschlüsselfeld für die Property
        )->withPivot('value'); // Das "value"-Feld wird als zusätzliches Pivot-Attribut geladen
    }
}