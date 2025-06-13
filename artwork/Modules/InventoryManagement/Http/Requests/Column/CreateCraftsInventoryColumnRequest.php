<?php

namespace Artwork\Modules\InventoryManagement\Http\Requests\Column;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCraftsInventoryColumnRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        //parenthesis is important here!
        $createsSelectColumn = (
            ($this->request->all()['type']['id'] ?? null) === CraftsInventoryColumnTypeEnum::SELECT->value
        );

        return [
            'name' => 'required|string',
            'type' => ['required', 'array:id,value'],
            'type.*.id' => Rule::enum(CraftsInventoryColumnTypeEnum::class),
            'typeOptions' => [
                'array',
                Rule::requiredIf($createsSelectColumn),
                Rule::when($createsSelectColumn, ['min:1']),
                Rule::when(!$createsSelectColumn, ['min:0'])
            ],
            'typeOptions.*' => 'required|string',
            'defaultOption' => 'nullable|string'
        ];
    }
}
