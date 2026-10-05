<?php

namespace Artwork\Modules\GeneralSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBrandingGraphicRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // public-Disk: nur Rasterbilder, kein SVG/HTML.
        $imageRule = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

        return [
            'smallLogo' => $imageRule,
            'bigLogo' => $imageRule,
            'banner' => $imageRule,
        ];
    }
}
