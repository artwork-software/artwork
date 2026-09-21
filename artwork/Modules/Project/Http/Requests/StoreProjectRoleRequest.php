<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sicherheits-Audit 21.09.2026 (E, NIEDRIG): Projektrollen wurden ohne Validierung angelegt
 * (500 bei fehlendem/überlangem Namen). Rechteprüfung liegt auf der Resource-Route.
 */
class StoreProjectRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
