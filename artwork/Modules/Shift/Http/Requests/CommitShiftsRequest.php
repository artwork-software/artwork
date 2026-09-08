<?php

namespace Artwork\Modules\Shift\Http\Requests;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Shift\Rules\IsoWeekExists;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Direkte Festschreibung einer KW (shifts.commit): week_number/year plus Gewerke als craft_ids
 * (Mehrfachauswahl) oder einzelnes craft_id (Altbestand). Nicht-Admins dürfen nur Gewerke
 * festschreiben, die sie planen dürfen (CraftScopeService) — fremde Gewerke → 422.
 * Die Rechteprüfung (can commit shifts) hängt an der Route.
 */
class CommitShiftsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // KW 53 nur in 53-Wochen-Jahren (IsoWeekExists) — sonst würde die Festschreibung still
            // auf die letzte KW gedeckelt (HelperService) und die falsche Woche treffen.
            'week_number' => ['required', 'integer', 'min:1', 'max:53', new IsoWeekExists('year')],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'craft_ids' => ['required_without:craft_id', 'nullable', 'array', 'max:100'],
            'craft_ids.*' => ['integer', 'exists:crafts,id'],
            'craft_id' => ['required_without:craft_ids', 'nullable', 'integer', 'exists:crafts,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $craftIds = $this->craftIds();
            if ($craftIds === []) {
                $validator->errors()->add('craft_ids', __('Please select at least one craft.'));

                return;
            }

            /** @var User|null $user */
            $user = $this->user();
            if ($user === null) {
                return;
            }

            $forbidden = app(CraftScopeService::class)->forbiddenCraftIds($user, $craftIds);
            if ($forbidden === []) {
                return;
            }

            $names = Craft::query()->whereKey($forbidden)->orderBy('name')->pluck('name')->all();
            $validator->errors()->add(
                'craft_ids',
                __('You are not allowed to commit this craft.') . ($names !== [] ? ' (' . implode(', ', $names) . ')' : '')
            );
        });
    }

    /**
     * @return array<int, int>
     */
    public function craftIds(): array
    {
        $ids = $this->input('craft_ids');
        if (!is_array($ids) || $ids === []) {
            $ids = $this->filled('craft_id') ? [$this->input('craft_id')] : [];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    public function weekNumber(): int
    {
        return (int) $this->input('week_number');
    }

    public function year(): int
    {
        return (int) $this->input('year');
    }
}
