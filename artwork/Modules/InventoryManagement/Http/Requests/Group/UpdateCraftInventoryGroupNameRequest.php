<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftInventoryGroupNameRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}
