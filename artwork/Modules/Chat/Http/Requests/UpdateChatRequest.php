<?php

namespace Artwork\Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $chat = $this->route('chat');
        $user = $this->user();

        // Check if user is a member of the chat
        if (!$chat->users()->where('users.id', $user->id)->exists()) {
            return false;
        }

        // Only allow updating group chats
        if (!$chat->is_group) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
