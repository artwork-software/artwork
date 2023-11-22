<?php

namespace Artwork\Modules\Craft\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CraftStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

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
            'users' => 'required|array',
            'assignable_by_all' => 'required|boolean',
        ];
    }
}
