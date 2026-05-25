<?php

namespace Artwork\Modules\Change\Services;

use Artwork\Modules\Change\Builders\ChangeBuilder;
use Artwork\Modules\Change\DTO\ChangeDTO;
use Artwork\Modules\Change\Interfaces\Builder;
use Artwork\Modules\Change\Repositories\ChangeRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Activitylog\Contracts\Activity;

class ChangeService
{
    public function __construct(
        private ChangeRepository $changeRepository
    ) {
    }

    public function createBuilder(): ChangeBuilder
    {
        return ChangeBuilder::newInstance();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function saveFromBuilder(Builder $builder): Activity
    {
        return $this->changeRepository->save($builder->build());
    }

    /**
     * Activity history for arbitrary callers as typed DTOs.
     *
     * @return Collection<int, ChangeDTO>
     */
    public function historyFor(Model $model): Collection
    {
        return $this->activitiesFor($model)
            ->map(fn (Activity $activity): ChangeDTO => ChangeDTO::fromActivity($activity))
            ->values();
    }

    /**
     * Returns the legacy frontend payload expected by all `*_history`
     * Resource entries: an array of `{changes, change_by, created_at}` rows
     * ordered newest first. `changes` mirrors the array shape that the
     * Antonrom-backed `ChangeBuilder` produced, so the Vue side is
     * unchanged.
     *
     * @return array<int, array<string, mixed>>
     */
    public function historyForFrontend(Model $model): array
    {
        return $this->activitiesFor($model)
            ->map(fn (Activity $activity): array => [
                'changes' => $this->propertiesAsArray($activity),
                'change_by' => $activity->causer,
                'created_at' => $this->formatTimestamp($activity),
            ])
            ->values()
            ->all();
    }

    /**
     * Variant for `RoomCalendarResource`: causer info is inlined into every
     * `changes` entry as `changed_by`, matching the legacy behaviour.
     *
     * @return array<int, array<string, mixed>>
     */
    public function historyForFrontendWithInlineCauser(Model $model): array
    {
        return $this->activitiesFor($model)
            ->map(function (Activity $activity): array {
                $causer = $activity->causer;
                $changerData = $causer ? [
                    'id' => $causer->id,
                    'first_name' => $causer->first_name ?? null,
                    'last_name' => $causer->last_name ?? null,
                    'profile_photo_url' => $causer->profile_photo_url ?? null,
                ] : null;

                $changes = array_map(function ($item) use ($changerData) {
                    if (is_array($item)) {
                        $item['changed_by'] = $changerData;
                    }
                    return $item;
                }, $this->propertiesAsArray($activity));

                return [
                    'changes' => $changes,
                    'created_at' => $this->formatTimestamp($activity),
                ];
            })
            ->values()
            ->all();
    }

    public function latestHistoryEntry(Model $model): ?Activity
    {
        return $model->activities()->latest()->first();
    }

    /**
     * @return Collection<int, Activity>
     */
    private function activitiesFor(Model $model): Collection
    {
        if ($model->relationLoaded('activities')) {
            return $model->activities->sortByDesc('created_at')->values();
        }

        return $model->activities()->latest()->get();
    }

    /**
     * @return array<int|string, mixed>
     */
    private function propertiesAsArray(Activity $activity): array
    {
        $properties = $activity->properties;

        if ($properties instanceof Collection) {
            return $properties->all();
        }

        return is_array($properties) ? $properties : (array) $properties;
    }

    private function formatTimestamp(Activity $activity): string
    {
        $createdAt = $activity->created_at;

        if ($createdAt === null) {
            return '';
        }

        return $createdAt->diffInHours() < 24
            ? $createdAt->diffForHumans()
            : $createdAt->format('d.m.Y, H:i');
    }
}
