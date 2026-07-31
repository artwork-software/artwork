<?php

namespace Artwork\Modules\Crm\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class CrmImportExecuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CRM_MANAGER->value);
    }

    public function rules(): array
    {
        return [
            'mapping' => ['required_without:type_mappings', 'nullable', 'array'],
            'mapping.display_name' => ['nullable', 'integer'],
            'mapping.properties' => ['nullable', 'array'],
            'mapping.properties.*' => ['integer'],

            'type_mappings' => ['required_without:mapping', 'nullable', 'array'],
            'type_mappings.*.crm_contact_type_id' => ['required', 'integer'],
            'type_mappings.*.display_name' => ['nullable', 'integer'],
            'type_mappings.*.properties' => ['nullable', 'array'],
            'type_mappings.*.properties.*' => ['integer'],

            'duplicates' => ['nullable', 'array'],
            'duplicates.enabled' => ['boolean'],
            'duplicates.match_by' => ['nullable', 'string', 'regex:/^(display_name|prop_\d+)$/'],
            'duplicates.action' => ['nullable', 'in:skip,update'],
        ];
    }
}
