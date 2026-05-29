<?php

namespace Artwork\Modules\ExternalAccess\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendCrmAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // fine-grained authorization happens in the controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['expires_at' => ['required', 'date', 'after:now']];
    }
}
