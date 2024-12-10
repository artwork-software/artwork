<?php

namespace Artwork\Modules\Budget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableColumnOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tableColumnOrders' => 'required|array',
            'tableColumnOrders.*' => 'required|integer|exists:table_column_orders,id',
        ];
    }
}
