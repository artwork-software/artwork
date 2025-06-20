<?php

namespace Artwork\Modules\Checklist\Http\Requests;

use Artwork\Modules\Event\Http\Requests\EventStoreOrUpdateRequest;

class ChecklistUpdateRequest extends EventStoreOrUpdateRequest
{
    /**
     * @return string[][]
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'name' => ['sometimes', 'nullable', 'string'],
            'private' => ['boolean'],
            'tasks' => ['sometimes', 'array'],
            'tasks.*.name' => ['sometimes', 'nullable', 'string'],
            'tasks.*.description' => ['sometimes', 'nullable', 'string'],
            'tasks.*.done' => ['required', 'nullable', 'boolean'],
            'tasks.*.order' => ['required', 'nullable', 'int'],

            'assigned_department_ids' => [
                'sometimes',
                'array',
            ],
            'assigned_department_ids.*.*' => ['required', 'exists:departments,id'],
        ];
    }

    /**
     * Get the request data.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function data($key = null, $default = null)
    {
        $data = $this->only(['user_id', 'name']);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }
}
