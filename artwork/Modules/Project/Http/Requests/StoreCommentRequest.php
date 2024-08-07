<?php

namespace Artwork\Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'text' => 'required|string|max:5000',
        ];
    }
}
