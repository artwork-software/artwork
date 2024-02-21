<?php

namespace Artwork\Modules\Budget\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSageAssignedDataCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        //handled by policy/authorizeResource: SageAssignedDataCommentPolicy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'userId' => 'int|exists:users,id',
            'sageAssignedDataId' => 'int|exists:sage_assigned_data,id',
            'comment' => 'string'
        ];
    }
}
