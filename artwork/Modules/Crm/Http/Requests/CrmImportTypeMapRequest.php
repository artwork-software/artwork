<?php

namespace Artwork\Modules\Crm\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class CrmImportTypeMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CRM_MANAGER->value);
    }

    public function rules(): array
    {
        return [
            'type_column_index' => ['required', 'integer', 'min:0'],
            'type_value_mapping' => ['required', 'array', 'min:1'],
            'type_value_mapping.*.type_value' => ['required', 'string'],
            'type_value_mapping.*.crm_contact_type_id' => ['nullable', 'integer', 'exists:crm_contact_types,id'],
        ];
    }
}
