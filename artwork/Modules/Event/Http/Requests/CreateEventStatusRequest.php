<?php

namespace Artwork\Modules\Event\Http\Requests;

use Artwork\Core\Validation\Rules\HexColorRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateEventStatusRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'color' => ['required', 'string', 'max:255', new HexColorRule()],
            'default' => 'required|boolean',
        ];
    }
}
