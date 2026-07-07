<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Contracts\CrmEntity;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Repositories\CrmContactRepository;
use Artwork\Modules\Crm\Repositories\CrmPropertyValueRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

readonly class CrmContactService
{
    public function __construct(
        private CrmContactRepository $contactRepository,
        private CrmPropertyValueRepository $propertyValueRepository,
    ) {}

    public function getByType(int $typeId, ?string $search = null, int $perPage = 15, array $filters = [], array $allowedPropertyIds = []): LengthAwarePaginator
    {
        return $this->contactRepository->getByType($typeId, $search, $perPage, $filters, $allowedPropertyIds);
    }

    public function searchForLinking(?string $search = null, ?int $typeId = null, int $limit = 20): Collection
    {
        return $this->contactRepository->searchForLinking($search, $typeId, $limit);
    }

    public function findById(int $id): ?CrmContact
    {
        return $this->contactRepository->findById($id);
    }

    public function store(array $data, array $propertyValues = []): CrmContact
    {
        $contact = $this->contactRepository->create([
            'crm_contact_type_id' => $data['crm_contact_type_id'],
            'display_name' => $data['display_name'],
            'profile_image' => $data['profile_image'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->savePropertyValues($contact, $propertyValues);

        return $contact;
    }

    public function update(CrmContact $contact, array $data, array $propertyValues = []): CrmContact
    {
        $this->contactRepository->update($contact, array_filter([
            'display_name' => $data['display_name'] ?? null,
            'profile_image' => $data['profile_image'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        $this->savePropertyValues($contact, $propertyValues);

        // Sync display_name from source entity if available
        $this->syncDisplayName($contact);

        return $contact->fresh(['contactType', 'propertyValues.property']);
    }

    public function destroy(CrmContact $contact): void
    {
        $this->contactRepository->delete($contact);
    }

    public function updateProfileImage(CrmContact $contact, ?string $path): void
    {
        $this->contactRepository->update($contact, ['profile_image' => $path]);
    }

    public function savePropertyValue(CrmContact $contact, int $propertyId, ?string $value): void
    {
        $this->propertyValueRepository->updateOrCreate(
            $contact->id,
            $propertyId,
            $value
        );

        $this->writeBackToSource($contact, $propertyId, $value);
    }

    private function writeBackToSource(CrmContact $contact, int $propertyId, ?string $value): void
    {
        $entity = $contact->getSourceEntity();

        if (!$entity) {
            return;
        }

        $property = CrmProperty::find($propertyId);

        if (!$property) {
            return;
        }

        $entity->setCrmFieldValue($property->name, $value);
    }

    private function syncDisplayName(CrmContact $contact): void
    {
        $entity = $contact->getSourceEntity();

        if (!$entity) {
            return;
        }

        $entity->refresh();
        $displayName = $entity->getCrmDisplayName();

        if ($displayName && $contact->display_name !== $displayName) {
            $contact->update(['display_name' => $displayName]);
        }
    }

    private function savePropertyValues(CrmContact $contact, array $propertyValues): void
    {
        foreach ($propertyValues as $propertyId => $value) {
            $this->savePropertyValue(
                $contact,
                (int) $propertyId,
                $value !== null ? (string) $value : null
            );
        }
    }
}
