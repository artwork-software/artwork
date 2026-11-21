<?php

namespace Artwork\Modules\Room\Exports;

use Artwork\Modules\Room\Models\Room;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Raumliste als Excel-Tabelle: Areal, Stammdaten, Adresse, Kategorien, Eigenschaften, Nebenräume und Raumadmins.
 */
class RoomListExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * @param Collection<int, Room> $rooms mit geladenen area, categories, attributes, adjoining_rooms,
     *                                     admins, adminDepartments
     */
    public function __construct(private readonly Collection $rooms)
    {
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            __('Room area'),
            __('Room'),
            __('Description'),
            __('Capacity'),
            __('Street and house number'),
            __('Zip code'),
            __('City'),
            __('Room categories'),
            __('Room properties'),
            __('Adjoining rooms'),
            __('Room admins'),
            __('Can be booked by anyone'),
            __('Relevant for disposition'),
            __('Temporary room'),
            __('Start date'),
            __('End date'),
        ];
    }

    /**
     * @return array<int, array<int, string|int|null>>
     */
    public function array(): array
    {
        return $this->rooms
            ->map(fn (Room $room): array => [
                $room->area?->name,
                $room->name,
                $room->description,
                $room->capacity,
                $room->street,
                $room->zip_code,
                $room->city,
                $room->categories->pluck('name')->implode(', '),
                $room->getRelation('attributes')->pluck('name')->implode(', '),
                $room->adjoining_rooms->pluck('name')->implode(', '),
                $this->adminNames($room),
                $this->yesNo($room->everyone_can_book),
                $this->yesNo($room->relevant_for_disposition),
                $this->yesNo($room->temporary),
                $room->temporary ? $room->start_date?->format('d.m.Y') : null,
                $room->temporary ? $room->end_date?->format('d.m.Y') : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function adminNames(Room $room): string
    {
        // toBase(): eine Eloquent-Collection aus Namen ließe sich nicht mit weiteren Namen zusammenführen
        return $room->admins
            ->toBase()
            ->map(static fn ($user): string => trim($user->first_name . ' ' . $user->last_name))
            ->merge($room->adminDepartments->pluck('name'))
            ->filter()
            ->implode(', ');
    }

    private function yesNo(?bool $value): string
    {
        return $value ? __('Yes') : __('No');
    }
}
