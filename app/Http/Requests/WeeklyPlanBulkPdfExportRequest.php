<?php

namespace App\Http\Requests;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Policies\UserPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sammel-PDF "Wochenplan je Person": gleiche Sichtrechte wie die Personalübersicht des Schichtplans
 * (Dienstplan sehen) und wie fremde Einsatzpläne (UserPolicy::canViewForeignRoster).
 */
class WeeklyPlanBulkPdfExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->can(PermissionEnum::VIEW_SHIFT_PLAN->value)
            && UserPolicy::canViewForeignRoster($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            // Ein beliebiger Tag der gewünschten Kalenderwoche
            'week' => ['required', 'date_format:Y-m-d'],
            'workers' => ['sometimes', 'array', 'max:150'],
            'workers.*.type' => ['required', 'string', Rule::in(['user', 'freelancer', 'service_provider'])],
            'workers.*.id' => ['required', 'integer'],
            'worker_types' => ['required', 'array', 'min:1'],
            'worker_types.*' => ['string', Rule::in(['user', 'freelancer', 'service_provider'])],
            'craft_ids' => ['sometimes', 'nullable', 'array'],
            'craft_ids.*' => ['integer', Rule::exists('crafts', 'id')],
            'include_empty_workers' => ['required', 'boolean'],
            'paperSize' => ['required', Rule::in(['a3', 'a4'])],
            'paperOrientation' => ['required', Rule::in(['landscape', 'portrait'])],
            'dpi' => ['required', 'integer', 'between:72,300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Der Schichtplan-Export schickt den Zeitraum als start/end: die Woche des Startdatums zählt
            'week' => $this->input('week', $this->input('start')),
            'worker_types' => $this->input('worker_types', ['user', 'freelancer', 'service_provider']),
            'include_empty_workers' => $this->boolean('include_empty_workers'),
            'paperSize' => $this->input('paperSize', 'a4'),
            'paperOrientation' => $this->input('paperOrientation', 'landscape'),
            'dpi' => $this->integer('dpi', 96),
        ]);
    }
}
