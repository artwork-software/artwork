<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gewerks-Scoping für planende Personen: Admins dürfen alle Gewerke, alle anderen nur Gewerke,
 * die „für alle planbar" sind (assignable_by_all) oder für die sie in craft_users als
 * Planer*in eingetragen sind. EINE Quelle für Wochenstatus, Festschreibung und „Woche kopieren".
 */
class CraftScopeService
{
    /**
     * IDs der planbaren Gewerke; null = keine Einschränkung (Admin).
     *
     * @return array<int, int>|null
     */
    public function plannableCraftIdsFor(User $user): ?array
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return null;
        }

        return $this->applyPlannableScope(Craft::query()->select('id'), $user)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Schränkt eine Craft-Query auf die planbaren Gewerke der Person ein (Admins: unverändert).
     */
    public function applyPlannableScope(Builder $query, User $user): Builder
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return $query;
        }

        return $query->where(static function (Builder $sub) use ($user): void {
            $sub->where('assignable_by_all', true)
                ->orWhereHas('craftShiftPlaner', static fn ($planers) => $planers->where('user_id', $user->id));
        });
    }

    /**
     * Angefragte Gewerks-IDs auf die planbare Menge zuschneiden. Leere Anfrage = alle planbaren
     * Gewerke (Admin: null = ohne Filter). Fremde IDs werden stillschweigend entfernt.
     *
     * @param array<int, int>|null $requested
     * @return array<int, int>|null
     */
    public function restrictToPlannable(User $user, ?array $requested): ?array
    {
        $allowed = $this->plannableCraftIdsFor($user);
        $requested = $requested === null || $requested === []
            ? null
            : array_values(array_unique(array_map('intval', $requested)));

        if ($allowed === null) {
            return $requested;
        }

        if ($requested === null) {
            return $allowed;
        }

        return array_values(array_intersect($requested, $allowed));
    }

    /**
     * IDs aus $requested, die die Person NICHT planen darf (Admin: immer leer).
     *
     * @param array<int, int> $requested
     * @return array<int, int>
     */
    public function forbiddenCraftIds(User $user, array $requested): array
    {
        $allowed = $this->plannableCraftIdsFor($user);
        if ($allowed === null) {
            return [];
        }

        return array_values(array_diff(array_map('intval', $requested), $allowed));
    }
}
