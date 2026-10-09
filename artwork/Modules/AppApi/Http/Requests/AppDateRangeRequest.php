<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared date-range contract of the app list endpoints (calendar, shift
 * plan, shift list): an optional Y-m-d start/end pair defaulting to the
 * current week, capped at a maximum range.
 */
class AppDateRangeRequest extends FormRequest
{
    // The app aborts requests after 10s — keep the range small enough to stay fast.
    private const MAX_RANGE_DAYS = 62;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start' => ['nullable', 'required_with:end', 'date_format:Y-m-d'],
            'end' => ['nullable', 'required_with:start', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || !$this->filled('start') || !$this->filled('end')) {
                    return;
                }

                $rangeDays = $this->startDate()->diffInDays($this->endDate()->startOfDay()) + 1;
                if ($rangeDays > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add(
                        'end',
                        sprintf('The requested range must not exceed %d days.', self::MAX_RANGE_DAYS),
                    );
                }
            },
        ];
    }

    public function startDate(): Carbon
    {
        return $this->filled('start')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->input('start'))->startOfDay()
            : Carbon::now()->startOfWeek();
    }

    public function endDate(): Carbon
    {
        return $this->filled('end')
            ? Carbon::createFromFormat('Y-m-d', (string) $this->input('end'))->endOfDay()
            : Carbon::now()->endOfWeek();
    }
}
