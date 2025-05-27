<?php

namespace Artwork\Modules\MaterialSet\Repositories;

use Artwork\Modules\MaterialSet\Models\MaterialSet;

class MaterialSetRepository
{
    public function create(array $data): MaterialSet
    {
        $set = MaterialSet::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $set->items()->create([
                'inventory_article_id' => $item['id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $set->load('items.article');
    }

    public function update(MaterialSet $set, array $data): MaterialSet
    {
        $set->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $set->items()->delete();

        foreach ($data['items'] as $item) {
            $set->items()->create([
                'inventory_article_id' => $item['id'] ?? $item['inventory_article_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return $set->load('items.article');
    }

    public function delete(MaterialSet $set): void
    {
        $set->items()->delete();
        $set->delete();
    }
}
