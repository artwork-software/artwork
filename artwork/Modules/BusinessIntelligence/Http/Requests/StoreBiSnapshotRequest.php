<?php

namespace Artwork\Modules\BusinessIntelligence\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBiSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'snapshot_date' => ['required', 'date'],
        ];
    }
}
