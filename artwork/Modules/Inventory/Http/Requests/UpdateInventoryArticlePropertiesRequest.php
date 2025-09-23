<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryArticlePropertiesRequest extends FormRequest
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
            'id' => ['required', 'exists:inventory_article_properties,id'],
            'name' => ['string', 'max:255', 'required'],
            'tooltip_text' => ['string', 'nullable', 'max:255'],
            'type' => ['string', 'max:255', 'nullable'],
            'is_filterable' => ['boolean', 'required'],
            'show_in_list' => ['boolean', 'required'],
            'is_required' => ['boolean', 'required'],
            'select_values' => ['array', 'nullable'],
            'across_articles' => ['boolean', 'required'],
            'individual_value' => ['boolean', 'required'],
        ];
    }
}
