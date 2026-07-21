<?php

namespace App\Http\Controllers;

use App\Settings\GeneralCalendarSettings;
use Artwork\Modules\Calendar\Events\DayRemarkUpdated;
use Artwork\Modules\Calendar\Models\DayRemark;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;

class DayRemarkController extends Controller
{
    /**
     * Upsert der instanzweiten Tagesbemerkung eines Datums.
     * Leerer Text löscht den Eintrag (genau eine Bemerkung pro Tag).
     */
    public function upsert(Request $request, string $date): JsonResponse
    {
        if (!app(GeneralCalendarSettings::class)->day_remarks_enabled) {
            abort(404);
        }

        try {
            $parsedDate = Carbon::createFromFormat('!Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            abort(422, 'Invalid date format, expected Y-m-d.');
        }

        if ($parsedDate->toDateString() !== $date) {
            abort(422, 'Invalid date format, expected Y-m-d.');
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:500'],
        ]);

        $text = trim($validated['remark'] ?? '');

        if ($text === '') {
            DayRemark::query()->where('date', $parsedDate->toDateString())->delete();

            $this->broadcastDayRemarkUpdate($parsedDate->toDateString(), null);

            return new JsonResponse([
                'date' => $parsedDate->toDateString(),
                'dayRemark' => null,
            ]);
        }

        $userId = Auth::id();

        $dayRemark = DayRemark::query()->whereDate('date', $parsedDate->toDateString())->first();

        if ($dayRemark !== null) {
            $dayRemark->update([
                'remark' => $text,
                'updated_by' => $userId,
            ]);
        } else {
            try {
                $dayRemark = DayRemark::query()->create([
                    'date' => $parsedDate->toDateString(),
                    'remark' => $text,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request inserted this date after our lookup.
                DayRemark::query()->whereDate('date', $parsedDate->toDateString())->update([
                    'remark' => $text,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $dayRemark = DayRemark::query()->whereDate('date', $parsedDate->toDateString())->firstOrFail();
            }
        }

        $dayRemarkPayload = [
            'text' => $dayRemark->remark,
            'updated_by' => Auth::user()?->full_name,
            'updated_at' => $dayRemark->updated_at?->format('d.m.Y H:i'),
        ];

        $this->broadcastDayRemarkUpdate($parsedDate->toDateString(), $dayRemarkPayload);

        return new JsonResponse([
            'date' => $parsedDate->toDateString(),
            'dayRemark' => $dayRemarkPayload,
        ]);
    }

    /**
     * DayRemarkUpdated ist ShouldBroadcastNow: broadcast() wäre ein synchroner
     * HTTP-Call an Reverb im Request — deshalb erst nach dem Response ausführen
     * (läuft im selben Prozess, braucht keinen Queue-Worker).
     *
     * @param array<string, mixed>|null $dayRemark
     */
    private function broadcastDayRemarkUpdate(string $date, ?array $dayRemark): void
    {
        dispatch(static function () use ($date, $dayRemark): void {
            broadcast(new DayRemarkUpdated($date, $dayRemark));
        })->afterResponse();
    }
}
