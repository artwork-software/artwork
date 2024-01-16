<?php

namespace Artwork\Modules\PermissionPresets\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        //authorized by web.php middleware
        return true;
    }

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
