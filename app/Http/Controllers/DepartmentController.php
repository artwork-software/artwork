<?php

namespace App\Http\Controllers;

use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Department\Http\Requests\StoreDepartmentRequest;
use Artwork\Modules\Department\Http\Requests\UpdateDepartmentRequest;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Services\DepartmentService;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
        private readonly UserService $userService,
    ) {
        $this->authorizeResource(Department::class);
    }

    public function index(): Response|ResponseFactory
    {
        return inertia(
            'Departments/DepartmentManagement',
            [
                'departments' => $this->departmentService->getDepartmentIndexResource(),
                'users' => $this->userService->getAllUsers()
            ]
        );
    }

    public function store(
        StoreDepartmentRequest $storeDepartmentRequest,
        NotificationService $notificationService
    ): RedirectResponse {
        $this->departmentService->createByRequest($storeDepartmentRequest, $notificationService);
        return Redirect::route('departments');
    }

    public function show(Department $department): Response|ResponseFactory
    {
        return inertia(
            'Departments/Show',
            [
                'department' => $this->departmentService->createDepartmentShowResource($department)
            ]
        );
    }

    public function edit(Department $department): Response|ResponseFactory
    {
        return inertia(
            'Departments/Edit',
            [
                'department' => $this->departmentService->createDepartmentShowResource($department),
                'users' => $this->userService->getAllUsers(),
            ]
        );
    }

    public function update(
        UpdateDepartmentRequest $updateDepartmentRequest,
        Department $department,
        UserService $userService,
        NotificationService $notificationService
    ): RedirectResponse {
        $this->departmentService->updateByRequest(
            $updateDepartmentRequest,
            $department,
            $userService,
            $notificationService
        );
        return Redirect::route('departments.show', $department->id);
    }

    public function removeAllMembers(Department $department)
    {
        $this->departmentService->removeAllMembers($department);
        return Redirect::route('departments.show', $department->id);
    }

    public function destroy(Department $department, NotificationService $notificationService): RedirectResponse
    {
        $this->departmentService->deleteDepartment($department, $notificationService);
        return Redirect::route('departments');
    }

    public function search(SearchRequest $request): Collection
    {
        return $this->departmentService->searchDepartments($request->get('query'));
    }
}
