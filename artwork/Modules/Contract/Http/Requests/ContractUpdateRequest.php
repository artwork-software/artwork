<?php

namespace Artwork\Modules\Contract\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->only([
            'contract_partner',
            'amount',
            'project_id',
            'description',
            'ksk_liable',
            'resident_abroad',
            'company_type_id',
            'contract_type_id',
            'has_power_of_attorney',
            'is_freed',
            'currency_id',
            'tasks',
            'accessibleUsers',
        ]);
    }
}
