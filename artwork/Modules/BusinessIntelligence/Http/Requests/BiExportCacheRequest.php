<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BiExportCacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::BI_EXPORT->value)
            || $this->user()->hasRole(\Artwork\Modules\Role\Enums\RoleEnum::ARTWORK_ADMIN->value);
    }

    public function rules(): array
    {
        return [
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
            // Bei reinem Termin-Export gibt es keine Projektspalten-Auswahl
            'columns' => ['required_unless:granularity,events', 'array'],
            // Nur Katalog-Spalten: unbekannte Schlüssel würden sonst als leere Spalte landen
            'columns.*' => ['string', Rule::in(BiExportService::allowedColumnKeys())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'granularity' => ['nullable', 'in:projects,events,both'],
            'event_tag_filter' => ['nullable', 'array'],
            'event_tag_filter.*' => [
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value === 'untagged') {
                        return;
                    }

                    if (!is_numeric($value) || !BiEventTypeTag::whereKey((int) $value)->exists()) {
                        $fail(__('Invalid BI tag filter value.'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'columns.*.in' => __('Unknown export column: :input'),
        ];
    }
}
