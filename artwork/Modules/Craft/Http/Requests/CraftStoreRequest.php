<?php

namespace Artwork\Modules\Craft\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CraftStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'abbreviation' => 'required|string|min:1|max:3',
            'users' => 'array',
            'users_for_inventory' => 'array',
            'assignable_by_all' => 'required|boolean',
            'inventory_planned_by_all' => 'required|boolean',
            'universally_applicable' => 'required|boolean',
            'qualifications' => 'nullable|array',
            'qualifications.*.id' => 'integer|exists:shift_qualifications,id',
            'color' => 'nullable|string|max:7',
            'notify_days' => 'nullable|integer|min:0',
        ];
    }
}
