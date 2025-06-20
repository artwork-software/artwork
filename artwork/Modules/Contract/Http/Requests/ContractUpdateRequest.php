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
     * Retrieve data from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function data($key = null, $default = null)
    {
        $contractData = $this->only([
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

        if ($key === null) {
            return $contractData;
        }

        return $contractData[$key] ?? $default;
    }
}
