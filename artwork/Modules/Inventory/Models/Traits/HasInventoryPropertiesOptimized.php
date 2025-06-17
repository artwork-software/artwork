<?php

namespace Artwork\Modules\Inventory\Models\Traits;

use Artwork\Modules\Inventory\Models\InventoryArticleProperties;
use Artwork\Modules\Inventory\Models\InventoryPropertyValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasInventoryPropertiesOptimized
{
    use HasInventoryProperties;

    /**
     * Scope für optimiertes Laden von Properties mit spezifischem Typ
     */
    public function scopeWithPropertyType($query, string $type)
    {
        return $query->with(['properties' => function ($query) use ($type) {
            $query->where('type', $type);
        }]);
    }

    /**
     * Optimierte Methode zum Abrufen eines Property-Werts nach Typ
     */
    public function getPropertyValueByType(string $type)
    {
        if (!$this->relationLoaded('properties')) {
            $this->load('properties');
        }

        $property = $this->properties->firstWhere('type', $type);
        
        return $property ? $property->pivot->value : null;
    }

    /**
     * Batch-Update für Properties
     */
    public function updateProperties(array $properties): void
    {
        $syncData = [];
        foreach ($properties as $propertyId => $value) {
            $syncData[$propertyId] = ['value' => $value];
        }
        
        $this->properties()->syncWithoutDetaching($syncData);
    }
}
