<?php

namespace Artwork\Modules\AiAssistant\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/ai/rooms/available (core-ai-aggregates, Entgrenzen P1).
 *
 * Ein Fenster von hoechstens 24 Stunden: `from` + `to` oder `at` + `duration_minutes`.
 */
class IndexAiAvailableRoomsRequest extends FormRequest
{
    public const MAX_WINDOW_MINUTES = 1440;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'at' => ['required_without:from', 'prohibits:from,to', 'date'],
            'duration_minutes' => ['required_with:at', 'integer', 'min:15', 'max:' . self::MAX_WINDOW_MINUTES],
            'from' => ['required_without:at', 'date'],
            'to' => ['required_with:from', 'date', 'after:from'],
            'room_category_id' => ['nullable', 'integer'],
            'area_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->query('from') === null) {
                return;
            }

            if ($this->from()->diffInMinutes($this->to()) > self::MAX_WINDOW_MINUTES) {
                $validator->errors()->add('to', 'Das Zeitfenster darf hoechstens 24 Stunden umfassen.');
            }
        });
    }

    public function from(): CarbonImmutable
    {
        $value = $this->input('at') ?? $this->input('from');

        return CarbonImmutable::parse((string) $value)->setTimezone((string) config('app.timezone'));
    }

    public function to(): CarbonImmutable
    {
        if ($this->input('at') !== null) {
            return $this->from()->addMinutes((int) $this->input('duration_minutes'));
        }

        return CarbonImmutable::parse((string) $this->input('to'))->setTimezone((string) config('app.timezone'));
    }

    public function roomCategoryId(): ?int
    {
        $value = $this->validated('room_category_id');

        return $value === null ? null : (int) $value;
    }

    public function areaId(): ?int
    {
        $value = $this->validated('area_id');

        return $value === null ? null : (int) $value;
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? 200);
    }
}
