<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Http\Requests\UpdateAiAssistantSettingsRequest;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hauseinstellungen des KI-Assistenten (core-modul-settings-policy).
 *
 * Die Rechtepruefung haengt an der Route (`can:change ai assistant settings`) und zusaetzlich am
 * FormRequest. `ai_session_client_id` wird bewusst weder gelesen noch geschrieben — das ist
 * Installationszustand aus `ai:install`, keine Hauseinstellung.
 */
class AiAssistantSettingsController extends Controller
{
    public function __construct(private readonly AiAssistantSettings $settings)
    {
    }

    public function edit(): Response
    {
        return Inertia::render('ToolSettings/AiAssistant/Index', [
            'aiAssistantSettings' => [
                'enabled' => $this->settings->enabled,
                'level_b_enabled' => $this->settings->level_b_enabled,
                'level_c_enabled' => $this->settings->level_c_enabled,
                'transcript_retention_days' => $this->settings->transcript_retention_days,
            ],
            // Kill-Switch des Betreibers. Steht er auf false, laeuft nichts, egal was hier gespeichert
            // ist — die Seite sagt das, statt einen wirkungslosen Schalter anzubieten.
            'instanceEnabled' => (bool) config('ai.enabled'),
        ]);
    }

    public function update(UpdateAiAssistantSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $previousEnabled = $this->settings->enabled;
        $previousLevelC = $this->settings->level_c_enabled;

        $this->settings->enabled = (bool) $validated['enabled'];
        $this->settings->level_b_enabled = (bool) $validated['level_b_enabled'];
        $this->settings->level_c_enabled = (bool) $validated['level_c_enabled'];
        $this->settings->transcript_retention_days = (int) $validated['transcript_retention_days'];
        $this->settings->save();

        $this->logIfReleaseChanged($request, $previousEnabled, $previousLevelC);

        return back()->with('success', __('The settings were saved.'));
    }

    /**
     * Wer den Assistenten oder die Aktionsstufe freischaltet, ist nachvollziehbar (C1 §7).
     * Die Aufbewahrungsdauer und Stufe B sind bewusst nicht protokolliert — sie erweitern keine Rechte.
     */
    private function logIfReleaseChanged(
        UpdateAiAssistantSettingsRequest $request,
        bool $previousEnabled,
        bool $previousLevelC
    ): void {
        $changes = [];

        if ($previousEnabled !== $this->settings->enabled) {
            $changes['enabled'] = ['from' => $previousEnabled, 'to' => $this->settings->enabled];
        }

        if ($previousLevelC !== $this->settings->level_c_enabled) {
            $changes['level_c_enabled'] = ['from' => $previousLevelC, 'to' => $this->settings->level_c_enabled];
        }

        if ($changes === []) {
            return;
        }

        activity()
            ->causedBy($request->user())
            ->withProperties($changes)
            ->log('ai_assistant_settings_updated');
    }
}
