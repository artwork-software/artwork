<?php

namespace Artwork\Modules\PermissionPresets\Http\Requests;

use Artwork\Modules\PermissionPresets\Models\PermissionPreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionPresetRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                //ignore given PermissionPreset-Model, should only determine if another preset has the same name
                Rule::unique('permission_presets')->ignore($this->route('permission_preset')->id)
            ],
            'permissions' => 'array'
        ];
    }
}
