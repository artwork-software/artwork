<?php

namespace App\Http\Requests;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\Invitation;
use Illuminate\Support\Facades\Hash;

class AcceptInvitationRequest extends UserCreateRequest
{
    use PasswordValidationRules;

    public function authorize()
    {
        $invitation = Invitation::query()
            ->where('email', $this->request->get('email'))
            ->firstOrFail();

        return Hash::check($this->request->get('token'), $invitation->token);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'exists:invitations'],
            'password' => $this->passwordRules(),
            'phone_number' => ['nullable', 'string', 'max:15'],
            'position' => ['required', 'string', 'max:255'],
            'business' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:5000'],
            'token' => ['required', 'string', 'min:20', 'max:20'],
        ];
    }
}
