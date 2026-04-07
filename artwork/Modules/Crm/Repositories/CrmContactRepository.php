<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CrmContactRepository
{
    public function getByType(int $typeId, ?string $search = null, int $perPage = 15, array $filters = [], array $allowedPropertyIds = []): LengthAwarePaginator
    {
        $query = CrmContact::where('crm_contact_type_id', $typeId)
            ->with(['contactType', 'propertyValues.property']);

        if ($search) {
            $query->where('display_name', 'like', "%{$search}%");
        }

        foreach ($filters as $propertyId => $filterValue) {
            $propId = is_numeric($propertyId) ? (int) $propertyId : null;
            if ($propId === null || $filterValue === null || $filterValue === '' || $filterValue === []) {
                continue;
            }

            // Skip filters for properties the user is not allowed to see (confidential groups)
            if (!empty($allowedPropertyIds) && !in_array($propId, $allowedPropertyIds)) {
                continue;
            }

            $query->whereHas('propertyValues', function ($q) use ($propId, $filterValue) {
                $q->where('crm_property_id', $propId);

                if (is_array($filterValue)) {
                    $q->whereIn('value', $filterValue);
                } else {
                    $property = CrmProperty::find($propId);
                    match ($property?->type) {
                        CrmPropertyTypeEnum::TEXT, CrmPropertyTypeEnum::TEXTAREA, CrmPropertyTypeEnum::LINK
                            => $q->where('value', 'like', '%' . $filterValue . '%'),
                        CrmPropertyTypeEnum::NUMBER, CrmPropertyTypeEnum::DATE, CrmPropertyTypeEnum::CHECKBOX
                            => $q->where('value', $filterValue),
                        default => $q->where('value', 'like', '%' . $filterValue . '%'),
                    };
                }
            });
        }

        return $query->orderBy('display_name')->paginate($perPage);
    }

    public function searchForLinking(?string $search = null, ?int $typeId = null, int $limit = 20): Collection
    {
        $query = CrmContact::with(['contactType'])->where('is_active', true);

        if ($typeId) {
            $query->where('crm_contact_type_id', $typeId);
        }

        if ($search) {
            $terms = array_filter(explode(' ', trim($search)));

            $query->where(function ($q) use ($search, $terms) {
                $q->where('display_name', 'like', "%{$search}%");

                $q->orWhereHas('propertyValues', fn ($pv) =>
                    $pv->where('value', 'like', "%{$search}%")
                       ->whereHas('property', fn ($p) => $p->where('type', 'text'))
                );

                if (count($terms) > 1) {
                    $q->orWhere(function ($multi) use ($terms) {
                        foreach ($terms as $term) {
                            $multi->where(fn ($inner) =>
                                $inner->where('display_name', 'like', "%{$term}%")
                                    ->orWhereHas('propertyValues', fn ($pv) =>
                                        $pv->where('value', 'like', "%{$term}%")
                                           ->whereHas('property', fn ($p) => $p->where('type', 'text'))
                                    )
                            );
                        }
                    });
                }
            });
        }

        return $query->orderBy('display_name')->limit($limit)->get();
    }

    public function getAll(): Collection
    {
        return CrmContact::with('contactType')->get();
    }

    public function findById(int $id): ?CrmContact
    {
        return CrmContact::with(['contactType', 'propertyValues.property.group'])->find($id);
    }

    public function create(array $data): CrmContact
    {
        return CrmContact::create($data);
    }

    public function update(CrmContact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    public function delete(CrmContact $contact): ?bool
    {
        return $contact->delete();
    }
}
