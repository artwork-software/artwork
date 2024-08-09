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
        // TODO: JONAS hier bitte die Regel für typeOptions überprüfen und anpassen
        // TODO: (Regel wurde vorher für jeden Type angewant wenn array und min:1 gesetzt war)
        return [
            'name' => 'required|string',
            'type' => ['required', 'array:id,value'],
            'type.*.id' => Rule::enum(CraftsInventoryColumnTypeEnum::class),
            'typeOptions' => [
                Rule::requiredIf(function () {
                    $typeId = $this->request->get('type.id');
                    return $typeId === CraftsInventoryColumnTypeEnum::SELECT->value;
                }),
                //'array',
                //'min:1'
            ],
            'typeOptions.*' => 'required|string',
            'defaultOption' => 'nullable|string'
        ];
    }
}
