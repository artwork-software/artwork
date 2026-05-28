<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

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
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
