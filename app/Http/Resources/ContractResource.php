<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'project' => $this->project,
            'amount' => $this->amount,
            'legal_form' => $this->legal_form,
            'type' => $this->type,
            'ksk_liable' => $this->ksk_liable,
            'partner' => $this->contract_partner,
            'resident_abroad' => $this->resident_abroad,
            'description' => $this->description
        ];
    }
}
