<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppDiscoverRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email_hash' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/'],
        ];
    }
}
