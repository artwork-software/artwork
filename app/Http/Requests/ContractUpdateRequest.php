<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function data(): array
    {
        return $this->only([
            'contract_partner',
            'amount',
            'project_id',
            'description',
            'ksk_liable',
            'resident_abroad',
            'legal_form',
            'type',
        ]);
    }
}
