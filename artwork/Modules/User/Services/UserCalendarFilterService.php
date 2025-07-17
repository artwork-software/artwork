<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Repositories\UserCalendarFilterRepository;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

readonly class UserCalendarFilterService
{
    public function __construct(private UserCalendarFilterRepository $userCalendarFilterRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->userCalendarFilterRepository->getAll();
    }

    /**
     * @throws Throwable
     */
    public function updateEventPropertiesOfAll(array $eventPropertyIds): void
    {
        foreach ($this->getAll() as $userCalendarFilter) {
            $this->updateOrFail(
                $userCalendarFilter,
                ['event_properties' => $eventPropertyIds]
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function updateOrFail(
        UserCalendarFilter $userCalendarFilter,
        array $attributes
    ): UserCalendarFilter {
        $this->userCalendarFilterRepository->updateOrFail($userCalendarFilter, $attributes);

        return $userCalendarFilter;
    }
}
