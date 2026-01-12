<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Inventory\Models\InventoryArticleFilterPreset;
use Illuminate\Http\Request;

class InventoryArticleFilterPresetController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:80'],
            'inventory_category_id' => ['nullable','integer'],
            'inventory_sub_category_id' => ['nullable','integer'],
            'filters' => ['nullable','array'],
            'tag_ids' => ['nullable','array'],
            'is_default' => ['nullable','boolean'],
        ]);

        $userId = auth()->id();

        if (($data['is_default'] ?? false) === true) {
            InventoryArticleFilterPreset::query()
                ->where('user_id', $userId)
                ->where('inventory_category_id', $data['inventory_category_id'] ?? null)
                ->where('inventory_sub_category_id', $data['inventory_sub_category_id'] ?? null)
                ->update(['is_default' => false]);
        }

        InventoryArticleFilterPreset::query()->create([
            'user_id' => $userId,
            'inventory_category_id' => $data['inventory_category_id'] ?? null,
            'inventory_sub_category_id' => $data['inventory_sub_category_id'] ?? null,
            'name' => $data['name'],
            'is_default' => (bool)($data['is_default'] ?? false),
            'filters' => $data['filters'] ?? [],
            'tag_ids' => $data['tag_ids'] ?? [],
        ]);

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, InventoryArticleFilterPreset $preset)
    {
        abort_unless($preset->user_id === auth()->id(), 403);

        $data = $request->validate([
            'name' => ['sometimes','string','max:80'],
            'filters' => ['sometimes','array'],
            'tag_ids' => ['sometimes','array'],
            'is_default' => ['sometimes','boolean'],
        ]);

        if (array_key_exists('is_default', $data)) {
            if ($data['is_default'] === true) {
                InventoryArticleFilterPreset::query()
                    ->where('user_id', auth()->id())
                    ->where('inventory_category_id', $preset->inventory_category_id)
                    ->where('inventory_sub_category_id', $preset->inventory_sub_category_id)
                    ->update(['is_default' => false]);
            }
        }

        $preset->update($data);

        return response()->json(['ok' => true]);
    }

    public function destroy(InventoryArticleFilterPreset $preset)
    {
        abort_unless($preset->user_id === auth()->id(), 403);
        $preset->delete();

        return response()->json(['ok' => true]);
    }
}
