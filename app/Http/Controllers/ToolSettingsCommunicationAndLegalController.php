<?php

namespace App\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ToolSettingsCommunicationAndLegalController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        return Inertia::render('CommunicationAndLegal/Index');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, GeneralSettings $generalSettings): RedirectResponse
    {
        $this->authorize('updateEmailSettings', $generalSettings);

        // Links landen als href im Footer/Mails: nur http(s).
        $request->validate([
            'businessName' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'impressumLink' => ['nullable', 'url:http,https', 'max:2048'],
            'privacyLink' => ['nullable', 'url:http,https', 'max:2048'],
            'emailFooter' => ['nullable', 'string', 'max:5000'],
            'invitationEmail' => ['nullable', 'email', 'max:255'],
            'businessEmail' => ['nullable', 'email', 'max:255'],
            'playingTimeWindowStart' => ['nullable', 'string', 'max:255'],
            'playingTimeWindowEnd' => ['nullable', 'string', 'max:255'],
            'letterheadName' => ['nullable', 'string', 'max:255'],
            'letterheadStreet' => ['nullable', 'string', 'max:255'],
            'letterheadZipCode' => ['nullable', 'string', 'max:255'],
            'letterheadCity' => ['nullable', 'string', 'max:255'],
            'letterheadEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $generalSettings->business_name = $request->get('businessName') ?? '';
        $generalSettings->page_title = $request->get('page_title') ?? '';
        $generalSettings->impressum_link = $request->get('impressumLink') ?? '';
        $generalSettings->privacy_link = $request->get('privacyLink') ?? '';
        $generalSettings->email_footer = $request->get('emailFooter') ?? '';
        $generalSettings->invitation_email = $request->get('invitationEmail') ?? '';
        $generalSettings->business_email = $request->get('businessEmail') ?? '';
        $generalSettings->playing_time_window_start = $request->get('playingTimeWindowStart') ?? '';
        $generalSettings->playing_time_window_end = $request->get('playingTimeWindowEnd') ?? '';
        $generalSettings->letterhead_name = $request->get('letterheadName') ?? '';
        $generalSettings->letterhead_street = $request->get('letterheadStreet') ?? '';
        $generalSettings->letterhead_zip_code = $request->get('letterheadZipCode') ?? '';
        $generalSettings->letterhead_city = $request->get('letterheadCity') ?? '';
        $generalSettings->letterhead_email = $request->get('letterheadEmail') ?? '';
        $generalSettings->save();

        return Redirect::back()->with('success', __('flash-messages.communication_and_legal.update'));
    }
}
