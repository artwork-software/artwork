<?php

namespace Artwork\Modules\Crm\Http\Requests;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
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
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:10240', new SafeUploadFile()],
            'use_type_column' => ['sometimes', 'boolean'],
            'crm_contact_type_id' => [
                'required_unless:use_type_column,true',
                'nullable',
                'integer',
                'exists:crm_contact_types,id',
            ],
        ];
    }
}
