<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\ItemEvent;

use Illuminate\Foundation\Http\FormRequest;

class DropItemOnInventoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer',
        ];
    }
}
