<?php

namespace Artwork\Modules\InternalIssue\Http\Requests;

use Carbon\Carbon;
use Illuminate\Validation\Validator;
use Throwable;

/**
 * Ende muss nach dem Beginn liegen (Datum UND Uhrzeit). Ein umgekehrter Zeitraum wurde von
 * Verfügbarkeit und Planung ignoriert – die Reservierung und jede Überbuchung blieben unsichtbar.
 */
trait ValidatesInternalIssuePeriod
{
    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start_date', 'start_time', 'end_date', 'end_time'])) {
                    return;
                }

                try {
                    $start = Carbon::parse($this->input('start_date') . ' ' . $this->input('start_time'));
                    $end = Carbon::parse($this->input('end_date') . ' ' . $this->input('end_time'));
                } catch (Throwable) {
                    $validator->errors()->add('end_time', __('validation.date', ['attribute' => 'end_time']));

                    return;
                }

                if ($end->lte($start)) {
                    $validator->errors()->add('end_time', __('The end must be after the start.'));
                }
            },
        ];
    }
}
