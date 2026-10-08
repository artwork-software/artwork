<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectTeamService;
use Illuminate\Foundation\Http\FormRequest;

class AppTeamRightsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_manager' => ['sometimes', 'boolean'],
            'can_write' => ['sometimes', 'boolean'],
            'access_budget' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer'],
        ];
    }

    /**
     * The project_user pivot columns, with the web's rules: only role ids that
     * exist are persisted (deleting a role cannot clean up the pivot's JSON
     * column) and managers always get write access.
     *
     * @return array{is_manager: bool, can_write: bool, access_budget: bool, roles: list<int>}
     */
    public function pivot(): array
    {
        return ProjectTeamService::withManagerWriteRight([
            'is_manager' => $this->boolean('is_manager'),
            'can_write' => $this->boolean('can_write'),
            'access_budget' => $this->boolean('access_budget'),
            'roles' => ProjectRole::query()
                ->whereIn('id', $this->validated('roles', []))
                ->pluck('id')
                ->all(),
        ]);
    }
}
