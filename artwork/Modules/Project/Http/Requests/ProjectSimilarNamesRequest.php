<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Namensprüfung bei der Projekt- und Projektgruppenanlage. Rechteprüfung vor der Validierung,
 * damit Personen ohne Anlagerecht ein 403 statt Validierungsfehlern bekommen.
 */
class ProjectSimilarNamesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Project::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
