<?php

namespace Artwork\Modules\InventoryScheduling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DropItemOnInventoryRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'required|integer',
        ];
    }
}
