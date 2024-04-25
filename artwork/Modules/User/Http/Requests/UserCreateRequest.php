<?php

namespace Artwork\Modules\User\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UserCreateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'phone_number' => ['nullable', 'string', 'max:15'],
            'position' => ['required', 'string', 'max:255'],
            'business' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userData(): array
    {
        return [
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $this->input('email'),
            'phone_number' => $this->input('phone_number'),
            'password' => Hash::make($this->input('password')),
            'position' => $this->input('position'),
            'business' => $this->input('business'),
            'description' => $this->input('description'),
            'opened_checklists' => [],
            'opened_areas' => []
        ];
    }
}
