<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sicherheits-Audit 21.09.2026 (E, NIEDRIG): Komponenten-Einstellungen (type/permission_type/data,
 * Nutzer-/Abteilungs-Pivots) kamen ungeprüft in die Tabelle. Rechteprüfung liegt auf der
 * Routengruppe (can:change project settings).
 */
class StoreComponentRequest extends FormRequest
{
    public const PERMISSION_TYPES = ['allSeeAndEdit', 'allSeeSomeEdit', 'someSeeSomeEdit'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ProjectTabComponentEnum::class)],
            'data' => ['nullable', 'array'],
            'permission_type' => ['nullable', 'string', Rule::in(self::PERMISSION_TYPES)],
            'users' => ['nullable', 'array'],
            'users.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'users.*.can_write' => ['nullable', 'boolean'],
            'departments' => ['nullable', 'array'],
            'departments.*.department_id' => ['required', 'integer', 'exists:departments,id'],
            'departments.*.can_write' => ['nullable', 'boolean'],
        ];
    }
}
