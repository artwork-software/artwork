<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Models\Contract;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\DTOs\BudgetInformationDto;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectTabBudgetInformationService
{
    /**
     * Was BudgetInformations, ProjectFileEditModal, ContractEditModal und die Upload-Modals von Freigegebenen bzw.
     * Personen mit Budgetzugriff lesen (Avatar, Name, Id). Mehr geht nicht raus – volle User-Modelle trügen
     * Arbeitszeitkonto, Login-Kennungen usw. mit.
     */
    private const SHARED_USER_FIELDS = ['id', 'first_name', 'last_name', 'profile_photo_url'];

    /**
     * Spalten für SHARED_USER_FIELDS; work_name/email nur für den Initialen-Avatar (nicht ausgeliefert).
     */
    private const SHARED_USER_COLUMNS = [
        'users.id',
        'users.first_name',
        'users.last_name',
        'users.profile_photo_path',
        'users.work_name',
        'users.email',
    ];

    public function __construct(
        private readonly ContractTypeService $contractTypeService,
        private readonly CompanyTypeService $companyTypeService,
        private readonly CurrencyService $currencyService,
    ) {
    }

    public function buildBudgetInformationPayload(Project $project, User $user): array
    {
        $dto = BudgetInformationDto::newInstance()
            ->setAccessBudget($this->slimAccessBudget($project))
            ->setContracts($this->visibleContracts($project, $user))
            ->setProjectFiles($this->visibleProjectFiles($project, $user))
            ->setProjectMoneySources($this->visibleMoneySources($project, $user))
            ->setProjectManagerIds($project->managerUsers->pluck('id'))
            ->setContractTypes($this->contractTypeService->getAll())
            ->setCompanyTypes($this->companyTypeService->getAll())
            ->setCurrencies($this->currencyService->getAll())
            ->setCostCenter($project->costCenter);

        return [
            'BudgetInformation' => $dto->toArray(),
        ];
    }

    /**
     * Spiegel von BudgetInformations.vue: Dokumente-Bereich nur mit globalen Budgetrechten,
     * Budgetzugriff im Projekt oder als Projektleitung; darin nur ausdrücklich freigegebene Dateien.
     */
    private function visibleProjectFiles(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value) &&
            !$this->hasBudgetAccess($project, $user) &&
            !$project->managerUsers->contains('id', $user->id)
        ) {
            return new Collection();
        }

        // Freigabeliste immer mitliefern (accessing_users): das Bearbeiten-Modal belegt sie damit vor
        if ($this->isAdmin($user)) {
            return $this->slimSharedUsers(
                $project->project_files()->with(['accessingUsers' => $this->sharedUserColumns()])->get()
            );
        }

        // Relationen für ProjectFilePolicy::view einmal laden statt je Datei (Projekt, Team, Budget-Rechte).
        // Das geteilte Projekt wird danach wieder abgehängt, damit es nicht je Datei mitserialisiert wird.
        $project->loadMissing(['users', 'managerUsers', 'access_budget']);

        return $this->slimSharedUsers(
            $project->project_files()
                ->whereHas('accessingUsers', fn ($query) => $query->whereKey($user->id))
                ->with(['accessingUsers' => $this->sharedUserColumns()])
                ->get()
                ->each(fn (ProjectFile $projectFile) => $projectFile->setRelation('project', $project))
                ->filter(fn (ProjectFile $projectFile) => $user->can('view', $projectFile))
                ->each(fn (ProjectFile $projectFile) => $projectFile->unsetRelation('project'))
                ->values()
        );
    }

    /**
     * Verträge-Bereich nur mit Vertragsrecht oder Budgetzugriff; darin nur Verträge, die die Person
     * laut ContractPolicy öffnen darf.
     */
    private function visibleContracts(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::CONTRACT_EDIT_UPLOAD->value) &&
            !$this->hasBudgetAccess($project, $user)
        ) {
            return new Collection();
        }

        // Freigabelisten und Stammdaten immer mitliefern (accessing_users/-departments, currency, company_type,
        // contract_type): ContractEditModal belegt sie damit vor und sendet sie beim Speichern vollständig zurück.
        // Für ContractPolicy (Projektleitung) das Projekt einmal laden statt je Vertrag; danach wieder abhängen,
        // damit es nicht je Vertrag mitserialisiert wird.
        $project->loadMissing('managerUsers');

        $contracts = $project->contracts
            ->loadMissing([
                'accessingUsers' => $this->sharedUserColumns(),
                'accessingDepartments',
                'currency',
                'company_type',
                'contract_type',
            ])
            ->each(fn (Contract $contract) => $contract->setRelation('project', $project));
        $visibleContracts = $contracts
            ->filter(fn (Contract $contract) => $user->can('view', $contract))
            ->values();
        $contracts->each(fn (Contract $contract) => $contract->unsetRelation('project'));

        return $this->slimSharedUsers($visibleContracts);
    }

    private function visibleMoneySources(Project $project, User $user): Collection
    {
        if (
            !$user->can(PermissionEnum::MONEY_SOURCE_EDIT_VIEW_ADD->value) &&
            !$this->hasBudgetAccess($project, $user)
        ) {
            return new Collection();
        }

        return $project->moneySources;
    }

    private function hasBudgetAccess(Project $project, User $user): bool
    {
        return $project->access_budget->contains('id', $user->id);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value);
    }

    /**
     * Eager-Load-Einschränkung: nur die Spalten für SHARED_USER_FIELDS laden.
     *
     * @return \Closure(BelongsToMany<User, Model>): void
     */
    private function sharedUserColumns(): \Closure
    {
        return function ($query): void {
            $query->select(self::SHARED_USER_COLUMNS);
        };
    }

    /**
     * Freigegebene nur mit SHARED_USER_FIELDS serialisieren (auch ohne Pivot und weitere Accessoren).
     *
     * @template TModel of ProjectFile|Contract
     * @param Collection<int, TModel> $models
     * @return Collection<int, TModel>
     */
    private function slimSharedUsers(Collection $models): Collection
    {
        return $models->each(function (ProjectFile|Contract $model): void {
            $model->accessingUsers->each(fn (User $sharedUser) => $sharedUser->setVisible(self::SHARED_USER_FIELDS));
        });
    }

    /**
     * Personen mit Budgetzugriff schlank ausliefern (Upload-Modals filtern ihre Personensuche nur nach der Id,
     * BudgetInformations prüft den eigenen Budgetzugriff per Id). Kopien, damit die am Projekt geladene Relation
     * für die Rechteprüfungen unverändert bleibt.
     *
     * @return Collection<int, User>
     */
    private function slimAccessBudget(Project $project): Collection
    {
        return new Collection($project->access_budget->map(
            fn (User $budgetUser): User => (clone $budgetUser)->setVisible(self::SHARED_USER_FIELDS)
        )->all());
    }
}
