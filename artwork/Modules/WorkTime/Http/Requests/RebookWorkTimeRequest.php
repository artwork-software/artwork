<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Artwork\Modules\WorkTime\Support\WorkTimeAccounting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * „Tag neu buchen“ in den Arbeitszeiten: vergangene Tage nach aktueller Rechnung (neu) buchen.
 */
class RebookWorkTimeRequest extends FormRequest
{
    /**
     * Höchstzahl Tage je Aufruf (ein Jahr), damit ein Klick nicht beliebig lange rechnet.
     */
    public const MAX_DAYS = 366;

    public function authorize(): bool
    {
        // Gleiche Hürde wie die manuelle Buchung: verändert das Stundenkonto
        return WorkTimeAccounting::isEnabled() && ($this->user()?->can('can manage workers') ?? false);
    }

    /**
     * @return array<int, \Closure(\Illuminate\Validation\Validator): void>
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                $user = $this->route('user');
                if ($user instanceof User && !$user->can_work_shifts) {
                    $validator->errors()->add(
                        'dates',
                        __('Only people in the shift plan have a time account that can be booked.')
                    );

                    return;
                }
                if ($user instanceof User && !$validator->errors()->has('dates.*')) {
                    $this->validateTimeAccountStarted($validator, $user);
                }
            },
        ];
    }

    /**
     * Zeitkonto beginnt mit der ersten Tagesbuchung der Person: davor (Muster rückwirkend gültig, Zeitkonto
     * noch nicht geführt, Zeitraum vor Einführung) wird nichts gebucht – auch nicht per direktem Aufruf.
     */
    private function validateTimeAccountStarted(\Illuminate\Validation\Validator $validator, User $user): void
    {
        $firstBookingDay = app(WorkTimeBookingRepository::class)->firstDailyBookingDay($user);
        $dates = array_filter((array) $this->input('dates', []), 'is_string');
        $beforeStart = array_filter(
            $dates,
            static fn (string $date): bool => $firstBookingDay === null || $date < $firstBookingDay
        );
        if ($beforeStart !== []) {
            $validator->errors()->add(
                'dates',
                __('Days before the first booking of the time account cannot be booked.')
            );
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dates' => ['required', 'array', 'min:1', 'max:' . self::MAX_DAYS],
            'dates.*' => ['required', 'date_format:Y-m-d', 'before:today'],
        ];
    }
}
