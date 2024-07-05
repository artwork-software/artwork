<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCraftsInventoryColumnRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => ['required', 'array:id,value'],
            'type.*.id' => Rule::enum(CraftsInventoryColumnTypeEnum::class),
            'selectOptions' => [
                Rule::requiredIf(
                    $this->request->all()['type']['id'] === CraftsInventoryColumnTypeEnum::SELECT->value
                ),
                'array',
                'min:1'
            ],
            'selectOptions.*' => 'required|string',
            'defaultOption' => 'nullable|string'
        ];
    }
}
