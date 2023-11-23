<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;

class CraftService
{
    /**
     * @param CraftRepository $craftRepository
     */
    public function __construct(private readonly CraftRepository $craftRepository)
    {
    }

    /**
     * @param CraftStoreRequest $craftStoreRequest
     * @return void
     */
    public function storeByRequest(CraftStoreRequest $craftStoreRequest): void
    {
        $craft = new Craft();
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
        }
    }

    /**
     * @param CraftUpdateRequest $craftUpdateRequest
     * @param Craft $craft
     * @return void
     */
    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest->only(['name', 'abbreviation', 'assignable_by_all']));
        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
        } else {
            $this->craftRepository->detachUsers($craft);
        }
    }

    /**
     * @param Craft $craft
     * @return void
     */
    public function delete(Craft $craft): void
    {
        $this->craftRepository->detachUsers($craft);
        $this->craftRepository->delete($craft);
        //$craft->delete();
    }
}
