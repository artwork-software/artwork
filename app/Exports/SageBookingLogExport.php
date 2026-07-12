<?php

namespace App\Exports;

use Artwork\Modules\Budget\Models\SageBookingLog;
use Artwork\Modules\Budget\Models\SageBookingLogEntry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SageBookingLogExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private readonly SageBookingLog $log)
    {
    }

    public function collection(): Collection
    {
        return $this->log->entries()->orderBy('id')->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Date'),
            __('Action'),
            __('Assignment'),
            __('Sage ID'),
            __('KTR'),
            __('KST'),
            __('Debit account'),
            __('Credit account'),
            __('Creditor'),
            __('Booking text'),
            __('Amount'),
            __('Document number'),
            __('Document date'),
            __('Booking date'),
            __('Collective booking'),
        ];
    }

    /**
     * @param SageBookingLogEntry $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->created_at?->format('d.m.Y H:i:s'),
            $this->translateAction($row->action),
            $this->translateTarget($row->target_type),
            $row->sage_id,
            $row->kst_traeger,
            $row->kst_stelle,
            $row->kto_soll,
            $row->kto_haben,
            $row->kreditor,
            $row->buchungstext,
            $row->buchungsbetrag,
            $row->belegnummer,
            $row->belegdatum,
            $row->buchungsdatum,
            $row->is_collective_booking ? __('Yes') : __('No'),
        ];
    }

    private function translateAction(string $action): string
    {
        return match ($action) {
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            default => $action,
        };
    }

    private function translateTarget(string $target): string
    {
        return match ($target) {
            'assigned' => __('Assigned'),
            'not_assigned' => __('Not assigned'),
            default => $target,
        };
    }
}
