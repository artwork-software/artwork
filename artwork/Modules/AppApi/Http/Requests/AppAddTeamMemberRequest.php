<?php

namespace Artwork\Modules\AppApi\Http\Requests;

class AppAddTeamMemberRequest extends AppTeamRightsRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            ...parent::rules(),
        ];
    }
}
