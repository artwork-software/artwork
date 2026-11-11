<?php

namespace Artwork\Modules\Project\Http\Requests;

use Artwork\Modules\Project\Services\ContentExport\ProjectContentExportConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Einstellungen eines Projektinhalte-Exports. Für die Vorschau (Anzahl Projekte) sind Spalten optional.
 */
class ProjectContentExportRequest extends FormRequest
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
        $isPreview = $this->routeIs('projects.content-export.preview');

        return [
            'format' => [$isPreview ? 'sometimes' : 'required', Rule::in([
                ProjectContentExportConfiguration::FORMAT_XLSX,
                ProjectContentExportConfiguration::FORMAT_DOCX,
            ])],
            'template_id' => ['nullable', 'integer', 'exists:project_content_export_templates,id'],
            ...ProjectContentExportRules::columns(!$isPreview),
            ...ProjectContentExportRules::filters(),
            ...ProjectContentExportRules::eventColumn(),
        ];
    }

    public function configuration(): ProjectContentExportConfiguration
    {
        $validated = $this->validated();
        $validated['format'] ??= ProjectContentExportConfiguration::FORMAT_XLSX;
        $validated['columns'] ??= [];

        return ProjectContentExportConfiguration::fromArray($validated);
    }
}
