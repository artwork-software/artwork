<?php

namespace Artwork\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Der Zugang zum Testshop: so einfach, dass er sich weitersagen lässt. */
class ShopAccessRequest extends FormRequest
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
            'user' => 'required|string|min:3|max:60',
            'password' => 'required|string|min:6|max:100',
        ];
    }
}
