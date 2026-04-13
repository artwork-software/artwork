<?php

namespace Artwork\Modules\Crm\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class CrmImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::CRM_MANAGER->value);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:10240'],
            'crm_contact_type_id' => ['required', 'integer', 'exists:crm_contact_types,id'],
        ];
    }
}
