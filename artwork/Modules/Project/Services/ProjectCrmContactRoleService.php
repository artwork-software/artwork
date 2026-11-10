<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentCrmContact;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rollen in der CRM-Kontaktliste (Einstellung „Rollen verwenden“):
 * - Freitext-Rollen je Projekt und Liste; die Reihenfolge der Rollen-Gruppen steht im Projektwert der
 *   Komponente (project_component_values.data.roles), die Rollen eines Kontakts an seiner Listenzeile.
 * - Sonderrolle „Künstler*in“ (is_artist an der Zeile): fest, nicht umbenennbar/löschbar; so markierte
 *   Kontakte sind Projektkünstler*innen. Freitext wie „Künstler*in“ oder „Künstlerin“ wird zur Sonderrolle.
 * - Vorschläge: alle bisher vergebenen Rollen aller Kontaktlisten.
 */
class ProjectCrmContactRoleService
{
    public const MAX_ROLE_LENGTH = 100;

    /** Freitexte, die als Sonderrolle „Künstler*in“ gelten (kleingeschrieben, ohne Leerzeichen am Rand). */
    private const ARTIST_ROLE_ALIASES = [
        'künstler*in', 'künstler:in', 'künstler_in', 'künstlerin', 'künstler', 'künstler*innen',
        'künstlerinnen', 'artist', 'artists',
    ];

    public function rolesEnabled(Component $component): bool
    {
        return filter_var($component->data['roles_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isArtistRoleName(string $name): bool
    {
        return in_array(mb_strtolower(trim($name)), self::ARTIST_ROLE_ALIASES, true);
    }

    /**
     * Rollen-Gruppen dieser Liste: gespeicherte Reihenfolge, danach an Kontakten vergebene Rollen.
     *
     * @return list<string>
     */
    public function roleList(Project $project, Component $component): array
    {
        $stored = (array) ($this->value($project, $component)?->data['roles'] ?? []);
        $used = ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->whereNotNull('roles')
            ->pluck('roles')
            ->flatten()
            ->all();

        return $this->unique([...$stored, ...$used]);
    }

    /**
     * Vorschläge für neue Rollen: alle bisher in Kontaktlisten vergebenen Rollen (alphabetisch).
     *
     * @return list<string>
     */
    public function suggestions(): array
    {
        $fromEntries = ProjectComponentCrmContact::query()->whereNotNull('roles')->pluck('roles')->flatten()->all();
        $fromLists = ProjectComponentValue::query()
            ->whereIn('component_id', Component::query()->where('type', 'CrmContactListComponent')->select('id'))
            ->pluck('data')
            ->map(fn ($data) => (array) ($data['roles'] ?? []))
            ->flatten()
            ->all();

        $roles = $this->unique([...$fromEntries, ...$fromLists]);
        natcasesort($roles);

        return array_values($roles);
    }

    /**
     * @throws ValidationException
     */
    public function addRole(Project $project, Component $component, string $name): string
    {
        $name = $this->normalizeName($name);
        $this->storeList($project, $component, $this->unique([...$this->roleList($project, $component), $name]));

        return $name;
    }

    /**
     * @throws ValidationException
     */
    public function renameRole(Project $project, Component $component, string $oldName, string $newName): void
    {
        $newName = $this->normalizeName($newName);

        DB::transaction(function () use ($project, $component, $oldName, $newName): void {
            $list = array_map(fn (string $role) => $role === $oldName ? $newName : $role, $this->roleList($project, $component));
            $this->storeList($project, $component, $this->unique($list));
            $this->entriesWithRole($project, $component, $oldName)->each(function (ProjectComponentCrmContact $entry) use ($oldName, $newName): void {
                $entry->update(['roles' => $this->unique(array_map(
                    fn (string $role) => $role === $oldName ? $newName : $role,
                    (array) $entry->roles
                ))]);
            });
        });
    }

    public function deleteRole(Project $project, Component $component, string $name): void
    {
        DB::transaction(function () use ($project, $component, $name): void {
            $this->storeList($project, $component, array_values(array_filter(
                $this->roleList($project, $component),
                fn (string $role) => $role !== $name
            )));
            $this->entriesWithRole($project, $component, $name)->each(function (ProjectComponentCrmContact $entry) use ($name): void {
                $roles = array_values(array_filter((array) $entry->roles, fn (string $role) => $role !== $name));
                $entry->update(['roles' => $roles === [] ? null : $roles]);
            });
        });
    }

    /**
     * Rollen eines Kontakts setzen. Freitext „Künstler*in“ & Co. wird zur Sonderrolle; neue Rollen kommen in
     * die Rollenliste der Komponente.
     *
     * @param array<int, mixed>|null $roles null = Rollen unverändert
     * @throws ValidationException
     */
    public function assignToEntry(ProjectComponentCrmContact $entry, ?array $roles, ?bool $isArtist): ProjectComponentCrmContact
    {
        $attributes = [];
        if ($roles !== null) {
            $names = [];
            foreach ($roles as $role) {
                $name = $this->normalizeName((string) $role);
                if (self::isArtistRoleName($name)) {
                    $isArtist = true;
                    continue;
                }
                $names[] = $name;
            }
            $names = $this->unique($names);
            $attributes['roles'] = $names === [] ? null : $names;

            $project = $entry->project()->firstOrFail();
            $component = $entry->component()->firstOrFail();
            $this->storeList($project, $component, $this->unique([...$this->roleList($project, $component), ...$names]));
        }
        if ($isArtist !== null) {
            $attributes['is_artist'] = $isArtist;
        }

        if ($attributes !== []) {
            $entry->update($attributes);
        }

        return $entry;
    }

    /**
     * @throws ValidationException
     */
    public function normalizeName(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ($name === '' || mb_strlen($name) > self::MAX_ROLE_LENGTH) {
            throw ValidationException::withMessages([
                'role' => __('Please enter a role name with at most :max characters.', ['max' => self::MAX_ROLE_LENGTH]),
            ]);
        }

        return $name;
    }

    /**
     * @param list<string> $roles
     */
    private function storeList(Project $project, Component $component, array $roles): void
    {
        $roles = array_values(array_filter($roles, fn (string $role) => !self::isArtistRoleName($role)));
        $value = ProjectComponentValue::query()->firstOrNew([
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
        $data = (array) ($value->data ?? []);
        $data['roles'] = $roles;
        $value->data = $data;
        $value->save();
    }

    private function value(Project $project, Component $component): ?ProjectComponentValue
    {
        return ProjectComponentValue::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProjectComponentCrmContact>
     */
    private function entriesWithRole(Project $project, Component $component, string $role)
    {
        return ProjectComponentCrmContact::query()
            ->where('project_id', $project->id)
            ->where('component_id', $component->id)
            ->whereJsonContains('roles', $role)
            ->get();
    }

    /**
     * Gleiche Rollen nur einmal (Groß-/Kleinschreibung egal, erste Schreibweise gewinnt).
     *
     * @param array<int, mixed> $roles
     * @return list<string>
     */
    private function unique(array $roles): array
    {
        $result = [];
        $seen = [];
        foreach ($roles as $role) {
            if (!is_scalar($role)) {
                continue;
            }
            $role = trim((string) $role);
            $key = mb_strtolower($role);
            if ($role === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $role;
        }

        return $result;
    }
}
