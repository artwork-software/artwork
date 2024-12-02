<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Core\FileHandling\Upload\HandlesFileUpload;
use Artwork\Modules\GeneralSettings\Http\Requests\UploadBrandingGraphicRequest;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ToolSettingsBrandingController extends Controller
{
    use HandlesFileUpload;
    
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
    public function update(UploadBrandingGraphicRequest $request, GeneralSettings $generalSettings): RedirectResponse
    {
        $this->authorize('updateImages', $generalSettings);

        $smallLogo = $request->file('smallLogo');
        $bigLogo = $request->file('bigLogo');
        $banner = $request->file('banner');
        if ($smallLogo) {
            $this->handleFile(ArtworkFileTypes::BRANDING, $smallLogo);
            $generalSettings->small_logo_path = $smallLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($bigLogo) {
            $this->handleFile(ArtworkFileTypes::BRANDING, $bigLogo);
            $generalSettings->big_logo_path = $bigLogo->storePublicly('logo', ['disk' => 'public']);
        }

        if ($banner) {
            $this->handleFile(ArtworkFileTypes::BRANDING, $banner);
            $generalSettings->banner_path = $banner->storePublicly('banner', ['disk' => 'public']);
        }

        $generalSettings->save();

        return Redirect::back()->with('success', __('flash-messages.branding.update'));
    }
}
