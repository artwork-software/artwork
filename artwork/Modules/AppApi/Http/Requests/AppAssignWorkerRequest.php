<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\AppApi\Enums\WorkerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppAssignWorkerRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer'],
            'worker_type' => ['required', Rule::enum(WorkerType::class)],
            'shift_qualification_id' => ['required', 'integer', 'exists:shift_qualifications,id'],
            // Explicit confirmation that a fully staffed function may be overbooked.
            'overbook' => ['sometimes', 'boolean'],
        ];
    }

    public function workerType(): WorkerType
    {
        return WorkerType::from($this->validated('worker_type'));
    }
}
