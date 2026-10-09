<?php

namespace Artwork\Modules\Contract\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Http\Resources\CommentResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Contract\Models\Contract
 */
class ContractResource extends JsonResource
{

    /**
     * Echte Freigaben (Pivot contract_user). Nur diese Liste belegt das Bearbeiten-Modal vor und wird beim
     * Speichern zurückgeschickt.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getAccessibleUsers(): \Illuminate\Support\Collection
    {
        return collect($this->accessingUsers->all());
    }

    /**
     * Nur zur Anzeige: Freigaben plus Projektleitungen, die laut ContractPolicy ohnehin Zugriff haben.
     * Würde diese Liste gespeichert, stünden die Projektleitungen danach dauerhaft in der Freigabe.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getDisplayedAccessUsers(): \Illuminate\Support\Collection
    {
        $usersWithAccess = $this->getAccessibleUsers();
        $project = $this->project_id !== null
            ? Project::where('id', $this->project_id)->with(['users'])->first()
            : null;

        foreach ($project?->users ?? [] as $user) {
            if ($user->pivot->is_manager && !$usersWithAccess->contains('id', $user->id)) {
                $usersWithAccess->push($user);
            }
        }

        return $usersWithAccess;
    }

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creator' => $this->creator,
            'basename' => $this->basename,
            'project' => $this->project,
            'amount' => $this->amount,
            'contract_type' => $this->contract_type,
            'company_type' => $this->company_type,
            'ksk_liable' => $this->ksk_liable,
            'ksk_amount' => $this->ksk_amount,
            'ksk_reason' => $this->ksk_reason,
            'partner' => $this->contract_partner,
            'resident_abroad' => $this->resident_abroad,
            'foreign_tax' => $this->foreign_tax,
            'foreign_tax_amount' => $this->foreign_tax_amount,
            'foreign_tax_city' => $this->foreign_tax_city,
            'foreign_tax_country' => $this->foreign_tax_country,
            'foreign_tax_reason' => $this->foreign_tax_reason,
            'reverse_charge_amount' => $this->reverse_charge_amount,
            // Kalenderdatum (Y-m-d) – ein Carbon-Objekt würde als UTC-Zeitpunkt des Vortags serialisiert
            'deadline_date' => $this->deadline_date?->format('Y-m-d'),
            'has_power_of_attorney' => $this->has_power_of_attorney,
            'currency' => $this->currency,
            'is_freed' => $this->is_freed,
            'description' => $this->description,
            'contract_state' => $this->contract_state,
            'contract_state_comment' => $this->contract_state_comment,
            // Gespeicherte Freigaben – Grundlage für das Bearbeiten-Modal
            'accessibleUsers' => $this->getAccessibleUsers()->map(
                fn (User $user): array => $this->userArray($user, $request)
            ),
            // Anzeige in der Vertragsübersicht (inkl. Projektleitungen), wird nie gespeichert
            'displayedAccessUsers' => $this->getDisplayedAccessUsers()->map(
                fn (User $user): array => $this->userArray($user, $request)
            ),
            //'accessibleUsers' => UserIndexResource::collection($this->getAccessibleUsers())->resolve(),
            'accessibleDepartments' => $this->accessingDepartments->map(fn ($department) => [
                'id' => $department->id,
                'name' => $department->name,
                'svg_name' => $department->svg_name,
            ]),
            'tasks' => Task::where('contract_id', $this->id)->get(),
            'comments' => CommentResource::collection($this->comments)->resolve()
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userArray(User $user, mixed $request): array
    {
        return [
            'resource' => class_basename($user),
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo_url' => $user->profile_photo_url,
            'email' => $user->visibleEmailFor($request->user()),
            'departments' => $user->departments,
            'position' => $user->position,
            'business' => $user->business,
            'phone_number' => $user->visiblePhoneNumberFor($request->user()),
            'project_management' => $user->can(PermissionEnum::PROJECT_MANAGEMENT->value),
            'display_name' => $user->getDisplayNameAttribute(),
            'type' => $user->getTypeAttribute(),
            'assigned_craft_ids' => $user->getAssignedCraftIdsAttribute(),
        ];
    }
}
