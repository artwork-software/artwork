<?php

namespace Artwork\Modules\User\Http\Requests;

use Artwork\Modules\User\Enums\MemberSortEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MembersManagementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string',
            'sort' => ['sometimes', Rule::enum(MemberSortEnum::class)],
            'saveFilterAndSort' => ['sometimes', 'boolean']
        ];
    }
}
