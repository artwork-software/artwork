<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Ein Schwung Einladungen aus dem Tab "Team": Personen aus diesem artwork, eine Rolle für alle. */
class InviteTicketingTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Berechtigung liegt auf der Routengruppe (manage ticketing).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1|max:50',
            'user_ids.*' => 'required|integer|distinct|exists:users,id',
            'preset' => ['required', Rule::in(TicketingDraftRules::TEAM_PRESETS)],
        ];
    }
}
