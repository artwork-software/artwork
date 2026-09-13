<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBiEventTypeTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'name_de' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            // Rolle je Kennzahl nur einmal vergebbar (Vorstellungen / Veranstaltungstage)
            'kpi_role' => [
                'nullable',
                Rule::in(BiEventTypeTag::KPI_ROLES),
                Rule::unique('bi_event_type_tags', 'kpi_role'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'kpi_role.unique' => __('Another tag already controls this key figure.'),
        ];
    }
}
