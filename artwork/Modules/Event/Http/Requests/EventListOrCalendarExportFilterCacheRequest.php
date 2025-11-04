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
