<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Models\ProjectUserPivot;
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
     * column) and managers always get write access. With $current (PATCH of
     * an existing member) fields the request does not send keep their stored
     * value instead of being reset to false/empty.
     *
     * @return array{is_manager: bool, can_write: bool, access_budget: bool, roles: list<int>}
     */
    public function pivot(?ProjectUserPivot $current = null): array
    {
        $flag = fn (string $key): bool => $this->has($key)
            ? $this->boolean($key)
            : (bool) ($current?->getAttribute($key) ?? false);
        $roleIds = $this->has('roles')
            ? $this->validated('roles', [])
            : ($current?->getAttribute('roles') ?? []);

        return ProjectTeamService::withManagerWriteRight([
            'is_manager' => $flag('is_manager'),
            'can_write' => $flag('can_write'),
            'access_budget' => $flag('access_budget'),
            'roles' => ProjectRole::query()
                ->whereIn('id', (array) $roleIds)
                ->pluck('id')
                ->all(),
        ]);
    }
}
