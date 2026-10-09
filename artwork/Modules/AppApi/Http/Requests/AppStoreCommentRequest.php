<?php

namespace Artwork\Modules\AppApi\Http\Requests;

use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AppStoreCommentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:5000'],
            'tab_id' => ['nullable', 'integer', 'exists:project_tabs,id'],
        ];
    }

    /**
     * Comments only in tabs the user may see — like the web
     * (StoreCommentRequest); admins pass through canSeeTab.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('tab_id') || !$this->filled('tab_id')) {
                return;
            }

            $user = $this->user();
            if (
                !$user instanceof User
                || !app(ProjectComponentVisibilityService::class)->canSeeTab($user, $this->integer('tab_id'))
            ) {
                $validator->errors()->add('tab_id', __('You do not have permission to access this project tab.'));
            }
        });
    }
}
