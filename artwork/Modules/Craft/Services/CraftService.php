<?php

namespace Artwork\Modules\Craft\Services;

use Artwork\Modules\Craft\Http\Requests\CraftStoreRequest;
use Artwork\Modules\Craft\Http\Requests\CraftUpdateRequest;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Repositories\CraftRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;

class CraftService
{
    public function __construct(
        private readonly CraftRepository $craftRepository
    ) {
    }

    public function getAll(array $with = []): Collection
    {
        return $this->craftRepository->getAll($with);
    }

    /**
     * Gewerke mit den zuweisbaren Personen für Projekt-Schichten-Tab und
     * Schichtplan-Listenansicht (Auswahlliste/Drag&Drop in SingleShiftInDailyShiftView,
     * ShiftsQualificationsDropElement, ShiftBookedElementComponent, DragElement).
     *
     * Personen tragen nur die dort gelesenen Felder: das volle User-Modell (88 Felder
     * inkl. Settings) machte die crafts-Prop lokal 2,3 MB groß (14 Gewerke × 153 User
     * × 6 KB), und Freelancer/Dienstleister schoben über `assigned_craft_ids` ($appends)
     * je Person eine craftables-Query nach.
     */
    /**
     * Planer:innen eines Gewerks (craft_users) in der Form, die das Frontend liest:
     * SingleEntityInShift/SingleShiftInShiftOverviewUser prüfen `craft_shift_planer[].id`
     * (Personen-Menü an der Schicht), RequestWorkTimeChangeModal zeigt Name, Position,
     * Firma und Avatar (UserPopoverTooltip).
     */
    public const PLANER_VISIBLE = [
        'id',
        'first_name',
        'last_name',
        'full_name',
        'position',
        'business',
        'profile_photo_url',
        'type',
    ];

    /**
     * Gewerke als Lookup (Karten/Modale im Einsatzplan): nur Stammfelder plus schlanke
     * Planer:innen — Craft::all() lieferte die Planer:innen als volle User-Modelle.
     */
    public function getLookupCrafts(): Collection
    {
        $crafts = Craft::query()
            ->orderBy('position')
            ->get(['id', 'name', 'abbreviation', 'color', 'position', 'universally_applicable']);

        foreach ($crafts as $craft) {
            self::slimPlaners($craft);
        }

        return $crafts;
    }

    public static function slimPlaners(Craft $craft): void
    {
        if ($craft->relationLoaded('craftShiftPlaner')) {
            $craft->craftShiftPlaner->each->setVisible(self::PLANER_VISIBLE);
        }
    }

    public function getAllWithAssignableWorkers(bool $withManagers = false): Collection
    {
        $workerVisible = [
            'id',
            'first_name',
            'last_name',
            'provider_name',
            'name',
            'full_name',
            'display_name',
            'profile_photo_url',
            'can_work_shifts',
            'is_freelancer',
            'type',
            'assigned_craft_ids',
            'shiftQualifications',
            'pivot',
        ];
        $qualificationVisible = ['id', 'name', 'icon', 'available', 'pivot'];
        // Frontend liest nur pivot.craft_id (Zuordnung Funktion → Gewerk)
        $qualificationPivotVisible = ['craft_id', 'shift_qualification_id'];

        $workerRelations = ['users', 'freelancers', 'serviceProviders'];
        if ($withManagers) {
            $workerRelations = [
                ...$workerRelations,
                'managingUsers',
                'managingFreelancers',
                'managingServiceProviders',
            ];
        }

        $with = ['qualifications'];
        foreach ($workerRelations as $relation) {
            // withAssignedCraftIds (HasShifts): Gewerk-IDs vorladen statt Query je Person
            $with[$relation] = static fn (Relation $query) => $query->withAssignedCraftIds();
        }

        // craftShiftPlaner (Craft::$with) bleibt geladen: die Listenansicht erkennt darüber,
        // ob die angemeldete Person Planer:in des Gewerks ist (Personen-Menü an der Schicht)
        $crafts = Craft::query()
            ->with($with)
            ->orderBy('position')
            ->get();

        foreach ($crafts as $craft) {
            self::slimPlaners($craft);
            foreach ($workerRelations as $relation) {
                foreach ($craft->getRelation($relation) as $worker) {
                    $worker->setVisible($workerVisible);
                    if ($worker->relationLoaded('shiftQualifications')) {
                        foreach ($worker->shiftQualifications as $qualification) {
                            $qualification->setVisible($qualificationVisible);
                            $qualification->pivot?->setVisible($qualificationPivotVisible);
                        }
                    }
                }
            }
        }

        return $crafts;
    }

