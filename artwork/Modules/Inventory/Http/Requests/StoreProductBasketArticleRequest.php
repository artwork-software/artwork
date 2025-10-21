<?php

namespace Artwork\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductBasketArticleRequest extends FormRequest
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
            'article_id' => ['required', 'integer', 'exists:inventory_articles,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
