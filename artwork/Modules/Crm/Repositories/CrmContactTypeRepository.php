<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Models\CrmContactType;
use Illuminate\Database\Eloquent\Collection;

class CrmContactTypeRepository
{
    public function getAll(): Collection
    {
        return CrmContactType::orderBy('sort_order')->get();
    }

    public function getAllWithProperties(): Collection
    {
        return CrmContactType::with('properties')
            ->orderBy('sort_order')
            ->get();
    }

    public function getActive(): Collection
    {
        return CrmContactType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getBySlug(string $slug): ?CrmContactType
    {
        return CrmContactType::where('slug', $slug)
            ->with('properties')
            ->first();
    }

    public function create(array $data): CrmContactType
    {
        return CrmContactType::create($data);
    }

    public function update(CrmContactType $type, array $data): bool
    {
        return $type->update($data);
    }

    public function delete(CrmContactType $type): ?bool
    {
        return $type->delete();
    }
}
