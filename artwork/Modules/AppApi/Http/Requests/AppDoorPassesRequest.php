<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A card is looked up by holder or card code, or by the QR its holder shows. */
class AppDoorPassesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'prohibits:q', 'string', 'max:512'],
        ];
    }

    /** @return array{q: string}|array{payload: string} */
    public function lookup(): array
    {
        return $this->filled('payload')
            ? ['payload' => (string) $this->validated('payload')]
            : ['q' => (string) ($this->validated('q') ?? '')];
    }
}
