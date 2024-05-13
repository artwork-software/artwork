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

        $generalSettings->business_name = $request->get('businessName') ?? '';
        $generalSettings->page_title = $request->get('page_title') ?? '';
        $generalSettings->impressum_link = $request->get('impressumLink') ?? '';
        $generalSettings->privacy_link = $request->get('privacyLink') ?? '';
        $generalSettings->email_footer = $request->get('emailFooter') ?? '';
        $generalSettings->business_email = $request->get('businessEmail') ?? '';

        $generalSettings->save();

        return Redirect::back()->with('success', __('flash-messages.communication_and_legal.update'));
    }
}
