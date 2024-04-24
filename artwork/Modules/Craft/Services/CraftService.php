<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class CraftService
{
    public function __construct(private CraftRepository $craftRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->craftRepository->getAll();
    }

    public function storeByRequest(CraftStoreRequest $craftStoreRequest): void
    {
        $craft = new Craft();
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
        }
    }

    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest->only(['name', 'abbreviation', 'assignable_by_all']));
        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
        } else {
            $this->craftRepository->detachUsers($craft);
        }
    }

    public function delete(Craft $craft): void
    {
        $this->craftRepository->detachUsers($craft);
        $this->craftRepository->delete($craft);
    }
}
