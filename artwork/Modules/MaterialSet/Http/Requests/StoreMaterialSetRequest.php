<?php

namespace Artwork\Modules\MaterialSet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialSetRequest extends FormRequest
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
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:inventory_articles,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}
