<?php

namespace Artwork\Modules\Event\Http\Requests;

use Dive\DryRequests\DryRunnable;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrUpdateEvent extends FormRequest
{
    use DryRunnable;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'start_time' => 'required|before:end_time',
            'end_time' => 'required',
            'name' => 'string|nullable',
            'description' => 'string|nullable',
        ];
    }
}
