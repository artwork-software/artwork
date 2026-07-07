<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\Crm\Repositories\CrmPropertyValueRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CrmPropertyValueService
{
    public function __construct(
        private CrmPropertyValueRepository $repository,
    ) {}

    public function getForContact(int $contactId): Collection
    {
        return $this->repository->getForContact($contactId);
    }

    public function updateOrCreate(int $contactId, int $propertyId, ?string $value): CrmPropertyValue
    {
        return $this->repository->updateOrCreate($contactId, $propertyId, $value);
    }
}
