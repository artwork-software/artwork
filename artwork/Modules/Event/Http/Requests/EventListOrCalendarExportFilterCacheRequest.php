<?php

namespace Artwork\Modules\Event\Http\Requests;

use Artwork\Modules\Filter\Services\FilterService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventListOrCalendarExportFilterCacheRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $desiresTimespanExport = $this->boolean('desiresTimespanExport');
        $desiresEventListExport = $this->boolean('desiresEventListExport');

        return [
            'desiresTimespanExport' => 'boolean',
            'desiresEventListExport' => 'boolean',
            // Anzeigeeinstellungen des Kalender-Excel-Exports (Kalender-Settings als Default,
            // im Modal übersteuert) — nested Keys einzeln, collect() reicht sie in den Cache
            'displaySettings' => 'sometimes|nullable|array',
            'displaySettings.use_event_status_color' => 'sometimes|boolean',
            'displaySettings.use_main_category_color' => 'sometimes|boolean',
            'displaySettings.show_artist_names_as_title' => 'sometimes|boolean',
            'displaySettings.event_name' => 'sometimes|boolean',
            'displaySettings.description' => 'sometimes|boolean',
            'displaySettings.project_artists' => 'sometimes|boolean',
            'displaySettings.project_status' => 'sometimes|boolean',
            'displaySettings.project_management' => 'sometimes|boolean',
            'displaySettings.show_event_creator' => 'sometimes|boolean',
            'displaySettings.show_event_admission' => 'sometimes|boolean',
            'displaySettings.show_event_status' => 'sometimes|boolean',
            'displaySettings.show_day_remarks' => 'sometimes|boolean',
            'displaySettings.hide_unoccupied_rooms' => 'sometimes|boolean',
            'displaySettings.show_planned_events' => 'sometimes|boolean',
            'desiredColumns' => [Rule::requiredIf($desiresEventListExport), 'array'],
            'conditional' => 'required|array',
            'conditional.projects' => [Rule::requiredIf($desiresTimespanExport === false), 'array'],
            'conditional.projects.*' => 'exists:projects,id',
            'conditional.dateStart' => [Rule::requiredIf($desiresTimespanExport), 'string'],
            'conditional.dateEnd' => [Rule::requiredIf($desiresTimespanExport), 'string'],
            'filter' => 'required|array',
            'filter.roomCategories' => 'array',
            'filter.roomCategories.*' => 'exists:room_categories,id',
            'filter.roomAttributes' => 'array',
            'filter.eventTypes' => 'array',
            'filter.eventTypes.*' => 'exists:event_types,id',
            // Event properties (custom properties from event_properties table)
            'filter.eventProperties' => 'array',
            'filter.eventProperties.*' => 'exists:event_properties,id',
            'filter.projectStates' => 'array',
            'filter.projectStates.*' => 'exists:project_states,id',
            // Optional: event attributes (fixed flags) — kept for future use if frontend sends them
//            'filter.eventAttributes' => 'array',
//            'filter.eventAttributes.*' => Rule::in(
//                [
//                    FilterService::LOUD,
//                    FilterService::NOT_LOUD,
//                    FilterService::WITH_AUDIENCE,
//                    FilterService::WITHOUT_AUDIENCE
//                ]
//            ),
            'filter.areas' => 'array',
            'filter.areas.*' => 'exists:areas,id',
            'filter.rooms' => 'array',
            'filter.rooms.*' => 'exists:rooms,id'
        ];
    }
}
