<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Artwork\Modules\AiAssistant\Services\AiExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/ai/exports (core-ai-event-stats-exports): eine Tabelle als PDF, XLSX oder CSV
 * (`format`, Default `pdf`; core-ai-export-formats).
 *
 * Die Zeilen kommen aus Werkzeugergebnissen, die artwork-ai bereits gelesen hat — der Endpunkt liest
 * selbst keine Daten und braucht deshalb nur `ai:read`. Zellen sind Text, Zahl oder leer.
 */
class StoreAiExportRequest extends FormRequest
{
    public const CELL_LIMIT = 500;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'note' => ['nullable', 'string', 'max:1000'],
            'columns' => ['required', 'array', 'min:1', 'max:' . (int) config('ai.export_max_columns')],
            'columns.*' => ['required', 'string', 'max:80'],
            'rows' => ['present', 'array', 'max:' . (int) config('ai.export_max_rows')],
            'rows.*' => ['array'],
            'format' => ['nullable', 'string', Rule::in(AiExportService::FORMATS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $columns = $this->input('columns');
            $rows = $this->input('rows');

            if (!is_array($columns) || !is_array($rows)) {
                return;
            }

            foreach ($rows as $i => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (count($row) > count($columns)) {
                    $validator->errors()->add("rows.{$i}", 'Eine Zeile hat mehr Zellen als Spalten.');

                    return;
                }

                foreach ($row as $cell) {
                    $valid = $cell === null || is_int($cell) || is_float($cell)
                        || (is_string($cell) && mb_strlen($cell) <= self::CELL_LIMIT);

                    if (!$valid) {
                        $validator->errors()->add(
                            "rows.{$i}",
                            'Zellen sind Text (hoechstens ' . self::CELL_LIMIT . ' Zeichen), Zahl oder leer.'
                        );

                        return;
                    }
                }
            }
        });
    }

    /**
     * @return list<list<string>>
     */
    public function rows(): array
    {
        $width = count($this->columns());

        return array_values(array_map(
            fn (array $row): array => array_pad(
                array_values(array_map(fn ($cell): string => $cell === null ? '' : (string) $cell, $row)),
                $width,
                ''
            ),
            (array) $this->validated('rows')
        ));
    }

    public function exportFormat(): string
    {
        return (string) ($this->validated('format') ?? 'pdf');
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return array_values(array_map('strval', (array) $this->validated('columns')));
    }
}
