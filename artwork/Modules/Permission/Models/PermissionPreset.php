<?php

namespace Artwork\Modules\Permission\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property array $permissions Rechte-NAMEN (seit 02.09.2026; vorher IDs – Migration konvertiert, permissionNames() toleriert beides)
 */
class PermissionPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    /**
     * Rechte-Namen des Presets. Numerische Altbestände (Permission-IDs) werden aufgelöst.
     *
     * @return string[]
     */
    public function permissionNames(): array
    {
        $entries = $this->permissions ?? [];
        $ids = array_values(array_filter($entries, static fn ($entry): bool => is_int($entry) || ctype_digit((string) $entry)));
        $names = array_values(array_filter($entries, static fn ($entry): bool => is_string($entry) && !ctype_digit($entry)));

        if ($ids !== []) {
            $names = [...$names, ...Permission::query()->whereIn('id', $ids)->pluck('name')->all()];
        }

        return array_values(array_unique($names));
    }
}
