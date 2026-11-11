<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vorlage des Projektinhalte-Exports: Spalten, Filter ohne Zeitraum und Terminarten der Termin-Spalte.
 */
class ProjectContentExportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ProjectContentExportTemplate|null $template */
        $template = $this->route('projectContentExportTemplate');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('project_content_export_templates', 'name')->ignore($template?->id),
            ],
            ...ProjectContentExportRules::columns(),
            ...ProjectContentExportRules::filters('settings.filters', false),
            ...ProjectContentExportRules::eventColumn('settings.event_column'),
            'settings' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('A template with this name already exists.'),
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     columns: array<int, array{key: string, title: string|null, show_label: bool}>,
     *     settings: array<string, mixed>
     * }
     */
    public function templateData(): array
    {
        $validated = $this->validated();

        return [
            'name' => trim((string) $validated['name']),
            'columns' => array_values(array_map(static fn (array $column): array => [
                'key' => (string) $column['key'],
                'title' => isset($column['title']) && trim((string) $column['title']) !== ''
                    ? trim((string) $column['title'])
                    : null,
                'show_label' => (bool) ($column['show_label'] ?? false),
            ], $validated['columns'])),
            'settings' => [
                // Nur bekannte Filter, ohne Zeitraum (ändert sich bei jedem Export)
                'filters' => $this->templateFilters((array) ($validated['settings']['filters'] ?? [])),
                'event_column' => [
                    'event_type_ids' => array_values(array_map(
                        'intval',
                        (array) ($validated['settings']['event_column']['event_type_ids'] ?? [])
                    )),
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function templateFilters(array $filters): array
    {
        $result = [
            'project_group_id' => isset($filters['project_group_id']) ? (int) $filters['project_group_id'] : null,
        ];
        foreach (ProjectContentExportRules::LIST_FILTERS as $key) {
            $result[$key] = array_values(array_map('intval', (array) ($filters[$key] ?? [])));
        }

        return $result;
    }
}
