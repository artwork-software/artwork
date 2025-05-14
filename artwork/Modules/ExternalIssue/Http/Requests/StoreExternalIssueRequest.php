<?php

namespace Artwork\Modules\ExternalIssue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'material_value' => 'required|numeric|min:0',
            'issued_by_id' => 'required|exists:users,id',
            'received_by_id' => 'nullable|exists:users,id',
            'issue_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:issue_date',
            'return_remarks' => 'nullable|string',
            'external_name' => 'required|string|max:255',
            'external_address' => 'nullable|string|max:255',
            'external_email' => 'nullable|email|max:255',
            'external_phone' => 'nullable|string|max:50',
            'files.*' => 'file|max:10240',
        ];
    }
}
