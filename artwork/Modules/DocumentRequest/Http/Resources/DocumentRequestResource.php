<?php

namespace Artwork\Modules\DocumentRequest\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'requester' => $this->requester ? [
                'id' => $this->requester->id,
                'first_name' => $this->requester->first_name,
                'last_name' => $this->requester->last_name,
                'profile_photo_url' => $this->requester->profile_photo_url,
                'email' => $this->requester->email,
            ] : null,
            'requested' => $this->requested ? [
                'id' => $this->requested->id,
                'first_name' => $this->requested->first_name,
                'last_name' => $this->requested->last_name,
                'profile_photo_url' => $this->requested->profile_photo_url,
                'email' => $this->requested->email,
            ] : null,
            'project' => $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null,
            'contract' => $this->contract ? [
                'id' => $this->contract->id,
                'name' => $this->contract->name,
                'basename' => $this->contract->basename,
            ] : null,
            'status' => $this->status,
            'contract_partner' => $this->contract_partner,
            'contract_value' => $this->contract_value,
            'amount' => $this->contract_value,
            'contract_type_id' => $this->contract_type_id,
            'company_type_id' => $this->company_type_id,
            'ksk_liable' => $this->ksk_liable,
            'ksk_amount' => $this->ksk_amount,
            'ksk_reason' => $this->ksk_reason,
            'foreign_tax' => $this->foreign_tax,
            'foreign_tax_amount' => $this->foreign_tax_amount,
            'foreign_tax_reason' => $this->foreign_tax_reason,
            'reverse_charge_amount' => $this->reverse_charge_amount,
            'deadline_date' => $this->deadline_date?->format('Y-m-d'),
            'contract_type' => $this->contractType,
            'company_type' => $this->companyType,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
