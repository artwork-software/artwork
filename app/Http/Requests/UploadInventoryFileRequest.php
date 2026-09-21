<?php

namespace App\Http\Requests;

use Artwork\Core\FileHandling\Upload\SafeUploadFile;
use Illuminate\Foundation\Http\FormRequest;

class UploadInventoryFileRequest extends FormRequest
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
        return [
            'file' => ['required', 'max:2097152', new SafeUploadFile()]
        ];
    }
}
