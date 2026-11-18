<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryTag;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;

class InventoryTagPermissionService
{
    public function userCanEditTag(User $user, InventoryTag $tag): bool
    {
        if (! $tag->has_restricted_permissions) {
            return true;
        }

        // User direkt freigegeben
        if ($tag->allowedUsers->contains('id', $user->id)) {
            return true;
        }

        // Über Departments (Teams) freigegeben
        $userDepartmentIds = $user->departments->pluck('id')->all();

        if ($tag->allowedDepartments->whereIn('id', $userDepartmentIds)->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * Ein Artikel ist bearbeitbar, wenn:
     * - er keine Tags hat, oder
     * - alle Tags entweder keine Einschränkung haben oder der User für diesen Tag berechtigt ist.
     */
    public function userCanEditArticle(User $user, InventoryArticle $article): bool
    {
        /** @var Collection<InventoryTag> $tags */
        $tags = $article->tags;

        if ($tags->isEmpty()) {
            return true;
        }

        foreach ($tags as $tag) {
            if (! $tag->has_restricted_permissions) {
                continue;
            }

            if (! $this->userCanEditTag($user, $tag)) {
                return false;
            }
        }

        return true;
    }
}
