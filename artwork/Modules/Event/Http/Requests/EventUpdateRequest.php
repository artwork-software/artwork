<?php

namespace Artwork\Modules\Event\Http\Requests;

use Carbon\Carbon;

class EventUpdateRequest extends EventStoreOrUpdateRequest
{
    /**
     * Event-Attribut => Request-Feld.
     */
    private const FIELDS = [
        'admission_time' => 'admissionTime',
        'room_id' => 'roomId',
        'declined_room_id' => 'declinedRoomId',
        'name' => 'title',
        'eventName' => 'eventName',
        'project_id_mandatory' => 'projectIdMandatory',
        'event_name_mandatory' => 'eventNameMandatory',
        'creating_project' => 'creatingProject',
        'description' => 'description',
        'audience' => 'audience',
        'is_loud' => 'isLoud',
        'project_id' => 'projectId',
        'event_type_id' => 'eventTypeId',
        'event_status_id' => 'eventStatusId',
        'is_series' => 'is_series',
        'frequency' => 'seriesFrequency',
        'seriesEnd' => 'seriesEndDate',
        'allSeriesEvents' => 'allSeriesEvents',
        'adminComment' => 'adminComment',
        'option_string' => 'optionString',
        'accept' => 'accept',
        'optionAccept' => 'optionAccept',
        'allDay' => 'allDay',
    ];

    /**
     * Retrieve data from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- Signatur der Elternklasse erlaubt keinen Typ
    public function data($key = null, $default = null): mixed
    {
        $eventData = [
            'start_time' => Carbon::create($this->get('start'))->setTimezone(config('app.timezone')),
            'end_time' => Carbon::create($this->get('end'))->setTimezone(config('app.timezone')),
        ];

        // Nur übernehmen, was der Aufrufer mitschickt: Antwort-Dialog und „Termine ohne Raum“ senden
        // z. B. keinen Status, keinen Einlass und keine Eigenschaften – vorher wurden diese beim
        // Speichern auf null gesetzt bzw. geleert.
        foreach (self::FIELDS as $attribute => $input) {
            if ($this->has($input)) {
                $eventData[$attribute] = $this->get($input);
            }
        }
        if ($this->has('isOption')) {
            $eventData['occupancy_option'] = $this->booleanValue('isOption');
        }
        if ($this->has('event_properties')) {
            $eventData['event_properties'] = $this->input('event_properties');
        }

        if ($key === null) {
            return $eventData;
        }

        return $eventData[$key] ?? $default;
    }
}
