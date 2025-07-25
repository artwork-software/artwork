<?php

namespace Artwork\Modules\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionPresetRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|unique:permission_presets',
            'permissions' => 'array'
        ];
    }
}
