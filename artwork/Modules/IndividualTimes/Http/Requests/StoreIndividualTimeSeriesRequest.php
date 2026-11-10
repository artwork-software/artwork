<?php

namespace Artwork\Modules\IndividualTimes\Http\Requests;

use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Throwable;

class StoreIndividualTimeSeriesRequest extends FormRequest
{
    /**
     * Längste erlaubte Serie: jeder Tag wird als eigene Zeile je Person angelegt, eine offene Serie
     * (z. B. bis 9999) würde Millionen Zeilen in einem Request erzeugen.
     */
    public const MAX_SPAN_DAYS = 731;

    /**
     * Validierungs-Closure für end_date: höchstens MAX_SPAN_DAYS nach start_date.
     */
    public static function maxSpanRule(Request $request): Closure
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Laravel-Validierungs-Closure (attribute, value, fail) – Signatur vorgegeben
        return static function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            try {
                $start = Carbon::parse((string) $request->input('start_date'));
                $end = Carbon::parse((string) $value);
            } catch (Throwable) {
                return;
            }

            if ($start->diffInDays($end, false) > self::MAX_SPAN_DAYS) {
                $fail(__('A series may span at most :days days.', ['days' => self::MAX_SPAN_DAYS]));
            }
        };
    }

    public function authorize(): bool
    {
        // hier ggf. Policy prüfen, erstmal true
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],

            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date', self::maxSpanRule($this)],

            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time'   => ['nullable', 'date_format:H:i'],
            'full_day'   => ['sometimes', 'boolean'],

            'working_time_minutes' => ['nullable', 'integer', 'min:0'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],

            'frequency' => ['required', 'string', 'in:weekly'], // später erweiterbar
            'interval'  => ['required', 'integer', 'min:1'],

            'weekdays'   => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],

            // Ausgewählte Subjekte (User, Freelancer, ServiceProvider)
            'subjects'          => ['required', 'array', 'min:1'],
            'subjects.*.type'   => ['required', 'string', 'in:user,freelancer,service_provider'],
            'subjects.*.id'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'full_day' => $this->boolean('full_day'),
        ]);
    }
}
