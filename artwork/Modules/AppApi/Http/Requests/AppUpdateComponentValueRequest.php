<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppUpdateComponentValueRequest extends FormRequest
{
    /**
     * Same contract as the web/external component write path: the payload is
     * the component type's own value object (e.g. {text}, {checked},
     * {selected}, {links}).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
        ];
    }
}
