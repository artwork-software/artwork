<?php

namespace Artwork\Modules\Department\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin \Artwork\Modules\Department\Models\Department
 */

class DepartmentIndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        // In manchen Kontexten wird diese Resource mit einem Array statt einem Eloquent-Model befüllt.
        // Daher verwenden wir data_get und prüfen methodenbasierten Zugriff defensiv.

        $resource = $this->resource; // kann Model oder Array sein

        $departmentId   = data_get($resource, 'id');
        $departmentName = data_get($resource, 'name');
        $svgName        = data_get($resource, 'svg_name');

        // Users können als Relation (Collection von Modellen) oder als Array vorliegen
        $usersRaw = data_get($resource, 'users', []);
        $usersCol = $usersRaw instanceof Collection ? $usersRaw : collect($usersRaw);

        $users = $usersCol->map(static function ($user) {
            $isObject = is_object($user);

            $projectManagement = false;
            if ($isObject && method_exists($user, 'can')) {
                $projectManagement = $user->can(PermissionEnum::PROJECT_MANAGEMENT->value);
            } else {
                $projectManagement = (bool) data_get($user, 'project_management', false);
            }

            $displayName = null;
            if ($isObject && method_exists($user, 'getDisplayNameAttribute')) {
                $displayName = $user->getDisplayNameAttribute();
            } else {
                $first = (string) data_get($user, 'first_name', '');
                $last  = (string) data_get($user, 'last_name', '');
                $displayName = trim($first . ' ' . $last);
            }

            $type = $isObject && method_exists($user, 'getTypeAttribute')
                ? $user->getTypeAttribute()
                : data_get($user, 'type');

            $assignedCraftIds = $isObject && method_exists($user, 'getAssignedCraftIdsAttribute')
                ? $user->getAssignedCraftIdsAttribute()
                : (array) data_get($user, 'assigned_craft_ids', []);

            return [
                'resource' => $isObject ? class_basename($user) : 'User',
                'id' => data_get($user, 'id'),
                'first_name' => data_get($user, 'first_name'),
                'last_name' => data_get($user, 'last_name'),
                'profile_photo_url' => data_get($user, 'profile_photo_url'),
                'email' => data_get($user, 'email'),
                'departments' => data_get($user, 'departments'),
                'position' => data_get($user, 'position'),
                'business' => data_get($user, 'business'),
                'phone_number' => data_get($user, 'phone_number'),
                'project_management' => $projectManagement,
                'display_name' => $displayName,
                'type' => $type,
                'assigned_craft_ids' => $assignedCraftIds,
            ];
        });

        return [
            'resource' => is_object($resource) ? class_basename($resource) : 'Department',
            'id' => $departmentId,
            'name' => $departmentName,
            'svg_name' => $svgName,
            'users' => $users,
            // Alternativ (wenn immer Models): 'users' => UserIndexResource::collection($this->users)->resolve()
        ];
    }
}
