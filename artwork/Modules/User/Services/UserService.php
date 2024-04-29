<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

readonly class UserService
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function getAuthUser(): ?User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $id
     * @return User
     */
    public function findUser(int $id): User
    {
        return $this->userRepository->findUser($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false
    ): array {
        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($this->userRepository->getWorkers() as $user) {
            $desiredUserResource = $desiredResourceClass::make($user);

            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $userData = [
                'user' => $desiredUserResource,
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * ($startDate->diffInDays($endDate) + 1),
            ];

            if ($addVacationsAndAvailabilities) {
                $userData['vacations'] = $user->getVacationDays();
                $userData['availabilities'] = $this->userRepository
                    ->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                        $user,
                        $startDate,
                        $endDate
                    );
            }

            $usersWithPlannedWorkingHours[] = $userData;
        }

        return $usersWithPlannedWorkingHours;
    }

    public function getAuthUserCrafts(): Collection
    {
        return $this->getAuthUser()->crafts;
    }
}
