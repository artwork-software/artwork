<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Events\MultiShiftCreateInShiftPlan;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService
    ) {
    }

    public function createFromPresets(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id'        => ['required', 'integer'],
            'day'            => ['required'],
            'preset_ids'     => ['required', 'array', 'min:1'],
            'preset_ids.*'   => ['integer', 'exists:single_shift_presets,id'],
            'project_id'     => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $dayDate = Carbon::parse($data['day']);
        if (!$dayDate) {
            return redirect()->back()->with('error', 'Ungültiges Datum angegeben.');
        }

        $presetIds = collect($data['preset_ids'])
            ->filter()
            ->unique()
            ->values()
            ->all();

        /** @var \Illuminate\Support\Collection<int, SingleShiftPreset> $presets */
        $presets = SingleShiftPreset::query()
            ->whereIn('id', $presetIds)
            ->with([
                'craft:id,abbreviation,name',
                'shiftsQualifications',
            ])
            ->get();

        $createdShifts = collect();

        DB::transaction(function () use (&$createdShifts, $presets, $dayDate, $data): void {

            foreach ($presets as $preset) {
                $startTime = $this->normalizeTimeString($preset->start_time);
                $endTime   = $this->normalizeTimeString($preset->end_time);

                if (!$startTime || !$endTime) {
                    continue;
                }

                $startDate = $dayDate->copy();
                $endDate   = $dayDate->copy();
                if ($this->timeToMinutes($endTime) <= $this->timeToMinutes($startTime)) {
                    $endDate = $endDate->addDay();
                }

                /** @var Shift $shift */
                $shift = Shift::query()->create([
                    'room_id'        => (int) $data['room_id'],
                    'craft_id'       => (int) $preset->craft_id,
                    'project_id'     => $data['project_id'] ?? null,

                    'start_date'     => $startDate->toDateString(),
                    'end_date'       => $endDate->toDateString(),

                    'start'          => $startTime,
                    'end'            => $endTime,

                    'break_minutes'  => (int) ($preset->break_duration ?? 0),
                    'description'    => $preset->description,

                    // Shift defaults
                    'is_committed'   => false,
                    'in_workflow'    => false,

                    'event_start_day' => $startDate->toDateString(),
                    'event_end_day'   => $endDate->toDateString(),
                ]);

                $quals = $preset->shiftsQualifications ?? collect();

                if ($quals->isNotEmpty()) {
                    foreach ($quals as $qual) {
                        $qty = (int) ($qual->pivot?->quantity ?? 1);
                        if ($qty < 1) {
                            $qty = 1;
                        }

                        $shift->shiftsQualifications()->create([
                            'shift_qualification_id' => $qual->id,
                            'value'                  => $qty,
                        ]);
                    }
                } else {
                    $fallbackQualId = ShiftQualification::available()
                        ->workerQualification()
                        ->orderByCreationDateAscending()
                        ->value('id');

                    if ($fallbackQualId) {
                        $shift->shiftsQualifications()->create([
                            'shift_qualification_id' => $fallbackQualId,
                            'value'                  => 1,
                        ]);
                    }
                }
                $createdShifts->push($shift);
            }
        });

        if ($createdShifts->isEmpty()) {
            return redirect()->back()->with('error', 'Es konnten keine Schichten aus den ausgewählten Vorlagen erstellt werden.');
        }

        broadcast(new MultiShiftCreateInShiftPlan($createdShifts));

        return redirect()->back()->with('success', $createdShifts->count() . ' Schicht(en) wurden hinzugefügt.');
    }

    private function normalizeTimeString(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        $time = trim($time);
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return substr($time, 0, 5);
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$h, $m] = explode(':', $time);
            $h = str_pad($h, 2, '0', STR_PAD_LEFT);
            return $h . ':' . $m;
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function timeToMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return ($h * 60) + $m;
    }
}
