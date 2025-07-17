<?php

namespace Artwork\Modules\Event\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Http\Requests\CreateEventPropertyRequest;
use Artwork\Modules\Event\Http\Requests\UpdateEventPropertyRequest;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventPropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Response;
use Inertia\ResponseFactory;

class EventPropertyController extends Controller
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private EventPropertyService $eventPropertyService,
        private Redirector $redirector
    ) {
    }

    public function index(): Response
    {
        return $this->responseFactory->render(
            'Settings/EventProperties/Index',
            [
                'event_properties' => $this->eventPropertyService->getAll()
            ]
        );
    }

    public function store(CreateEventPropertyRequest $request): RedirectResponse
    {
        if($request->validated()){
            $this->eventPropertyService->createFromRequest($request);
            return $this->redirector->back();
        }

        return $this->redirector->back()->withErrors($request->validated());
    }

    public function update(
        EventProperty $eventProperty,
        UpdateEventPropertyRequest $request
    ): RedirectResponse {
        if($request->validated()){
            $this->eventPropertyService->updateFromRequest($eventProperty, $request);
            return $this->redirector->back();
        }

        return $this->redirector->back()->withErrors($request->validated());
    }

    public function destroy(EventProperty $eventProperty): RedirectResponse
    {
        $this->eventPropertyService->forceDelete($eventProperty);

        return $this->redirector->back();
    }
}
