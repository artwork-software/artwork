<?php

namespace Artwork\Modules\User\Http\Controllers;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserEmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Bestätigungslink aus ConfirmEmailChangeNotification. Signiert und zeitlich begrenzt (Route-Middleware
 * signed); einlösen kann ihn nur die angemeldete Person, deren Adresse vorgemerkt ist.
 */
class UserEmailChangeController
{
    public function __construct(private readonly UserEmailChangeService $emailChangeService)
    {
    }

    public function confirm(Request $request, User $user, string $hash): RedirectResponse
    {
        abort_unless($request->user()?->is($user), 403);

        $redirect = redirect()->route('user.edit.info', $user);

        try {
            $this->emailChangeService->confirmSelfChange($user, $hash);
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors());
        }

        return $redirect->with('success', __('Your new email address has been confirmed.'));
    }
}
