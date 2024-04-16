<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use Artwork\Modules\Department\Http\Requests\StoreDepartmentRequest;
use Artwork\Modules\Department\Http\Requests\UpdateDepartmentRequest;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Services\DepartmentService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class DepartmentController extends Controller
{
    /**
     * @param DepartmentService $departmentService
     * @param UserService $userService
     */
    public function __construct(
        private readonly DepartmentService $departmentService,
        private readonly UserService $userService,
    ) {
        $this->authorizeResource(Department::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
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

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDepartmentRequest $storeDepartmentRequest
     * @return RedirectResponse
     */
    public function store(StoreDepartmentRequest $storeDepartmentRequest): RedirectResponse
    {
        $this->departmentService->createByRequest($storeDepartmentRequest);
        return Redirect::route('departments');
    }

    /**
     * Show the specified resource.
     *
     * @param Department $department
     * @return Response|ResponseFactory
     */
    public function show(Department $department): Response|ResponseFactory
    {
        return inertia(
            'Departments/Show',
            [
                'department' => $this->departmentService->createDepartmentShowResource($department)
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Department $department
     * @return Response|ResponseFactory
     */
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

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDepartmentRequest $updateDepartmentRequest
     * @param Department $department
     * @return RedirectResponse
     */
    public function update(UpdateDepartmentRequest $updateDepartmentRequest, Department $department): RedirectResponse
    {
        $this->departmentService->updateByRequest($updateDepartmentRequest, $department);
        return Redirect::route('departments.show', $department->id);
    }

    public function removeAllMembers(Department $department)
    {
        $this->departmentService->removeAllMembers($department);
        return Redirect::route('departments.show', $department->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Department $department
     * @return RedirectResponse
     */
    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->deleteDepartment($department);
        return Redirect::route('departments');
    }

    /**
     * @param SearchRequest $request
     * @return Collection
     */
    public function search(SearchRequest $request): Collection
    {
        return $this->departmentService->searchDepartments($request->get('query'));
    }
}
