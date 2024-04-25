<?php

namespace Artwork\Modules\Event\Http\Requests;

use Carbon\Carbon;

class EventUpdateRequest extends EventStoreOrUpdateRequest
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'start_time' => Carbon::create($this->get('start'))->setTimezone(config('app.timezone')),
            'end_time' => Carbon::create($this->get('end'))->setTimezone(config('app.timezone')),
            'room_id' => $this->get('roomId'),
            'declined_room_id' => $this->get('declinedRoomId'),
            'name' => $this->get('title'),
            'eventName' => $this->get('eventName'),
            'project_id_mandatory' => $this->get('projectIdMandatory'),
            'event_name_mandatory' => $this->get('eventNameMandatory'),
            'creating_project' => $this->get('creatingProject'),
            'description' => $this->get('description'),
            'audience' => $this->get('audience'),
            'is_loud' => $this->get('isLoud'),
            'project_id' => $this->get('projectId'),
            'event_type_id' => $this->get('eventTypeId'),
            'occupancy_option' => $this->get('isOption'),
            'is_series' => $this->get('is_series'),
            'frequency' => $this->get('seriesFrequency'),
            'seriesEnd' => $this->get('seriesEndDate'),
            'allSeriesEvents' => $this->get('allSeriesEvents'),
            'adminComment' => $this->get('adminComment'),
            'option_string' => $this->get('optionString'),
            'accept' => $this->get('accept'),
            'optionAccept' => $this->get('optionAccept'),
            'allDay' => $this->get('allDay')
        ];
    }
}
