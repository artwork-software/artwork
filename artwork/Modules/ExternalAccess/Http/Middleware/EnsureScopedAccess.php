<?php

namespace Artwork\Modules\ExternalAccess\Http\Middleware;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Services\ExternalScopeResolver;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScopedAccess
{
    public function __construct(
        private readonly ExternalScopeResolver $scopeResolver,
    ) {
    }

    /**
     * @param string $requiredAccess 'read' or 'write' (default: 'read')
     */
    public function handle(Request $request, Closure $next, string $requiredAccess = 'read'): Response
    {
        $external = $request->user('external');
        if (!$external) {
            // Should not happen when the external middleware group is configured
            // correctly — kept as defense in depth.
            abort(401);
        }

        $project = $request->route('project');
        $tab = $request->route('tab');

        if (!$project instanceof Project || !$tab instanceof ProjectTab) {
            abort(404);
        }

        // ProjectTab is a global tab definition (no project_id column); the same
        // tab exists for every project and is bound to a project only through
        // external_access_scopes (project_id + project_tab_id). A tab/project
        // consistency check is therefore obsolete — the scope lookup below is the
        // authoritative restriction.
        $scope = $this->scopeResolver->resolveScope($external, $project->id, $tab->id);
        if (!$scope) {
            // Scope expired / missing → 403 with a clear message. The external
            // frontend catches 403 and shows the "access expired" page.
            abort(403, __('Your access to this tab has expired or been removed.'));
        }

        if ($requiredAccess === 'write' && $scope->access_type !== ExternalAccessType::WRITE) {
            abort(403, __('You do not have write access to this tab.'));
        }

        // Attach the scope to the request for downstream controllers.
        $request->attributes->set('external_scope', $scope);

        return $next($request);
    }
}
