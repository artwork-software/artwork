<?php

namespace Artwork\Modules\MaterialSet\Repositories;

use Artwork\Modules\MaterialSet\Models\MaterialSet;
use Illuminate\Support\Facades\DB;

class MaterialSetRepository
{
    public function create(array $data): MaterialSet
    {
        $set = DB::transaction(function () use ($data): MaterialSet {
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

            return $set;
        });

        return $set->load('items.article');
    }

    public function update(MaterialSet $set, array $data): MaterialSet
    {
        // delete + recreate: ohne Transaktion verliert das Set seine Items, wenn ein create fehlschlägt
        DB::transaction(function () use ($set, $data): void {
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
        });

        return $set->load('items.article');
    }

    public function delete(MaterialSet $set): void
    {
        $set->items()->delete();
        $set->delete();
    }
}
