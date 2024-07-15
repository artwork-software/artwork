<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCraftsInventoryColumnBackgroundColorRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['background_color' => 'string'];
    }
}
