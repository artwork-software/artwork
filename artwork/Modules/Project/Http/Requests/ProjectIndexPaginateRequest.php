<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Enum\ProjectSortEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectIndexPaginateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string',
            'sort' => ['sometimes', Rule::enum(ProjectSortEnum::class)],
            'project_state_ids' => 'sometimes|array',
            'project_state_ids.*' => 'exists:project_states,id',
            'project_filters' => 'sometimes|array',
            'project_filters.*' => 'boolean',
            'saveFilterAndSort' => ['sometimes', 'boolean'],
            // wird am Konto gespeichert: nur die Werte des Paginators (1–100), sonst ließ sich per URL ein
            // riesiges Limit dauerhaft setzen
            'entitiesPerPage' => ['sometimes', 'nullable', 'integer', 'between:1,100'],
        ];
    }
}
