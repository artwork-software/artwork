<?php

namespace Artwork\Modules\Crm\Services;

use Artwork\Modules\Crm\Exports\CrmContactsExcelExport;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

readonly class CrmExportService
{
    public function export(array $filters): BinaryFileResponse
    {
        $columns = $filters['columns'] ?? ['display_name'];

        $query = CrmContact::query()
            ->with(['contactType', 'propertyValues']);

        if (!empty($filters['contact_type_ids'])) {
            $query->whereIn('crm_contact_type_id', $filters['contact_type_ids']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['project_ids'])) {
            $projectIds = $filters['project_ids'];
            $query->where(function ($q) use ($projectIds): void {
                $q->whereHas('artistResidencies', function ($sub) use ($projectIds): void {
                    $sub->whereIn('project_id', $projectIds);
                })->orWhereHas('accommodationResidencies', function ($sub) use ($projectIds): void {
                    $sub->whereIn('project_id', $projectIds);
                });
            });
        }

        $contacts = $query->orderBy('display_name')->get();

        $propertyIds = collect($columns)
            ->filter(fn (string $col) => str_starts_with($col, 'prop_'))
            ->map(fn (string $col) => (int) str_replace('prop_', '', $col))
            ->values()
            ->toArray();

        $properties = $propertyIds
            ? CrmProperty::whereIn('id', $propertyIds)->orderBy('sort_order')->get()
            : new Collection();

        $export = new CrmContactsExcelExport($contacts, $columns, $properties);
        $filename = 'crm-export-' . now()->format('Y-m-d_His') . '.xlsx';

        return $export->download($filename)->deleteFileAfterSend();
    }
}
