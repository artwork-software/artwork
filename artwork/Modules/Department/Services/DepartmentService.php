<?php

namespace Artwork\Modules\Department\Services;

use App\Enums\NotificationConstEnum;
use App\Events\DepartmentUpdated;
use App\Http\Resources\DepartmentIndexResource;
use App\Http\Resources\DepartmentShowResource;
use App\Http\Resources\UserIndexResource;
use App\Models\User;
use App\Support\Services\NotificationService;
use Artwork\Modules\Department\Http\Requests\StoreDepartmentRequest;
use Artwork\Modules\Department\Http\Requests\UpdateDepartmentRequest;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Repositories\DepartmentRepository;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    /**
     * @param NotificationService $notificationService
     * @param UserService $userService
     * @param DepartmentRepository $departmentRepository
     */
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly UserService $userService,
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    /**
     * @param string $query
     * @return Collection
     */
    public function searchDepartments(string $query): \Illuminate\Support\Collection
    {
        return $this->departmentRepository
            ->searchDepartments($query)
            ->map(
                fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'svg_name' => $department->svg_name,
                    'users' => UserIndexResource::collection($department->users)->resolve()
                ]
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDepartmentIndexResource(): array
    {
        return DepartmentIndexResource::collection($this->departmentRepository->getAllDepartments())->resolve();
    }

    /**
     * @param StoreDepartmentRequest $request
     * @return void
     */
    public function createByRequest(StoreDepartmentRequest $request): void
    {
        $department = new Department();
        $department->fill($request->only(['name', 'svg_name']));
        $this->departmentRepository->save($department);

        $this->departmentRepository->syncUsers(
            $department,
            Collection::make($request->get('assigned_users'))->pluck('id')->toArray()
        );

        foreach ($this->departmentRepository->getDepartmentUsers($department) as $user) {
            $this->sendAddedToDepartmentNotification($department, $user);
        }

        $this->broadcastDepartmentUpdated();
    }

    /**
     * @param UpdateDepartmentRequest $updateDepartmentRequest
     * @param Department $department
     * @return void
     */
    public function updateByRequest(
        UpdateDepartmentRequest $updateDepartmentRequest,
        Department $department
    ): void {
        $department->update($updateDepartmentRequest->only('name', 'svg_name'));

        $previousTeamMemberIds = $this->departmentRepository->getDepartmentUsers($department)->pluck('id');
        $this->departmentRepository->syncUsers(
            $department,
            Collection::make($updateDepartmentRequest->get('users'))->pluck('id')->toArray()
        );
        $currentTeamMemberIds = $this->departmentRepository->getDepartmentUsers($department)->pluck('id');

        foreach ($currentTeamMemberIds as $currentTeamMemberId) {
            if (!$previousTeamMemberIds->contains($currentTeamMemberId)) {
                $this->sendAddedToDepartmentNotification(
                    $department,
                    $this->userService->findUser($currentTeamMemberId)
                );
            }
        }

        foreach ($previousTeamMemberIds as $previousTeamMemberId) {
            if (!$currentTeamMemberIds->contains($previousTeamMemberId)) {
                $this->sendTeamNotification(
                    'Du wurdest aus Team ' . $department->name . ' gelöscht',
                    'red',
                    2,
                    'error',
                    $department->id,
                    $this->userService->findUser($previousTeamMemberId)
                );
            }
        }

        $this->broadcastDepartmentUpdated();
    }

    /**
     * @param Department $department
     * @return void
     */
    public function deleteDepartment(Department $department): void
    {
        foreach ($this->departmentRepository->getDepartmentUsers($department) as $user) {
            $this->sendTeamNotification(
                'Team "' . $department->name . '" wurde gelöscht',
                'red',
                2,
                'error',
                $department->id,
                $user
            );
        }

        $department->delete();

        $this->broadcastDepartmentUpdated();
    }

    /**
     * @param Department $department
     * @param User $user
     * @return void
     */
    public function sendAddedToDepartmentNotification(Department $department, User $user): void
    {
        $this->sendTeamNotification(
            'Du wurdest zu Team ' . $department->name . ' hinzugefügt',
            'green',
            3,
            'success',
            $department->id,
            $user
        );
    }

    /**
     * @param string $notificationTitle
     * @param string $icon
     * @param int $priority
     * @param string $broadcastType
     * @param int $departmentId
     * @param User $user
     * @return void
     */
    private function sendTeamNotification(
        string $notificationTitle,
        string $icon,
        int $priority,
        string $broadcastType,
        int $departmentId,
        User $user
    ): void {
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setIcon($icon);
        $this->notificationService->setPriority($priority);
        $this->notificationService->setDepartmentId($departmentId);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TEAM);
        $this->notificationService->setBroadcastMessage(
            [
                'id' => rand(10, 1000000),
                'type' => $broadcastType,
                'message' => $notificationTitle
            ]
        );
        $this->notificationService->setNotificationTo($user);
        $this->notificationService->createNotification();
    }

    /**
     * @return void
     */
    public function broadcastDepartmentUpdated(): void
    {
        broadcast(new DepartmentUpdated())->toOthers();
    }

    /**
     * @param Department $department
     * @return DepartmentShowResource
     */
    public function createDepartmentShowResource(Department $department): DepartmentShowResource
    {
        return new DepartmentShowResource($department);
    }
}
