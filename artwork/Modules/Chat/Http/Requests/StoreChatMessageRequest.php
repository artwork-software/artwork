<?php

namespace Artwork\Modules\Chat\Http\Requests;

use Artwork\Modules\Chat\Models\Chat;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Nur Mitglieder des Chats dürfen Nachrichten anlegen (ChatPolicy::view;
     * Artwork-Admins passieren über Gate::before).
     */
    public function authorize(): bool
    {
        $chat = Chat::find($this->input('chat_id'));

        return $chat !== null && (bool) $this->user()?->can('view', $chat);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chat_id' => ['required', 'integer', 'exists:chats,id'],
            'message' => ['required', 'string', 'max:10000'],
        ];
    }
}
