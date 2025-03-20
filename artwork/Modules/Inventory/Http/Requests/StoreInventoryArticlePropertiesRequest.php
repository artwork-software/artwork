<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryArticlePropertiesRequest extends FormRequest
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
            'name' => ['string', 'max:255', 'required'],
            'tooltip_text' => ['string', 'nullable', 'max:255'],
            'type' => ['string', 'max:255', 'required'],
            'is_filterable' => ['boolean', 'required'],
            'show_in_list' => ['boolean', 'required'],
        ];
    }
}
