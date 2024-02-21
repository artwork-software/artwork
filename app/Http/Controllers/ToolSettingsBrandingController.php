<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ToolSettingsBrandingController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        return Inertia::render('Branding/Index');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, GeneralSettings $generalSettings): RedirectResponse
    {
        $this->authorize('updateImages', $generalSettings);

        $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');

        if ($smallLogo) {
            $generalSettings->small_logo_path = $smallLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($bigLogo) {
            $generalSettings->big_logo_path = $bigLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($banner) {
            $generalSettings->banner_path = $banner->storePublicly('banner', ['disk' => 'public']);
        }

        $generalSettings->save();

        return Redirect::back()->with('success', 'Branding erfolgreich aktualisiert.');
    }
}
