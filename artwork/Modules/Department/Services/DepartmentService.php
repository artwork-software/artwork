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

readonly class DepartmentService
{
    public function __construct(private DepartmentRepository $departmentRepository)
    {
    }

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

    public function removeAllMembers(Department $department): void
    {
        $this->departmentRepository->syncUsers($department, []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDepartmentIndexResource(): array
    {
        return DepartmentIndexResource::collection($this->departmentRepository->getAllDepartments())->resolve();
    }

    public function createByRequest(StoreDepartmentRequest $request, NotificationService $notificationService): void
    {
        $department = new Department();
        $department->fill($request->only(['name', 'svg_name']));
        $this->departmentRepository->save($department);

        $this->departmentRepository->syncUsers(
            $department,
            Collection::make($request->get('assigned_users'))->pluck('id')->toArray()
        );

        foreach ($this->departmentRepository->getDepartmentUsers($department) as $user) {
            $this->sendAddedToDepartmentNotification($department, $user, $notificationService);
        }

        $this->broadcastDepartmentUpdated();
    }

    public function updateByRequest(
        UpdateDepartmentRequest $updateDepartmentRequest,
        Department $department,
        UserService $userService,
        NotificationService $notificationService
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
                    $userService->findUser($currentTeamMemberId),
                    $notificationService
                );
            }
        }

        foreach ($previousTeamMemberIds as $previousTeamMemberId) {
            if (!$currentTeamMemberIds->contains($previousTeamMemberId)) {
                $user = $userService->findUser($previousTeamMemberId);
                $this->sendTeamNotification(
                    //'Du wurdest aus Team ' . $department->name . ' gelöscht',
                    __('notification.department.remove', ['department' => $department->name], $user->language),
                    'red',
                    2,
                    'error',
                    $department->id,
                    $user,
                    $notificationService
                );
            }
        }

        $this->broadcastDepartmentUpdated();
    }

    public function deleteDepartment(Department $department, NotificationService $notificationService): void
    {
        foreach ($this->departmentRepository->getDepartmentUsers($department) as $user) {
            $this->sendTeamNotification(
                __('notification.department.delete', ['department' => $department->name], $user->language),
                'red',
                2,
                'error',
                $department->id,
                $user,
                $notificationService
            );
        }

        $department->delete();

        $this->broadcastDepartmentUpdated();
    }

    public function sendAddedToDepartmentNotification(
        Department $department,
        User $user,
        NotificationService $notificationService
    ): void {
        $this->sendTeamNotification(
            __('notification.department.add', ['department' => $department->name], $user->language),
            'green',
            3,
            'success',
            $department->id,
            $user,
            $notificationService
        );
    }

    private function sendTeamNotification(
        string $notificationTitle,
        string $icon,
        int $priority,
        string $broadcastType,
        int $departmentId,
        User $user,
        NotificationService $notificationService
    ): void {
        $notificationService->setTitle($notificationTitle);
        $notificationService->setIcon($icon);
        $notificationService->setPriority($priority);
        $notificationService->setDepartmentId($departmentId);
        $notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_TEAM);
        $notificationService->setBroadcastMessage(
            [
                'id' => rand(10, 1000000),
                'type' => $broadcastType,
                'message' => $notificationTitle
            ]
        );
        $notificationService->setNotificationTo($user);
        $notificationService->createNotification();
    }

    public function broadcastDepartmentUpdated(): void
    {
        broadcast(new DepartmentUpdated())->toOthers();
    }

    public function createDepartmentShowResource(Department $department): DepartmentShowResource
    {
        return new DepartmentShowResource($department);
    }
}
