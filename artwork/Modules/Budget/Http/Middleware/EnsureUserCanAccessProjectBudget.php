<?php

namespace Artwork\Modules\Budget\Http\Middleware;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\RowComment;
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

        $projectId = $this->resolveProjectId($request);

        if ($projectId !== null) {
            $project = Project::find($projectId);
            abort_unless(
                $project && $project->access_budget()->where('users.id', $user->id)->exists(),
                403
            );

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

    private function resolveProjectId(Request $request): ?int
    {
        // 1) Route-Model-Bindings (project, column, table, columnCell, subPosition, ...)
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if (!$parameter instanceof Model) {
                continue;
            }

            if ($parameter instanceof Project) {
                return $parameter->id;
            }

            $projectId = $this->projectResolverService->resolveProjectId($this->unwrap($parameter));
            if ($projectId !== null) {
                return $projectId;
            }
        }

        // 2) Direkte project_id im Request (z. B. Vorlage in Projekt importieren)
        if (($directProjectId = $request->input('project_id')) !== null) {
            return (int) $directProjectId;
        }

        // 3) Ids im Body/Query (table_id, column_id, cell_id, ...)
        foreach (self::INPUT_KEY_MODEL_MAP as $key => $modelClass) {
            $id = $request->input($key);
            if ($id === null || is_array($id)) {
                continue;
            }

            $model = $modelClass::withTrashed()->find($id);
            if ($model === null) {
                continue;
            }

            $projectId = $this->projectResolverService->resolveProjectId($model);
            if ($projectId !== null) {
                return $projectId;
            }
        }

        return null;
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
