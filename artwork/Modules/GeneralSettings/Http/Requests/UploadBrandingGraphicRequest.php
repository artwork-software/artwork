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
        /*
         *     $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');

         */
        return [
        ];
    }
}
