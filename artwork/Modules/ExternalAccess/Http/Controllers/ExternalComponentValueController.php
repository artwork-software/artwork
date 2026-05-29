<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\ExternalAccess\Http\Requests\UpdateExternalComponentValueRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalComponentValueService;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\JsonResponse;

class ExternalComponentValueController extends Controller
{
    public function __construct(
        private readonly ExternalComponentValueService $service,
    ) {
    }

    public function update(
        UpdateExternalComponentValueRequest $request,
        Project $project,
        ProjectTab $tab,
        Component $component,
    ): JsonResponse {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        $this->service->updateComponentValue(
            external: $external,
            project: $project,
            tab: $tab,
            component: $component,
            data: $request->validated('data'),
        );

        return response()->json([
            'status' => 'updated',
        ]);
    }
}
