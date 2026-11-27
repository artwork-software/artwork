<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Shift\Enums\ShortNoticeReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Grund einer kurzfristigen Änderung. Die Berechtigung prüft die Route
 * (approve-shift-plan-requests, wie Bestätigen und Export der Änderungsübersicht).
 */
class UpdateShortNoticeReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', Rule::enum(ShortNoticeReason::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
