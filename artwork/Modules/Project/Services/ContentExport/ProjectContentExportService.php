<?php

namespace Artwork\Modules\Project\Services\ContentExport;

use Artwork\Core\Services\CacheService;
use Artwork\Modules\Event\Exceptions\ExportLinkExpiredException;
use Artwork\Modules\Project\Exports\ProjectContentExcelWriter;
use Artwork\Modules\Project\Exports\ProjectContentWordWriter;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectContentExportTemplate;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Projektinhalte-Export (Excel/Word) aus der Projektübersicht. Ablauf wie beim Terminexport: Einstellungen
 * werden kurz gecacht (Token), der Download öffnet sich in einem eigenen Tab. Das Token gilt nur für die
 * Person, die es angelegt hat.
 */
class ProjectContentExportService
{
    public function __construct(
        private readonly ProjectContentExportColumns $columns,
        private readonly ProjectContentExportQuery $query,
        private readonly ProjectContentRowBuilder $rowBuilder,
        private readonly ProjectContentExcelWriter $excelWriter,
        private readonly ProjectContentWordWriter $wordWriter,
        private readonly CacheService $cacheService,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     * @throws Throwable
     */
    public function cacheConfiguration(User $user, array $validated): string
    {
        return (string) $this->cacheService->setValueAndGetCacheTokenValidForTenSeconds([
            'user_id' => $user->id,
            'configuration' => $validated,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function downloadBy(User $user, string $token): BinaryFileResponse
    {
        $cached = $this->cacheService->getValueByToken($token);
        if (!is_array($cached) || !isset($cached['configuration'])) {
            throw new ExportLinkExpiredException();
        }
        if ((int) ($cached['user_id'] ?? 0) !== $user->id) {
            throw new AccessDeniedHttpException();
        }

        $configuration = ProjectContentExportConfiguration::fromArray($cached['configuration']);
        $file = $this->export($user, $configuration);

        return response()
            ->download($file['path'], $file['filename'])
            ->deleteFileAfterSend();
    }

    /**
     * @return array{count: int, hidden_columns: int}
     */
    public function preview(User $user, ProjectContentExportConfiguration $configuration): array
    {
        return [
            'count' => $this->query->count($user, $configuration),
            'hidden_columns' => $this->resolveColumns($user, $configuration)['hidden'],
        ];
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function export(User $user, ProjectContentExportConfiguration $configuration): array
    {
        $columns = $this->resolveColumns($user, $configuration)['columns'];
        $projects = $this->query->build($user, $configuration)->get();
        $rows = $this->rowBuilder->build($projects, $columns, $configuration);

        $isWord = $configuration->format === ProjectContentExportConfiguration::FORMAT_DOCX;
        $path = $isWord
            ? $this->wordWriter->write(array_column($rows, 'cells'))
            : $this->excelWriter->write(
                array_column($columns, 'title'),
                array_map(static fn (array $row): array => array_column($row['cells'], 'value'), $rows)
            );

        $template = $configuration->templateId !== null
            ? ProjectContentExportTemplate::query()->find($configuration->templateId)
            : null;

        $this->logExport($user, $projects->modelKeys(), $isWord ? 'Word' : 'Excel', $template?->name);

        return [
            'path' => $path,
            'filename' => $this->filename($template?->name, $configuration->format),
        ];
    }

    /**
     * Spalten der Einstellungen, die die Person ausgeben darf. Komponenten, die sie nicht sehen darf (z. B. aus
     * der Vorlage einer anderen Person), entfallen und werden gezählt.
     *
     * @return array{
     *     columns: array<int, array{key: string, title: string, show_label: bool, component: Component|null}>,
     *     hidden: int
     * }
     */
    public function resolveColumns(User $user, ProjectContentExportConfiguration $configuration): array
    {
        $systemColumns = $this->columns->systemColumns();
        $visibleComponents = $this->columns->visibleComponents($user)->keyBy('id');

        $columns = [];
        $hidden = 0;
        foreach ($configuration->columns as $column) {
            $componentId = ProjectContentExportColumns::componentIdOf($column['key']);
            $component = $componentId !== null ? $visibleComponents->get($componentId) : null;

            if ($componentId !== null ? $component === null : !array_key_exists($column['key'], $systemColumns)) {
                $hidden++;
                continue;
            }

            $columns[] = [
                'key' => $column['key'],
                'title' => $column['title'] ?? $this->columns->defaultTitle($column['key'], $component),
                'show_label' => $column['show_label'],
                'component' => $component,
            ];
        }

        return ['columns' => $columns, 'hidden' => $hidden];
    }

    /**
     * Eintrag im Verlauf jedes exportierten Projekts (gleiches Property-Format wie ChangeBuilder).
     *
     * @param array<int, int> $projectIds
     */
    private function logExport(User $user, array $projectIds, string $formatLabel, ?string $templateName): void
    {
        if ($projectIds === []) {
            return;
        }

        $translationKey = $templateName !== null
            ? 'Project contents were exported as {0} (template "{1}")'
            : 'Project contents were exported as {0}';
        $placeholders = $templateName !== null ? [$formatLabel, $templateName] : [$formatLabel];
        $properties = json_encode([[
            'type' => 'project',
            'translationKey' => $translationKey,
            'translationKeyPlaceholderValues' => $placeholders,
        ]], JSON_THROW_ON_ERROR);

        $now = now();
        $subjectType = (new Project())->getMorphClass();
        $table = (new Activity())->getTable();

        foreach (array_chunk($projectIds, 500) as $chunk) {
            DB::table($table)->insert(array_map(static fn (int $projectId): array => [
                'log_name' => 'project',
                'description' => $translationKey,
                'subject_type' => $subjectType,
                'subject_id' => $projectId,
                'event' => 'exported',
                'causer_type' => $user->getMorphClass(),
                'causer_id' => $user->id,
                'properties' => $properties,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }

    private function filename(?string $templateName, string $format): string
    {
        $parts = array_filter([
            __('Project contents'),
            $templateName,
            now()->format('Y-m-d'),
        ]);

        $name = implode('_', array_map(
            static fn (string $part): string => trim(
                Str::of($part)->replaceMatches('/[\\\\\/:*?"<>|]+/', '-')->toString()
            ),
            $parts
        ));

        return $name . '.' . $format;
    }
}
