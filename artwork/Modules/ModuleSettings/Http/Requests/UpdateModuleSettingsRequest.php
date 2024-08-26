<?php

namespace Artwork\Modules\ModuleSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleSettingsRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'menu' => 'required|string|exists:settings,name',
            'enabled' => 'required|boolean'
        ];
    }
}
