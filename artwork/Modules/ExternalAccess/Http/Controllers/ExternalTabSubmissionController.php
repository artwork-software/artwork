<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalTabSubmissionService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExternalTabSubmissionController extends Controller
{
    /**
     * Service bewusst per Methoden-Injection: Konstruktor-DI würde die Abhängigkeitskette
     * (NotificationService → Session) schon vor der externen Session-Middleware auflösen.
     */
    public function store(
        Request $request,
        Project $project,
        ProjectTab $tab,
        ExternalTabSubmissionService $service,
    ): RedirectResponse {
        /** @var ExternalAccess $external */
        $external = $request->user('external');
        /** @var ExternalAccessScope $scope */
        $scope = $request->attributes->get('external_scope');

        $service->submit($external, $project, $tab, $scope);

        return redirect()
            ->route('external.project.tab.show', ['project' => $project->id, 'tab' => $tab->id])
            ->with('status', __('Your data has been submitted. The inviting person has been notified.'));
    }
}
