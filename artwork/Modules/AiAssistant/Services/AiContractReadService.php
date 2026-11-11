<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiContractData;
use Artwork\Modules\AiAssistant\DTOs\AiContractListData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vertraege mit Deadline (ai-scenarios-4 S15).
 *
 * Recht: „Vertragsbausteine sehen und herunterladen" oder Admin, sonst 403 `no_contract_access`.
 * Menge wie die Vertragsuebersicht der Oberflaeche (`ContractController::index()`): Admins alle,
 * sonst eigene (Ersteller:in), direkt freigegebene, ueber die Abteilung freigegebene — zusaetzlich
 * die der Projektleitung (`ContractPolicy::hasAccess()`). Haengt ein Vertrag an einem Projekt, das
 * die Person nicht sehen darf, bleibt der Vertrag (wie in der Vertragsuebersicht), das Projekt
 * erscheint aber als `null`; der Filter `project_id` greift nur fuer sichtbare Projekte.
 * Sortiert nach Deadline (ohne Deadline zuletzt), dann Name.
 */
class AiContractReadService
{
    public function list(
        User $user,
        ?string $dueBefore,
        ?int $projectId,
        ?string $search,
        ?int $limit
    ): AiContractListData {
        $admin = $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);

        if (!$admin && !$user->checkPermissionTo(PermissionEnum::CONTRACT_SEE_DOWNLOAD->value, 'web')) {
            AiErrors::abort(AiErrors::NO_CONTRACT_ACCESS);
        }

        $max = (int) config('ai.list_limit');
        $limit = $limit === null ? $max : min(max(1, $limit), $max);
        $departmentIds = $user->departments()->pluck('departments.id')->all();

        $query = Contract::query()
            ->when(!$admin, static fn (Builder $builder) => $builder->where(
                static function (Builder $access) use ($user, $departmentIds): void {
                    $access->where('creator_id', $user->id)
                        ->orWhereHas(
                            'accessingUsers',
                            static fn (Builder $users) => $users->where('user_id', $user->id)
                        )
                        ->orWhereHas(
                            'accessingDepartments',
                            static fn (Builder $departments) => $departments->whereIn('department_id', $departmentIds)
                        )
                        ->orWhereHas(
                            'project.managerUsers',
                            static fn (Builder $managers) => $managers->where('users.id', $user->id)
                        );
                }
            ))
            ->when($dueBefore !== null, static fn (Builder $builder) => $builder->whereNotNull('deadline_date')
                ->whereDate('deadline_date', '<=', (string) $dueBefore))
            ->when($projectId !== null, static fn (Builder $builder) => $builder->where('project_id', $projectId)
                ->whereIn('project_id', Project::query()->visibleFor($user)->select('projects.id')))
            ->when($search !== null, fn (Builder $builder) => $builder->where(
                function (Builder $sub) use ($search): void {
                    $pattern = $this->contains((string) $search);
                    $sub->where('name', 'like', $pattern)->orWhere('contract_partner', 'like', $pattern);
                }
            ));

        $total = (clone $query)->count();
        $today = CarbonImmutable::today()->toDateString();

        $contracts = $query->with(['project', 'currency'])
            ->orderByRaw('CASE WHEN deadline_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline_date')
            ->orderBy('name')
            ->limit($limit)
            ->get();
        $visibleProjects = array_flip(Project::query()->visibleFor($user)
            ->whereIn('id', $contracts->pluck('project_id')->filter()->unique()->all())
            ->pluck('id')
            ->all());

        return new AiContractListData(
            data: $contracts->map(static function (Contract $contract) use ($today, $visibleProjects): AiContractData {
                $deadline = $contract->deadline_date?->toDateString();

                return new AiContractData(
                    id: (int) $contract->id,
                    name: AiText::truncate($contract->name),
                    contract_state: AiText::truncate($contract->contract_state),
                    deadline: $deadline,
                    overdue: $deadline !== null && $deadline < $today,
                    amount: $contract->amount === null ? null : number_format((float) $contract->amount, 2, '.', ''),
                    currency: AiText::truncate($contract->currency?->name),
                    project: isset($visibleProjects[$contract->project_id])
                        ? AiRefData::make($contract->project_id, $contract->project?->name)
                        : null,
                );
            })->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    private function contains(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }
}
