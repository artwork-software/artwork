<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmContactType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class CrmImportService
{
    private const MIRRORED_SLUGS = ['user', 'freelancer', 'service_provider'];

    public function __construct(
        private CrmContactService $contactService,
        private CrmContactTypeService $contactTypeService,
    ) {
    }

    public function getImportableContactTypes(): \Illuminate\Support\Collection
    {
        return $this->contactTypeService->getActive()
            ->reject(fn ($type) => in_array($type->slug, self::MIRRORED_SLUGS))
            ->values();
    }

    public function storeAndParseUpload(UploadedFile $file): ?array
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('crm-imports', $filename, 'local');
        $fullPath = Storage::disk('local')->path($path);

        $parsed = $this->parseFile($fullPath);

        if (empty($parsed['headers'])) {
            Storage::disk('local')->delete($path);
            return null;
        }

        return ['path' => $path, 'parsed' => $parsed];
    }

    public function loadContactTypeForMapping(int $contactTypeId): CrmContactType
    {
        $contactType = CrmContactType::with('properties')->findOrFail($contactTypeId);

        $contactType->setRelation(
            'properties',
            $contactType->properties->reject(
                fn ($p) => $p->type === CrmPropertyTypeEnum::UPLOAD
            )->values()
        );

        return $contactType;
    }

    public function storeSession(string $path, int $contactTypeId): void
    {
        session([
            'crm_import_path' => $path,
            'crm_import_type_id' => $contactTypeId,
        ]);
    }

    public function storeSessionMultiType(string $path): void
    {
        session([
            'crm_import_path' => $path,
            'crm_import_multi_type' => true,
        ]);
    }

    public function getSession(): ?array
    {
        $path = session('crm_import_path');

        if (!$path || !Storage::disk('local')->exists($path)) {
            return null;
        }

        $multiType = session('crm_import_multi_type', false);

        if ($multiType) {
            return [
                'path' => $path,
                'multiType' => true,
                'typeColumnIndex' => session('crm_import_type_column_index'),
                'typeValueMapping' => session('crm_import_type_value_mapping', []),
                'fullPath' => Storage::disk('local')->path($path),
            ];
        }

        $typeId = session('crm_import_type_id');
        if (!$typeId) {
            return null;
        }

        return [
            'path' => $path,
            'typeId' => $typeId,
            'fullPath' => Storage::disk('local')->path($path),
        ];
    }

    public function runImport(array $mapping): array
    {
        $sessionData = $this->getSession();
        if (!$sessionData) {
            return ['created' => 0, 'skipped' => []];
        }

        $contactType = CrmContactType::with('properties')->findOrFail($sessionData['typeId']);

        $result = $this->executeImport(
            $sessionData['fullPath'],
            $sessionData['typeId'],
            $mapping,
            $contactType->properties,
        );

        $this->cleanup();

        return $result;
    }

    public function extractUniqueColumnValuesFromFile(string $path, int $columnIndex): array
    {
        $fullPath = Storage::disk('local')->path($path);
        $sheets = Excel::toArray(new class {
        }, $fullPath);
        $rows = $sheets[0] ?? [];

        if (count($rows) <= 1) {
            return [];
        }

        $dataRows = array_slice($rows, 1);
        $values = [];
        $counts = [];

        foreach ($dataRows as $row) {
            $val = trim((string) ($row[$columnIndex] ?? ''));
            if ($val !== '') {
                if (!isset($counts[$val])) {
                    $values[] = $val;
                    $counts[$val] = 0;
                }
                $counts[$val]++;
            }
        }

        sort($values);

        return array_map(fn (string $v) => ['value' => $v, 'count' => $counts[$v]], $values);
    }

    public function storeTypeValueMapping(array $mapping, int $typeColumnIndex): void
    {
        session([
            'crm_import_type_column_index' => $typeColumnIndex,
            'crm_import_type_value_mapping' => $mapping,
        ]);
    }

    public function loadContactTypesForMultiMapping(array $typeIds): \Illuminate\Support\Collection
    {
        return CrmContactType::with('properties')
            ->whereIn('id', $typeIds)
            ->get()
            ->map(function (CrmContactType $type) {
                $type->setRelation(
                    'properties',
                    $type->properties->reject(
                        fn ($p) => $p->type === CrmPropertyTypeEnum::UPLOAD
                    )->values()
                );

                return $type;
            });
    }

    public function runMultiTypeImport(array $typeMappings): array
    {
        $sessionData = $this->getSession();
        if (!$sessionData || !($sessionData['multiType'] ?? false)) {
            return ['created' => 0, 'skipped' => []];
        }

        $typeColumnIndex = $sessionData['typeColumnIndex'];
        $typeValueMapping = collect($sessionData['typeValueMapping']);

        // Build lookup: typeValue → contactTypeId
        $valueToTypeId = [];
        foreach ($typeValueMapping as $entry) {
            if (!empty($entry['crm_contact_type_id'])) {
                $valueToTypeId[$entry['type_value']] = (int) $entry['crm_contact_type_id'];
            }
        }

        // Build lookup: contactTypeId → mapping
        $typeIdToMapping = [];
        foreach ($typeMappings as $tm) {
            $typeIdToMapping[(int) $tm['crm_contact_type_id']] = [
                'display_name' => (int) $tm['display_name'],
                'properties' => $tm['properties'] ?? [],
            ];
        }

        // Load all needed contact types with properties
        $contactTypeIds = array_unique(array_values($valueToTypeId));
        $contactTypes = CrmContactType::with('properties')
            ->whereIn('id', $contactTypeIds)
            ->get()
            ->keyBy('id');

        $sheets = Excel::toArray(new class {
        }, $sessionData['fullPath']);
        $rows = $sheets[0] ?? [];

        if (count($rows) <= 1) {
            $this->cleanup();
            return ['created' => 0, 'skipped' => []];
        }

        $dataRows = array_slice($rows, 1);
        $created = 0;
        $skipped = [];

        foreach ($dataRows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;

            if (!collect($row)->contains(fn ($cell) => $cell !== null && trim((string) $cell) !== '')) {
                continue;
            }

            $typeValue = trim((string) ($row[$typeColumnIndex] ?? ''));
            $contactTypeId = $valueToTypeId[$typeValue] ?? null;

            if ($contactTypeId === null) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => __('Unknown or unmapped type value') . ': "' . mb_substr($typeValue, 0, 50) . '"',
                ];
                continue;
            }

            $mapping = $typeIdToMapping[$contactTypeId] ?? null;
            if ($mapping === null) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => __('No mapping for contact type ID') . ' ' . $contactTypeId,
                ];
                continue;
            }

            $contactType = $contactTypes->get($contactTypeId);
            if (!$contactType) {
                continue;
            }

            $propertiesById = $contactType->properties->keyBy('id');
            $displayNameIndex = $mapping['display_name'] ?? null;
            $nameColumnIndices = $this->resolveNameFallbackColumns($mapping['properties'] ?? [], $propertiesById);

            $displayName = $this->resolveDisplayName($row, $displayNameIndex, $nameColumnIndices);
            if ($displayName === '') {
                $skipped[] = ['row' => $rowNumber, 'reason' => __('Empty name')];
                continue;
            }
            $propertyValues = [];
            $rowSkipped = false;

            foreach ($mapping['properties'] as $propertyId => $columnIndex) {
                $property = $propertiesById->get((int) $propertyId);
                if (!$property) {
                    continue;
                }

                $rawValue = trim((string) ($row[$columnIndex] ?? ''));
                if ($rawValue === '') {
                    continue;
                }

                $castResult = $this->castValue($rawValue, $property);
                if ($castResult === false) {
                    $skipped[] = [
                        'row' => $rowNumber,
                        'reason' => __('Invalid value ":value" for property ":property" (:type)', [
                            'value' => mb_substr($rawValue, 0, 50),
                            'property' => $property->name,
                            'type' => $property->type->value,
                        ]),
                    ];
                    $rowSkipped = true;
                    break;
                }

                $propertyValues[$propertyId] = $castResult;
            }

            if ($rowSkipped) {
                continue;
            }

            try {
                $this->contactService->store(
                    [
                        'crm_contact_type_id' => $contactTypeId,
                        'display_name' => $displayName,
                    ],
                    $propertyValues,
                );
                $created++;
            } catch (\Throwable $e) {
                Log::warning('CRM Import: Failed to create contact', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
                $skipped[] = ['row' => $rowNumber, 'reason' => __('Creation failed') . ': ' . $e->getMessage()];
            }
        }

        $this->cleanup();

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function cleanup(): void
    {
        $path = session('crm_import_path');
        if ($path) {
            Storage::disk('local')->delete($path);
        }
        session()->forget([
            'crm_import_path',
            'crm_import_type_id',
            'crm_import_multi_type',
            'crm_import_type_column_index',
            'crm_import_type_value_mapping',
        ]);
    }

    public function getSessionContactTypeSlug(): ?string
    {
        $typeId = session('crm_import_type_id');
        if (!$typeId) {
            return null;
        }

        return CrmContactType::find($typeId)?->slug;
    }

    private function parseFile(string $filePath): array
    {
        $sheets = Excel::toArray(new class {
        }, $filePath);
        $rows = $sheets[0] ?? [];

        if (count($rows) === 0) {
            return ['headers' => [], 'preview' => [], 'totalRows' => 0];
        }

        $headers = array_map(fn ($h) => trim((string) $h), $rows[0]);
        $dataRows = array_slice($rows, 1);

        // Filter out completely empty rows
        $dataRows = array_values(array_filter($dataRows, function (array $row): bool {
            return collect($row)->contains(fn ($cell) => $cell !== null && trim((string) $cell) !== '');
        }));

        $preview = array_slice($dataRows, 0, 5);

        return [
            'headers' => $headers,
            'preview' => $preview,
            'totalRows' => count($dataRows),
        ];
    }

    private function executeImport(
        string $filePath,
        int $contactTypeId,
        array $mapping,
        Collection $properties,
    ): array {
        $sheets = Excel::toArray(new class {
        }, $filePath);
        $rows = $sheets[0] ?? [];

        if (count($rows) <= 1) {
            return ['created' => 0, 'skipped' => []];
        }

        $dataRows = array_slice($rows, 1);
        $displayNameIndex = $mapping['display_name'] ?? null;
        $propertyMapping = $mapping['properties'] ?? [];

        $created = 0;
        $skipped = [];

        $propertiesById = $properties->keyBy('id');
        $nameColumnIndices = $this->resolveNameFallbackColumns($propertyMapping, $propertiesById);

        foreach ($dataRows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 because row 1 is header, array is 0-indexed

            if (!collect($row)->contains(fn ($cell) => $cell !== null && trim((string) $cell) !== '')) {
                continue;
            }

            $displayName = $this->resolveDisplayName($row, $displayNameIndex, $nameColumnIndices);
            if ($displayName === '') {
                $skipped[] = ['row' => $rowNumber, 'reason' => __('Empty name')];
                continue;
            }

            $propertyValues = [];
            $rowSkipped = false;

            foreach ($propertyMapping as $propertyId => $columnIndex) {
                $property = $propertiesById->get((int) $propertyId);
                if (!$property) {
                    continue;
                }

                $rawValue = trim((string) ($row[$columnIndex] ?? ''));
                if ($rawValue === '') {
                    continue;
                }

                $castResult = $this->castValue($rawValue, $property);
                if ($castResult === false) {
                    $skipped[] = [
                        'row' => $rowNumber,
                        'reason' => __('Invalid value ":value" for property ":property" (:type)', [
                            'value' => mb_substr($rawValue, 0, 50),
                            'property' => $property->name,
                            'type' => $property->type->value,
                        ]),
                    ];
                    $rowSkipped = true;
                    break;
                }

                $propertyValues[$propertyId] = $castResult;
            }

            if ($rowSkipped) {
                continue;
            }

            try {
                $this->contactService->store(
                    [
                        'crm_contact_type_id' => $contactTypeId,
                        'display_name' => $displayName,
                    ],
                    $propertyValues,
                );
                $created++;
            } catch (\Throwable $e) {
                Log::warning('CRM Import: Failed to create contact', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                ]);
                $skipped[] = ['row' => $rowNumber, 'reason' => __('Creation failed') . ': ' . $e->getMessage()];
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private const NAME_FALLBACK_ALIASES = [
        'first_name' => ['vorname', 'first name', 'first_name', 'firstname'],
        'last_name' => ['nachname', 'last name', 'last_name', 'lastname', 'familienname', 'surname'],
    ];

    /**
     * Find column indices for first_name/last_name properties from the property mapping.
     *
     * @return array{first_name: int|null, last_name: int|null}
     */
    private function resolveNameFallbackColumns(array $propertyMapping, Collection $propertiesById): array
    {
        $result = ['first_name' => null, 'last_name' => null];

        foreach ($propertyMapping as $propertyId => $columnIndex) {
            $property = $propertiesById->get((int) $propertyId);
            if (!$property) {
                continue;
            }

            $normalized = mb_strtolower(trim($property->name));

            if (in_array($normalized, self::NAME_FALLBACK_ALIASES['first_name'], true)) {
                $result['first_name'] = (int) $columnIndex;
            } elseif (in_array($normalized, self::NAME_FALLBACK_ALIASES['last_name'], true)) {
                $result['last_name'] = (int) $columnIndex;
            }
        }

        return $result;
    }

    /**
     * Resolve display_name from the row: use mapped column, or fall back to first+last name.
     */
    private function resolveDisplayName(array $row, ?int $displayNameIndex, array $nameColumnIndices): string
    {
        if ($displayNameIndex !== null) {
            $name = trim((string) ($row[$displayNameIndex] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        // Fallback: build from first_name + last_name
        $parts = [];
        if ($nameColumnIndices['first_name'] !== null) {
            $parts[] = trim((string) ($row[$nameColumnIndices['first_name']] ?? ''));
        }
        if ($nameColumnIndices['last_name'] !== null) {
            $parts[] = trim((string) ($row[$nameColumnIndices['last_name']] ?? ''));
        }

        return trim(implode(' ', array_filter($parts)));
    }

    private function castValue(string $value, mixed $property): string|false
    {
        return match ($property->type) {
            CrmPropertyTypeEnum::TEXT,
            CrmPropertyTypeEnum::TEXTAREA,
            CrmPropertyTypeEnum::LINK => $value,

            CrmPropertyTypeEnum::NUMBER => is_numeric($value) ? $value : false,

            CrmPropertyTypeEnum::DATE => $this->parseDate($value),

            CrmPropertyTypeEnum::CHECKBOX => in_array(
                mb_strtolower($value),
                ['1', 'true', 'yes', 'ja', 'x'],
                true,
            ) ? '1' : '0',

            CrmPropertyTypeEnum::SELECT => in_array($value, $property->select_values ?? [], true)
                ? $value
                : false,

            CrmPropertyTypeEnum::UPLOAD => false,
        };
    }

    private function parseDate(string $value): string|false
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return false;
        }
    }
}
