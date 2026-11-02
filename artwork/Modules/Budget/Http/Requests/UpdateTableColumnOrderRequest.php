<?php

namespace Artwork\Modules\Budget\Http\Requests;

use Artwork\Modules\SageApiSettings\Models\SageApiSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTableColumnOrderRequest extends FormRequest
{
    /**
     * Systemweite Sage-Einstellung (Spaltenreihenfolge aller Budgettabellen) – nicht über Budgetzugriff
     * auf ein einzelnes Projekt.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('updateInterfaceSettings', SageApiSettings::class) ?? false;
    }

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
