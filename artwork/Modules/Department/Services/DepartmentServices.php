<?php

namespace Artwork\Modules\Department\Services;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Department\Repositories\DepartmentRepository;
use Illuminate\Http\Request;

class DepartmentServices
{
    public function __construct(private readonly DepartmentRepository $departmentRepository){}

    public function createByRequest(Request $request){
        $department = new Department();
        $department->fill($request->only([]));
        return $this->departmentRepository->save($department);
    }
}
