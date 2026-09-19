<?php

namespace Artwork\Modules\WorkTime\Http\Requests;

use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkTimeChangeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $this->integer('user_id') === $user->id
            && Shift::query()
                ->whereKey($this->integer('shift_id'))
                ->where('craft_id', $this->integer('craft_id'))
                ->whereHas('users', fn (Builder $query): Builder => $query->where('users.id', $user->id))
                ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'request_start_time' => 'required|string|date_format:H:i',
            'request_end_time' => 'required|string|date_format:H:i',
            'shift_id' => 'required|exists:shifts,id',
            'craft_id' => 'required|exists:crafts,id',
            'request_comment' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
