<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class EventUpdateRequest extends EventStoreOrUpdateRequest
{
    public function data()
    {
        return [
            'start_time' => Carbon::parse($this->get('start'))->setTimezone(config('app.timezone')),
            'end_time' => Carbon::parse($this->get('end'))->setTimezone(config('app.timezone')),
            'room_id' => $this->get('roomId'),
            'name' => $this->get('title'),
            'eventName' => $this->get('eventName'),
            'project_id_mandatory' => $this->get('projectIdMandatory'),
            'creating_project' => $this->get('creatingProject'),
            'description' => $this->get('description'),
            'audience' => $this->get('audience'),
            'is_loud' => $this->get('isLoud'),
            'project_id' => $this->get('projectId'),
            'event_type_id' => $this->get('eventTypeId'),
            'occupancy_option' => $this->get('isOption'),
        ];
    }
}
