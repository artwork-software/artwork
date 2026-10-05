<?php

namespace Artwork\Modules\Project\Http\Middleware;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Closure;
use Illuminate\Http\Request;

/**
 * Typgebundene Tab-Daten-Endpunkte (projects.tabs.*): nach dem Projektzugriff (CanViewProject) muss
 * die Person eine Komponente des passenden Typs in einem für sie sichtbaren Tab sehen dürfen —
 * dieselbe Regel wie in der Projektansicht. Mit ALLOW_PROJECT_WRITERS genügen zusätzlich
 * Schreibrechte im Projekt (Endpunkte, die auch Bearbeiten-Dialoge außerhalb der Tabs laden).
 */
class EnsureUserCanSeeProjectComponent
{
    public const ALLOW_PROJECT_WRITERS = 'project-writers';

    public function __construct(
        private readonly ProjectComponentVisibilityService $projectComponentVisibilityService,
    ) {
    }

    public static function for(ProjectTabComponentEnum ...$types): string
    {
        return self::class . ':' . implode(',', array_map(
            static fn (ProjectTabComponentEnum $type): string => $type->value,
            $types
        ));
    }

    public static function forOrProjectWriters(ProjectTabComponentEnum ...$types): string
    {
        return self::for(...$types) . ',' . self::ALLOW_PROJECT_WRITERS;
    }

    public function handle(Request $request, Closure $next, string ...$parameters): mixed
    {
        $user = $request->user();
        abort_unless((bool) $user, 401);

        $allowProjectWriters = in_array(self::ALLOW_PROJECT_WRITERS, $parameters, true);
        $types = array_values(array_diff($parameters, [self::ALLOW_PROJECT_WRITERS]));

        if ($this->projectComponentVisibilityService->canSeeComponentTypeInProject($user, $types)) {
            return $next($request);
        }

        $project = $request->route('project');
        if ($allowProjectWriters && $project instanceof Project && $user->can('update', $project)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this project component.');
    }
}
