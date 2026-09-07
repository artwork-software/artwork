<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Http\Requests\StoreUserContractAssignRequest;
use Artwork\Modules\User\Http\Requests\UpdateUserContractAssignRequest;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Speichern-Endpunkt des Userprofil-Tabs "Vertrag & Arbeitszeit" (beide Routen
 * user-contract-settings.update-user und shift.work-time-pattern.update-user landen hier).
 *
 * Vertragszuweisung ist eine Historie (user_contract_assigns.valid_from/valid_until):
 *  - assign_id gesetzt            → diesen Zeitraum bearbeiten (Felder und/oder Gültigkeit)
 *  - valid_from ohne assign_id    → NEUEN Zeitraum anlegen; der bisher offene Zeitraum
 *                                   (valid_until null, Beginn vor dem neuen) wird auf valid_from − 1 Tag geschlossen
 *  - weder noch (Altpfad)         → den heute gültigen Zeitraum aktualisieren bzw. anlegen
 * Überschneidungen mit anderen Zeiträumen → 422.
 *
 * Arbeitszeit (user_work_times) hat ihre eigene Historie: id → Update, sonst updateOrCreate(user_id, valid_from).
 */
class UserContractAssignController extends Controller
{
    private const WORK_TIME_FIELDS = [
        'work_time_pattern_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    private const META_FIELDS = ['id', 'assign_id', 'valid_from', 'valid_until'];

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserContractAssignRequest $request, User $user): RedirectResponse
    {
        // Nur freigegebene Felder übernehmen – niemals user_id vom Request
        $data = $request->safe()->except(['user_id']);

        $validFrom = self::dateOrNull($data['valid_from'] ?? null);
        $validUntil = self::dateOrNull($data['valid_until'] ?? null);
        $hasValidity = $request->exists('valid_from') || $request->exists('valid_until');

        // Kein ->filter(): false/0/null sind gueltige Werte (Regel deaktivieren,
        // Vertrag entfernen via user_contract_id = null, Felder auf 0 setzen).
        $contractData = collect($data)->except(array_merge(self::WORK_TIME_FIELDS, self::META_FIELDS))->all();
        $workTimeData = collect($data)->only(self::WORK_TIME_FIELDS)->all();
        $assignId = isset($data['assign_id']) ? (int) $data['assign_id'] : null;
        $workTimeId = isset($data['id']) ? (int) $data['id'] : null;

        $retroactive = false;

        try {
            DB::transaction(function () use (
                $user,
                $contractData,
                $workTimeData,
                $assignId,
                $workTimeId,
                $validFrom,
                $validUntil,
                $hasValidity,
                &$retroactive
            ): void {
                if ($assignId !== null || !empty($contractData)) {
                    $assign = $this->saveContractPeriod(
                        $user,
                        $assignId,
                        $contractData,
                        $validFrom,
                        $validUntil,
                        $hasValidity
                    );
                    $retroactive = $retroactive || self::isRetroactive($assign);
                }

                if ($this->hasWorkTimeData($workTimeData)) {
                    $workTime = $this->saveWorkTimePeriod($user, $workTimeId, $workTimeData, $validFrom, $validUntil);
                    $retroactive = $retroactive
                        || ($workTime->valid_from !== null && $workTime->valid_from->lt(Carbon::today()));
                }
            });

            // If the overtime rule settings changed, recompute so deadlines/status reflect the new period.
            if ($request->has('overtime_rule_active') || $request->exists('overtime_compensation_period')) {
                app(OvertimeService::class)->recomputeForUser($user);
            }

            // Neuprüfung läuft über UserContractAssign::booted() → ShiftRuleRevalidationService (ab valid_from)
            return back()->with('success', self::successMessage(__('User contract assigned successfully.'), $retroactive));
        } catch (ValidationException | ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to assign user contract', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('Could not assign user contract. Please try again.'));
        }
    }

    /**
     * Einen Vertragszeitraum entfernen (Historie). Der Satz muss zur Person gehören.
     */
    public function destroyAssign(User $user, UserContractAssign $assign): RedirectResponse
    {
        if ((int) $assign->user_id !== (int) $user->id) {
            abort(404);
        }

        $retroactive = self::isRetroactive($assign);
        $assign->delete();

        return back()->with('success', self::successMessage(__('Contract period removed.'), $retroactive));
    }

    /**
     * Flash-Text; bei rückwirkenden Zeiträumen mit Hinweis auf die Neuprüfung festgeschriebener Schichten
     * (die App teilt nur success/error als Flash – daher im Erfolgstext statt eigenem Kanal).
     */
    private static function successMessage(string $base, bool $retroactive): string
    {
        if (!$retroactive) {
            return $base;
        }

        return $base . ' ' . __(
            'The period starts in the past. Committed shifts in this period will be re-checked against the shift rules.'
        );
    }

