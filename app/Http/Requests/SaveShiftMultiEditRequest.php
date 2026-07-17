<?php

namespace App\Http\Requests;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveShiftMultiEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'userType' => ['required', 'integer', Rule::in([0, 1, 2])],
            'userTypeId' => ['required', 'integer', 'min:1'],
            'craft_abbreviation' => ['nullable', 'string', 'max:255'],
            'shiftsToHandle' => ['sometimes', 'array:assignToShift,removeFromShift'],
            'shiftsToHandle.assignToShift' => ['sometimes', 'array', 'max:500'],
            'shiftsToHandle.assignToShift.*' => ['required', 'array:shiftId,shiftQualificationId,isOverbooked'],
            'shiftsToHandle.assignToShift.*.shiftId' => ['required', 'integer', 'distinct', 'exists:shifts,id'],
            'shiftsToHandle.assignToShift.*.shiftQualificationId' => [
                'nullable',
                'integer',
                'exists:shift_qualifications,id',
            ],
            'shiftsToHandle.assignToShift.*.isOverbooked' => ['sometimes', 'boolean'],
            'shiftsToHandle.removeFromShift' => ['sometimes', 'array', 'max:500'],
            'shiftsToHandle.removeFromShift.*' => ['required', 'integer', 'distinct', 'exists:shifts,id'],
            'fullPeriodProjectAssignments' => ['sometimes', 'array', 'max:20'],
            'fullPeriodProjectAssignments.*' => ['required', 'integer', 'distinct', 'exists:projects,id'],
        ];
    }

    /**
     * @return array<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['userType', 'userTypeId'])) {
                return;
            }

            $employableType = match ($this->integer('userType')) {
                1 => Freelancer::class,
                2 => ServiceProvider::class,
                default => User::class,
            };

            if (!$employableType::query()->whereKey($this->integer('userTypeId'))->exists()) {
                $validator->errors()->add('userTypeId', __('The selected worker does not exist.'));
            }
        }];
    }
}
