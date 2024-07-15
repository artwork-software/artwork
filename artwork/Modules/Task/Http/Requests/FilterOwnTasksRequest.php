<?php

namespace Artwork\Modules\Task\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterOwnTasksRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => 'nullable|integer',
        ];
    }
}
