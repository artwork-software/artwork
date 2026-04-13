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

    public function getSession(): ?array
    {
        $path = session('crm_import_path');
        $typeId = session('crm_import_type_id');

        if (!$path || !$typeId || !Storage::disk('local')->exists($path)) {
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

    public function cleanup(): void
    {
        $path = session('crm_import_path');
        if ($path) {
            Storage::disk('local')->delete($path);
        }
        session()->forget(['crm_import_path', 'crm_import_type_id']);
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
        $displayNameIndex = $mapping['display_name'];
        $propertyMapping = $mapping['properties'] ?? [];

        $created = 0;
        $skipped = [];

        $propertiesById = $properties->keyBy('id');

        foreach ($dataRows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 because row 1 is header, array is 0-indexed

            if (!collect($row)->contains(fn ($cell) => $cell !== null && trim((string) $cell) !== '')) {
                continue;
            }

            $displayName = trim((string) ($row[$displayNameIndex] ?? ''));
            if ($displayName === '') {
                $skipped[] = ['row' => $rowNumber, 'reason' => 'Empty name'];
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
                        'reason' => sprintf(
                            'Invalid value "%s" for property "%s" (%s)',
                            mb_substr($rawValue, 0, 50),
                            $property->name,
                            $property->type->value,
                        ),
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
                $skipped[] = ['row' => $rowNumber, 'reason' => 'Creation failed: ' . $e->getMessage()];
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
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
