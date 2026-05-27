<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Artwork\Modules\BusinessIntelligence\Enums\BiEffortBucketEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBiTimeEffortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'effort_bucket' => ['required', 'string', Rule::enum(BiEffortBucketEnum::class)],
        ];
    }
}
