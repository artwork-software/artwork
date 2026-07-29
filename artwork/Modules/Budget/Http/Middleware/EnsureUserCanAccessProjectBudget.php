<?php

namespace Artwork\Modules\Budget\Http\Middleware;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\RowComment;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageAssignedDataComment;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Budget\Services\BudgetModelProjectResolverService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * Backend-Autorisierung für alle Budget-Endpunkte: Mutationen erfordern
 * Admin-Rolle, globale Budget-Berechtigung oder Budgetzugriff (access_budget)
 * auf dem betroffenen Projekt. Vorlagen-/globale Endpunkte erfordern die
 * Template- bzw. globale Budget-Berechtigung.
 */
class EnsureUserCanAccessProjectBudget
{
    /**
     * Request-Schlüssel (Body oder Query) => Model-Klasse zur Projekt-Auflösung.
     */
    private const INPUT_KEY_MODEL_MAP = [
        'table_id' => Table::class,
        'column_id' => Column::class,
        'columnId' => Column::class,
        'first_column_id' => Column::class,
        'cell_id' => ColumnCell::class,
        'selectedCell' => ColumnCell::class,
        'sub_position_id' => SubPosition::class,
        'subPosition_id' => SubPosition::class,
        'subPositionId' => SubPosition::class,
        'main_position_id' => MainPosition::class,
        'mainPosition_id' => MainPosition::class,
        'mainPositionId' => MainPosition::class,
        'sub_position_row_id' => SubPositionRow::class,
        'selectedRow' => SubPositionRow::class,
        'sageAssignedDataId' => SageAssignedData::class,
        'sage_assigned_data_id' => SageAssignedData::class,
    ];

    public function __construct(
        private readonly BudgetModelProjectResolverService $projectResolverService,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        abort_unless((bool) $user, 401);

        if (
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->canAny([
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN->value,
                PermissionEnum::GLOBAL_PROJECT_BUDGET_ADMIN_NO_DOCS->value,
            ])
        ) {
            return $next($request);
        }

        $projectIds = $this->resolveProjectIds($request);

        if ($projectIds !== []) {
            // JEDES referenzierte Projekt muss freigegeben sein — sonst ließe
            // sich mit einer eigenen project_id/table_id im Body eine fremde
            // column_id/row_id am Zugriffscheck vorbeischleusen (Cross-Projekt-IDOR).
            $accessibleCount = Project::query()
                ->whereIn('id', $projectIds)
                ->whereHas('access_budget', fn ($query) => $query->where('users.id', $user->id))
                ->count();
            abort_unless($accessibleCount === count($projectIds), 403);

            return $next($request);
        }

        // Kein Projektbezug auflösbar: Vorlagen-Endpunkte (und deren Papierkorb)
        // brauchen die Template-Berechtigung, alles andere (z. B. globale
        // Sage-Aufräumaktionen) bleibt den globalen Rollen oben vorbehalten.
        $routeName = (string) $request->route()?->getName();
        if (str_contains($routeName, 'template') || str_contains($routeName, 'trashed')) {
            abort_unless(
                $user->canAny([
                    PermissionEnum::UPDATE_BUDGET_TEMPLATES->value,
                    PermissionEnum::VIEW_BUDGET_TEMPLATES->value,
                ]),
                403
            );

            return $next($request);
        }

        abort(403);
    }

    /**
     * Sammelt ALLE aus dem Request auflösbaren Projekt-Ids (Route-Bindings,
     * project_id, Body-Ids, verschachtelte Payloads). Kein First-Match: jede
     * referenzierte Id zählt, damit gemischte Payloads nicht am Check vorbeikommen.
     *
     * @return array<int, int>
     */
    private function resolveProjectIds(Request $request): array
    {
        $projectIds = [];

        // 1) Route-Model-Bindings (project, column, table, columnCell, subPosition, ...)
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (!$parameter instanceof Model) {
                continue;
            }

            if ($parameter instanceof Project) {
                $projectIds[] = $parameter->id;
                continue;
            }

            $projectId = $this->projectResolverService->resolveProjectId($this->unwrap($parameter));
            if ($projectId !== null) {
                $projectIds[] = $projectId;
            }
        }

        // 2) Direkte project_id im Request (z. B. Vorlage in Projekt importieren)
        $directProjectId = $request->input('project_id');
        if ($directProjectId !== null && !is_array($directProjectId)) {
            $projectIds[] = (int) $directProjectId;
        }

        // 3) Ids im Body/Query (table_id, column_id, cell_id, ...)
        foreach (self::INPUT_KEY_MODEL_MAP as $key => $modelClass) {
            $id = $request->input($key);
            if ($id === null || is_array($id)) {
                continue;
            }

            $projectId = $this->resolveFromModelId($modelClass, $id);
            if ($projectId !== null) {
                $projectIds[] = $projectId;
            }
        }

        // 4) Festschreibungs-Endpunkte senden {position: {id}, type: main|sub}
        $positionId = $request->input('position.id');
        if ($positionId !== null && !is_array($positionId)) {
            $positionClass = $request->input('type') === 'main' ? MainPosition::class : SubPosition::class;
            $projectId = $this->resolveFromModelId($positionClass, $positionId);
            if ($projectId !== null) {
                $projectIds[] = $projectId;
            }
        }

        // 5) Zeilen-Reorder sendet verschachtelt {updates: [{sub_position_id, row_ids}]}
        foreach ((array) $request->input('updates', []) as $update) {
            if (!is_array($update)) {
                continue;
            }

            $subPositionId = $update['sub_position_id'] ?? null;
            if ($subPositionId !== null && !is_array($subPositionId)) {
                $projectId = $this->resolveFromModelId(SubPosition::class, $subPositionId);
                if ($projectId !== null) {
                    $projectIds[] = $projectId;
                }
            }
        }

        return array_values(array_unique($projectIds));
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function resolveFromModelId(string $modelClass, mixed $id): ?int
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
            ? $modelClass::withTrashed()
            : $modelClass::query();

        $model = $query->find($id);
        if ($model === null) {
            return null;
        }

        return $this->projectResolverService->resolveProjectId($model);
    }

    /**
     * Kommentare/Kalkulationen auf ihr Budget-Model zurückführen, damit der
     * Projekt-Resolver sie auflösen kann.
     */
    private function unwrap(Model $model): Model
    {
        return match (true) {
            $model instanceof CellComment => $model->cell ?? $model,
            $model instanceof CellCalculation => ColumnCell::withTrashed()->find($model->cell_id) ?? $model,
            $model instanceof RowComment =>
                SubPositionRow::withTrashed()->find($model->sub_position_row_id) ?? $model,
            $model instanceof SageAssignedDataComment => $model->sageAssignedData ?? $model,
            default => $model,
        };
    }
}
