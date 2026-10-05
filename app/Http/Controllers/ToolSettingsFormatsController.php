<?php

namespace App\Http\Controllers;

use Artwork\Modules\GeneralSettings\Models\FormatSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ToolSettingsFormatsController extends Controller
{
    public function index(FormatSettings $settings): Response
    {
        return Inertia::render('ToolSettings/Formats/Index', [
            'formatSettings' => [
                'number_locale' => $settings->number_locale,
                'currency' => $settings->currency,
                'date_format' => $settings->date_format,
            ],
            'numberLocales' => FormatSettings::NUMBER_LOCALES,
            'currencies' => FormatSettings::CURRENCIES,
            'dateFormats' => FormatSettings::DATE_FORMATS,
        ]);
    }

    public function update(Request $request, FormatSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'number_locale' => ['required', 'string', Rule::in(FormatSettings::NUMBER_LOCALES)],
            'currency' => ['required', 'string', Rule::in(FormatSettings::CURRENCIES)],
            'date_format' => ['required', 'string', Rule::in(FormatSettings::DATE_FORMATS)],
        ]);

        $settings->fill($validated)->save();

        return back()->with('success', __('Regional formats saved'));
    }
}
