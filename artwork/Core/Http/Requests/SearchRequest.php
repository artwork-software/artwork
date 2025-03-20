<?php

namespace Artwork\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'query' => 'required|string',
            'type' => 'nullable|string',
            'projectId' => 'nullable|integer',
        ];
    }
}
