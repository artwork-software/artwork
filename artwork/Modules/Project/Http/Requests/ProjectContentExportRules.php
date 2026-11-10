<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportColumns;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Gemeinsame Regeln für Projektinhalte-Export und seine Vorlagen (Spalten, Filter, Termin-Spalte).
 */
final class ProjectContentExportRules
{
    private const MAX_COLUMNS = 100;

    /** Filter, die Listen von IDs sind (Merkmale und Mitglieder). */
    public const LIST_FILTERS = [
        'project_state_ids',
        'category_ids',
        'genre_ids',
        'sector_ids',
        'event_type_ids',
        'member_ids',
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function columns(bool $required = true): array
    {
        return [
            'columns' => [$required ? 'required' : 'sometimes', 'array', 'min:1', 'max:' . self::MAX_COLUMNS],
            'columns.*' => ['array'],
            'columns.*.key' => [
                'required',
                'string',
                'max:50',
                'distinct',
                // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (!is_string($value) || !ProjectContentExportColumns::isValidKey($value)) {
                        $fail(__('validation.in', ['attribute' => 'column']));
                    }
                },
            ],
            'columns.*.title' => ['nullable', 'string', 'max:255'],
            'columns.*.show_label' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param bool $withDateRange Vorlagen speichern den Zeitraum bewusst nicht
     * @return array<string, array<int, mixed>>
     */
    public static function filters(string $prefix = 'filters', bool $withDateRange = true): array
    {
        $rules = [
            $prefix => ['sometimes', 'array'],
            "$prefix.project_group_id" => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where('is_group', true)->whereNull('deleted_at'),
            ],
        ];
        foreach (ProjectContentExportRules::LIST_FILTERS as $key) {
            $rules["$prefix.$key"] = ['sometimes', 'array', 'max:500'];
            $rules["$prefix.$key.*"] = ['integer'];
        }
        if ($withDateRange) {
            $rules["$prefix.date_start"] = ['nullable', 'date_format:Y-m-d', "required_with:$prefix.date_end"];
            $rules["$prefix.date_end"] = [
                'nullable',
                'date_format:Y-m-d',
                "required_with:$prefix.date_start",
                "after_or_equal:$prefix.date_start",
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function eventColumn(string $prefix = 'event_column'): array
    {
        return [
            $prefix => ['sometimes', 'array'],
            "$prefix.event_type_ids" => ['sometimes', 'array', 'max:500'],
            "$prefix.event_type_ids.*" => ['integer'],
        ];
    }
}
