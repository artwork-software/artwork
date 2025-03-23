<?php

namespace Artwork\Modules\Event\Services;

use App\Settings\EventSettings;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Repositories\EventStatusRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;

readonly class EventStatusService
{
    public function __construct(
        private EventStatusRepository $eventStatusRepository
    ) {
    }


    public function create(array $data): EventStatus
    {
        if($data['default']) {
            $this->eventStatusRepository->removeDefaultStatus();
        }

        // add order to the data
        $data['order'] = $this->eventStatusRepository->getNewModelQuery()->count() + 1;

        $status = $this->eventStatusRepository->getNewModelInstance($data);
        $this->eventStatusRepository->save($status);



        return $status;
    }

    public function update(EventStatus $eventStatus, array $data): EventStatus
    {
        if($data['default']) {
            $this->eventStatusRepository->removeDefaultStatus();
        }

        $eventStatus->fill($data);
        $this->eventStatusRepository->save($eventStatus);

        return $eventStatus;
    }

    public function updateSettings(Request $request): void
    {
        $settings = app(EventSettings::class);
        $settings->enable_status = $request->boolean('enable_status');
        $settings->save();

        if ($request->boolean('enable_status')) {
            $eventsWithoutStatus = Event::whereNull('event_status_id')->get();
            $eventsWithoutStatus->each(function ($event): void {
                $event->update(['event_status_id' => 1]);
            });
        } else {
            // setze den Status in den Calendar Settings der user auf false bei use_event_status_color
            $users = User::all();

            $users->each(function ($user): void {
                /** @var User $user */
                $user->calendar_settings()->update(['use_event_status_color' => false]);
            });
        }
    }

    public function delete(EventStatus $eventStatus): void
    {
        $this->eventStatusRepository->delete($eventStatus);
    }
}
