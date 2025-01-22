<?php

namespace Artwork\Modules\UserCalendarFilter\Services;

use Artwork\Modules\EventProperty\Models\EventProperty;
use Artwork\Modules\UserCalendarFilter\Repositories\UserCalendarFilterRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class UserCalendarFilterService
{
    public function __construct(private UserCalendarFilterRepository $userCalendarFilterRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->userCalendarFilterRepository->getAll();
    }

    public function update(): EventProperty
    {
    }

    public function updateEventProperties(array $eventPropertyIds): void
    {
    }
}
