<?php

namespace Artwork\Modules\Crm\Repositories;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CrmContactRepository
{
    public function getByType(
        int $typeId,
        ?string $search = null,
        int $perPage = 15,
        array $filters = [],
        array $allowedPropertyIds = [],
        ?string $sort = null,
        string $direction = 'asc'
    ): LengthAwarePaginator {
        $query = CrmContact::where('crm_contact_type_id', $typeId)
            ->with([
                'contactType',
                // Only expose property values the user is allowed to see
                // (confidential groups) — mirrors the filtering in show()/getData().
                'propertyValues' => fn ($q) => $q->whereIn('crm_property_id', $allowedPropertyIds),
                'propertyValues.property',
            ]);

        $this->applySearch($query, $search, $allowedPropertyIds);
        $this->applyPropertyFilters($query, $filters, $allowedPropertyIds);
        $this->applySort($query, $sort, $direction, $allowedPropertyIds);

        return $query->paginate($perPage);
    }

    /**
     * Sucht im Anzeigenamen und in den Werten textartiger Eigenschaften (z.B. E-Mail,
     * Telefon). $allowedPropertyIds = null bedeutet: keine Einschränkung (CRM-Manager).
     */
    public function applySearch($query, ?string $search, ?array $allowedPropertyIds = null): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search, $allowedPropertyIds) {
            $q->where('display_name', 'like', "%{$search}%")
                ->orWhereHas('propertyValues', fn ($pv) => $pv
                    ->when($allowedPropertyIds !== null, fn ($sub) => $sub->whereIn('crm_property_id', $allowedPropertyIds))
                    ->where('value', 'like', "%{$search}%")
                    ->whereHas('property', fn ($p) => $p->whereIn('type', ['text', 'textarea', 'link'])));
        });
    }

    /**
     * Wendet die Eigenschafts-Filter der Kontaktliste an ({propertyId: wert|array}).
     * $allowedPropertyIds = null bedeutet: keine Einschränkung (CRM-Manager).
     */
    public function applyPropertyFilters($query, array $filters, ?array $allowedPropertyIds = null): void
    {
        foreach ($filters as $propertyId => $filterValue) {
            $propId = is_numeric($propertyId) ? (int) $propertyId : null;
            if ($propId === null || $filterValue === null || $filterValue === '' || $filterValue === []) {
                continue;
            }

            // Skip filters for properties the user is not allowed to see (confidential groups)
            if ($allowedPropertyIds !== null && !in_array($propId, $allowedPropertyIds, true)) {
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
    }

    private function applySort($query, ?string $sort, string $direction, array $allowedPropertyIds): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
            return;
        }

        if ($sort !== null && str_starts_with($sort, 'prop_')) {
            $propId = (int) substr($sort, 5);
            if (in_array($propId, $allowedPropertyIds, true)) {
                $property = CrmProperty::find($propId);
                $valueColumn = $property?->type === CrmPropertyTypeEnum::NUMBER
                    ? \Illuminate\Support\Facades\DB::raw('CAST(value AS DECIMAL(20,4))')
                    : 'value';

                $query->orderBy(
                    \Artwork\Modules\Crm\Models\CrmPropertyValue::select($valueColumn)
                        ->whereColumn('crm_contact_id', 'crm_contacts.id')
                        ->where('crm_property_id', $propId)
                        ->limit(1),
                    $direction
                )->orderBy('display_name');
                return;
            }
        }

        $query->orderBy('display_name', $direction);
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