    public function storeByRequest(CraftStoreRequest $craftStoreRequest): void
    {
        $craft = new Craft();
        $craft->fill($craftStoreRequest->only(['name', 'abbreviation', 'assignable_by_all', 'universally_applicable']));
        $this->craftRepository->save($craft);

        if (!$craftStoreRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftStoreRequest->get('users'));
            // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop
            User::forgetCachedShareDataForIds($craftStoreRequest->get('users') ?? []);
        }
    }

    public function updateByRequest(CraftUpdateRequest $craftUpdateRequest, Craft $craft): void
    {
        $craft->update($craftUpdateRequest
            ->only([
                'name',
                'abbreviation',
                'assignable_by_all',
                'color',
                'notify_days',
                'commit_request_deadline_days',
                'universally_applicable',
            ]));

        $managersToBeAssigned = $craftUpdateRequest->collect('managersToBeAssigned')->groupBy(
            function ($managerToBeAssigned) {
                return $managerToBeAssigned['manager_type'];
            }
        );

        if ($craftUpdateRequest->has('qualifications')) {
            $craft->qualifications()->detach();
            $craft->qualifications()->sync($craftUpdateRequest->collect('qualifications')->pluck('id')->toArray());
        }

        if ($managersToBeAssigned->empty()) {
            $craft->managingUsers()->sync([]);
            $craft->managingFreelancers()->sync([]);
            $craft->managingServiceProviders()->sync([]);
        }

        foreach ($managersToBeAssigned as $managerType => $managers) {
            switch ($managerType) {
                case User::class:
                    $craft->managingUsers()->sync($managers->pluck('manager_id'));
                    break;
                case Freelancer::class:
                    $craft->managingFreelancers()->sync($managers->pluck('manager_id'));
                    break;
                case ServiceProvider::class:
                    $craft->managingServiceProviders()->sync($managers->pluck('manager_id'));
                    break;
            }
        }

        // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop —
        // bisherige und neue Planer invalidieren
        $previousPlanerIds = $craft->craftShiftPlaner()->pluck('users.id')->all();
        if (!$craftUpdateRequest->boolean('assignable_by_all')) {
            $this->craftRepository->syncUsers($craft, $craftUpdateRequest->get('users'));
            User::forgetCachedShareDataForIds(array_unique([
                ...$previousPlanerIds,
                ...($craftUpdateRequest->get('users') ?? []),
            ]));
        } else {
            $this->craftRepository->detachUsers($craft);
            User::forgetCachedShareDataForIds($previousPlanerIds);
        }
    }

    public function delete(Craft $craft): void
    {
        $previousPlanerIds = $craft->craftShiftPlaner()->pluck('users.id')->all();
        $this->craftRepository->detachUsers($craft);
        $this->craftRepository->delete($craft);
        // Craft-Planer-Status steckt im gecachten shift_workflow_flags-Prop
        User::forgetCachedShareDataForIds($previousPlanerIds);
    }

    public function getAssignableByAllCrafts(): Collection
    {
        return $this->craftRepository->getAssignableByAllCrafts();
    }

    public function findById(int $id): Craft
    {
        return $this->craftRepository->findById($id);
    }

    public function reorder(array $crafts): void
    {
        foreach ($crafts as $craft) {
            $this->craftRepository->findById($craft['id'])->update(['position' => $craft['position']]);
        }
    }
}
