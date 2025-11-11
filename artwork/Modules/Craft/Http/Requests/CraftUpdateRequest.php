<?php

namespace Artwork\Modules\Craft\Http\Requests;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CraftUpdateRequest extends FormRequest
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
            'managersToBeAssigned' => 'sometimes|array',
            'managersToBeAssigned.*' => 'array',
            'managersToBeAssigned.*.manager_id' => 'required|integer',
            'managersToBeAssigned.*.manager_type' => Rule::in(
                User::class,
                Freelancer::class,
                ServiceProvider::class
            ),
            'qualifications' => 'sometimes|array',
            'qualifications.*.id' => 'integer|exists:shift_qualifications,id',
        ];
    }
}
