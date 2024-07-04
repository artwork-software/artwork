<?php

namespace Artwork\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUpload extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['max:2000'],
            'tabId' => ['integer', 'nullable'],
        ];
    }
}
