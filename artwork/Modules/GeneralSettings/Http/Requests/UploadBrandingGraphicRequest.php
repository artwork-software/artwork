<?php

namespace Artwork\Modules\GeneralSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBrandingGraphicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /*
         *     $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');

         */
        return [
            'smallLogo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'bigLogo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }
}
