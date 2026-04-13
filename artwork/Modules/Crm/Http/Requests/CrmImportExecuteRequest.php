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
            'mapping' => ['required', 'array'],
            'mapping.display_name' => ['required', 'integer'],
            'mapping.properties' => ['nullable', 'array'],
            'mapping.properties.*' => ['integer'],
        ];
    }
}
