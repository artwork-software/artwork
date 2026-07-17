<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftPlanRequestRequest extends FormRequest
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
            'week_number' => ['required', 'integer', 'min:1', 'max:53'],
            'year' => ['required', 'integer'],
            // Entweder ein einzelnes Gewerk (Altbestand) oder eine Mehrfachauswahl —
            // die Anfragen werden im Controller weiterhin pro Gewerk getrennt angelegt.
            'craft_id' => ['required_without:craft_ids', 'integer', 'exists:crafts,id'],
            'craft_ids' => ['required_without:craft_id', 'array', 'min:1'],
            'craft_ids.*' => ['integer', 'distinct', 'exists:crafts,id'],
        ];
    }
}
