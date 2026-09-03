<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CraftService
{
    public function __construct(
        private readonly CraftRepository $craftRepository
    ) {
    }

    public function getAll(array $with = []): Collection
    {
        return $this->craftRepository->getAll($with);
    }

    public function storeByRequest(CraftStoreRequest $craftStoreRequest): void
    {
        $craft = new Craft();
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all', 'universally_applicable']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
            // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop
            User::forgetCachedShareDataForIds($craftStoreRequest->get('users') ?? []);
        }
    }

    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest
            ->only([
                'name',
                'abbreviation',
                'assignable_by_all',
                'color',
                'notify_days',
                'commit_request_deadline_days',
                'universally_applicable',
            ]));

        $managersToBeAssigned = $craftUpdateRequest->collect('managersToBeAssigned')->groupBy(
            function ($managerToBeAssigned) {
                return $managerToBeAssigned['manager_type'];
            }
        );

        if ($craftUpdateRequest->has('qualifications')) {
            $craft->qualifications()->detach();
            $craft->qualifications()->sync($craftUpdateRequest->collect('qualifications')->pluck('id')->toArray());
        }

        if ($managersToBeAssigned->empty()) {
            $craft->managingUsers()->sync([]);
            $craft->managingFreelancers()->sync([]);
            $craft->managingServiceProviders()->sync([]);
        }

        foreach ($managersToBeAssigned as $managerType => $managers) {
            switch ($managerType) {
                case User::class:
                    $craft->managingUsers()->sync($managers->pluck('manager_id'));
                    break;
                case Freelancer::class:
                    $craft->managingFreelancers()->sync($managers->pluck('manager_id'));
                    break;
                case ServiceProvider::class:
                    $craft->managingServiceProviders()->sync($managers->pluck('manager_id'));
                    break;
            }
        }

        // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop —
        // bisherige und neue Planer invalidieren
        $previousPlanerIds = $craft->craftShiftPlaner()->pluck('users.id')->all();
        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
            User::forgetCachedShareDataForIds(array_unique([
                ...$previousPlanerIds,
                ...($craftUpdateRequest->get('users') ?? []),
            ]));
        } else {
            $this->craftRepository->detachUsers($craft);
            User::forgetCachedShareDataForIds($previousPlanerIds);
        }
    }

    public function delete(Craft $craft): void
    {
        $previousPlanerIds = $craft->craftShiftPlaner()->pluck('users.id')->all();
        $this->craftRepository->detachUsers($craft);
        $this->craftRepository->delete($craft);
        // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop
        User::forgetCachedShareDataForIds($previousPlanerIds);
    }

    public function getAssignableByAllCrafts(): Collection
    {
        return $this->craftRepository->getAssignableByAllCrafts();
    }

    public function findById(int $id): Craft
    {
        return $this->craftRepository->findById($id);
    }

    public function reorder(array $crafts): void
    {
        foreach ($crafts as $craft) {
            $this->craftRepository->findById($craft['id'])->update(['position' => $craft['position']]);
        }
    }
}
