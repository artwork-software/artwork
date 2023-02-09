<?php

namespace App\Http\Requests;

use App\Enums\PermissionNameEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChecklistUpdateRequest extends EventStoreOrUpdateRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'name' => ['sometimes', 'nullable', 'string'],

            'tasks' => ['sometimes', 'array'],
            'tasks.*.name' => ['sometimes', 'nullable', 'string'],
            'tasks.*.description' => ['sometimes', 'nullable', 'string'],
            'tasks.*.done' => ['required', 'nullable', 'boolean'],
            'tasks.*.order' => ['required', 'nullable', 'int'],

            'assigned_department_ids' => [
                'sometimes',
                'array',
                Rule::prohibitedIf(Auth::user()->canNot(PermissionNameEnum::DEPARTMENT_UPDATE->value))
            ],
            'assigned_department_ids.*.*' => ['required', 'exists:departments,id'],
        ];
    }

    public function data(): array
    {
        return $this->only(['user_id', 'name']);
    }
}
