<?php

// app/Modules/Inventory/Services/InventoryArticleFilterResolver.php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryArticleFilterPreset;
use Artwork\Modules\Inventory\Models\InventoryArticleFilterState;
use Illuminate\Support\Facades\Request;

class InventoryArticleFilterResolver
{
    public function resolve(?int $categoryId, ?int $subCategoryId): array
    {
        $user = auth()->user();
        $presetId = Request::integer('filter_preset_id') ?: null;

        $hasExplicit =
            Request::has('filters') ||
            Request::has('tag_ids') ||
            $presetId !== null;

        $filters = [];
        $tagIds  = [];
        $source  = 'empty';

        if ($user && $presetId) {
            $preset = InventoryArticleFilterPreset::query()
                ->where('user_id', $user->id)
                ->where('id', $presetId)
                ->first();

            if ($preset) {
                $filters = $this->normalizeFilters($preset->filters ?? []);
                $tagIds  = $this->normalizeTagIds($preset->tag_ids ?? []);
                $source  = 'preset';
            }
        }

        if ($source !== 'preset') {
            if (Request::has('filters') || Request::has('tag_ids')) {
                $filters = $this->parseFiltersFromRequest();
                $tagIds  = $this->parseTagIdsFromRequest();
                $source  = 'request';
            } elseif ($user) {
                $defaultPreset = InventoryArticleFilterPreset::query()
                    ->where('user_id', $user->id)
                    ->where('is_default', true)
                    ->where(function ($q) use ($categoryId, $subCategoryId): void {
                        $q->where(function ($qq) use ($categoryId, $subCategoryId): void {
                            $qq->where('inventory_category_id', $categoryId)
                                ->where('inventory_sub_category_id', $subCategoryId);
                        })->orWhere(function ($qq): void {
                            $qq->whereNull('inventory_category_id')
                                ->whereNull('inventory_sub_category_id');
                        });
                    })
                    ->orderByRaw('CASE WHEN inventory_category_id IS NULL THEN 1 ELSE 0 END')
                    ->first();

                if ($defaultPreset) {
                    $filters = $this->normalizeFilters($defaultPreset->filters ?? []);
                    $tagIds  = $this->normalizeTagIds($defaultPreset->tag_ids ?? []);
                    $source  = 'default_preset';
                } else {
                    $state = InventoryArticleFilterState::query()
                        ->where('user_id', $user->id)
                        ->where('inventory_category_id', $categoryId)
                        ->where('inventory_sub_category_id', $subCategoryId)
                        ->first();

                    if ($state) {
                        $filters = $this->normalizeFilters($state->filters ?? []);
                        $tagIds  = $this->normalizeTagIds($state->tag_ids ?? []);
                        $source  = 'state';
                    }
                }
            }
        }

        if ($user && $hasExplicit) {
            InventoryArticleFilterState::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'inventory_category_id' => $categoryId,
                    'inventory_sub_category_id' => $subCategoryId,
                ],
                [
                    'filters' => $filters,
                    'tag_ids' => $tagIds,
                ]
            );
        }

        return [
            'filters' => $filters,
            'tag_ids' => $tagIds,
            'filter_preset_id' => $presetId,
            'source' => $source,
        ];
    }

    private function parseFiltersFromRequest(): array
    {
        $raw = Request::get('filters', []);
        if (is_array($raw)) {
            return $this->normalizeFilters($raw);
        }

        if (is_string($raw) && $raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                return $this->normalizeFilters(is_array($decoded) ? $decoded : []);
            } catch (\Throwable) {
                return [];
            }
        }

        return [];
    }

    private function parseTagIdsFromRequest(): array
    {
        $raw = Request::input('tag_ids', []);

        if (is_array($raw)) {
            return $this->normalizeTagIds($raw);
        }

        if (is_string($raw) && $raw !== '') {
            return $this->normalizeTagIds(explode(',', $raw));
        }

        return [];
    }

    private function normalizeFilters(array $filters): array
    {
        $out = [];
        foreach ($filters as $f) {
            if (!is_array($f)) {
                continue;
            }

            $pid = isset($f['property_id']) ? (int)$f['property_id'] : null;
            $op  = isset($f['operator']) ? (string)$f['operator'] : 'like';
            $val = $f['value'] ?? null;

            if (!$pid) {
                continue;
            }
            $out[] = [
                'property_id' => $pid,
                'operator' => $op,
                'value' => $val,
            ];
        }
        return $out;
    }

    private function normalizeTagIds(array $tagIds): array
    {
        $tagIds = array_map(static fn($v) => (int)$v, $tagIds);
        $tagIds = array_values(array_filter($tagIds, static fn($v) => $v > 0));
        return $tagIds;
    }
}
