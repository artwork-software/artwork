<?php

namespace Artwork\Modules\Crm\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class CrmExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CRM_MANAGER->value);
    }

    public function rules(): array
    {
        return [
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
            'contact_type_ids' => ['nullable', 'array'],
            'contact_type_ids.*' => ['integer', 'exists:crm_contact_types,id'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
