<?php

namespace Artwork\Modules\Craft\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CraftUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'abbreviation' => 'required|string|min:1|max:3',
            'users' => 'array',
            'assignable_by_all' => 'required|boolean',
        ];
    }
}
