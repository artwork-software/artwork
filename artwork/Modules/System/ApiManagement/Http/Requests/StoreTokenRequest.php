<?php

namespace Artwork\Modules\System\ApiManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;

class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Die Rechteprüfung passiert im Controller über die TokenPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            // Mindestens ein Scope: Ein Token ohne Scopes käme durch keine Prüfung der Maschinen-API
            // und wäre damit nutzlos.
            'scopes' => 'required|array|min:1',
            'scopes.*' => ['string', Rule::in(Passport::scopeIds())],
        ];
    }
}