    /**
     * Einen Arbeitszeit-Satz (user_work_times) entfernen. Der Satz muss zur Person gehören.
     */
    public function destroyWorkTime(User $user, UserWorkTime $workTime): RedirectResponse
    {
        if ((int) $workTime->user_id !== (int) $user->id) {
            abort(404);
        }

        $workTime->delete();

        return back()->with('success', __('Work time period removed.'));
    }

    /**
     * @param array<string, mixed> $contractData
     */
    private function saveContractPeriod(
        User $user,
        ?int $assignId,
        array $contractData,
        ?Carbon $validFrom,
        ?Carbon $validUntil,
        bool $hasValidity
    ): UserContractAssign {
        if ($assignId !== null) {
            /** @var UserContractAssign $assign */
            $assign = $user->contractAssigns()->whereKey($assignId)->firstOrFail();

            $newFrom = $hasValidity ? $validFrom : $assign->valid_from;
            $newUntil = $hasValidity ? $validUntil : $assign->valid_until;
            $this->assertNoOverlap($user, $newFrom, $newUntil, $assign->id);

            $assign->fill($contractData);
            $assign->valid_from = $newFrom;
            $assign->valid_until = $newUntil;
            $assign->save();

            return $assign;
        }

        if ($validFrom === null) {
            // Altpfad (ohne Gültigkeitsbeginn): heute gültigen Zeitraum aktualisieren (Gültigkeit unverändert),
            // sonst anlegen – ohne Historie offen ab Beginn, mit Historie ab heute.
            $current = $user->contractAssignFor(Carbon::today());
            if ($current !== null) {
                $current->fill($contractData)->save();

                return $current;
            }

            $from = $user->contractAssigns()->exists() ? Carbon::today() : null;
            $this->assertNoOverlap($user, $from, $validUntil, null);

            return $user->contractAssigns()->create(array_merge($contractData, [
                'valid_from' => $from,
                'valid_until' => $validUntil,
            ]));
        }

        // Neuer Zeitraum: bisher offenen Zeitraum (Beginn vor dem neuen) einen Tag vorher schließen
        $closeAt = $validFrom->copy()->subDay();
        $user->contractAssigns()
            ->whereNull('valid_until')
            ->where(function ($query) use ($validFrom): void {
                $query->whereNull('valid_from')->orWhereDate('valid_from', '<', $validFrom->toDateString());
            })
            ->get()
            ->each(function (UserContractAssign $open) use ($closeAt): void {
                $open->valid_until = $closeAt;
                $open->save();
            });

        $this->assertNoOverlap($user, $validFrom, $validUntil, null);

        return $user->contractAssigns()->create(array_merge($contractData, [
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ]));
    }

    /**
     * @param array<string, mixed> $workTimeData
     */
    private function saveWorkTimePeriod(
        User $user,
        ?int $workTimeId,
        array $workTimeData,
        ?Carbon $validFrom,
        ?Carbon $validUntil
    ): UserWorkTime {
        $workTimeData['user_id'] = $user->id;
        $workTimeData['valid_from'] = ($validFrom ?? Carbon::today())->toDateString();
        $workTimeData['valid_until'] = $validUntil?->toDateString();

        if ($workTimeId !== null) {
            /** @var UserWorkTime $workTime */
            $workTime = $user->workTimes()->whereKey($workTimeId)->firstOrFail();
            $workTime->fill($workTimeData)->save();

            return $workTime;
        }

        return $user->workTimes()->updateOrCreate(
            ['user_id' => $user->id, 'valid_from' => $workTimeData['valid_from']],
            $workTimeData
        );
    }

    /**
     * 422, wenn sich [$from, $until] mit einem anderen Zeitraum der Person überschneidet.
     */
    private function assertNoOverlap(User $user, ?Carbon $from, ?Carbon $until, ?int $ignoreId): void
    {
        $conflict = $user->contractAssigns()
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->with('userContract')
            ->get()
            ->first(fn (UserContractAssign $other): bool => $other->overlaps($from, $until));

        if ($conflict === null) {
            return;
        }

        throw ValidationException::withMessages([
            'valid_from' => __(
                'The period overlaps with the existing contract period {0} ({1} – {2}). Adjust the dates or edit that period.',
                [
                    $conflict->userContract?->name ?? __('individual'),
                    $conflict->valid_from?->format('d.m.Y') ?? __('open'),
                    $conflict->valid_until?->format('d.m.Y') ?? __('open'),
                ]
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $workTimeData
     */
    private function hasWorkTimeData(array $workTimeData): bool
    {
        return collect($workTimeData)->filter(fn ($value) => !is_null($value))->isNotEmpty();
    }

    private static function isRetroactive(UserContractAssign $assign): bool
    {
        return $assign->valid_from !== null && $assign->valid_from->copy()->startOfDay()->lt(Carbon::today());
    }

    private static function dateOrNull(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse((string) $value)->startOfDay();
    }

    /**
     * Display the specified resource.
     */
    public function show(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserContractAssignRequest $request, UserContractAssign $userContractAssign): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserContractAssign $userContractAssign): void
    {
        //
    }
}
