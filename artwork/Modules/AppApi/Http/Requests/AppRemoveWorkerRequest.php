<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\AppApi\Enums\WorkerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppRemoveWorkerRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'worker_id' => ['required', 'integer'],
            'worker_type' => ['required', Rule::enum(WorkerType::class)],
        ];
    }

    public function workerType(): WorkerType
    {
        return WorkerType::from($this->validated('worker_type'));
    }
}
