<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class BiExportCacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::BI_EXPORT->value)
            || $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
            // Bei reinem Termin-Export gibt es keine Projektspalten-Auswahl
            'columns' => ['required_unless:granularity,events', 'array'],
            'columns.*' => ['string'],
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
}
