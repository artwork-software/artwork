<?php

namespace Artwork\Modules\Mail\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Mail\Http\Requests\TestMailRequest;
use Artwork\Modules\Mail\Http\Requests\UpdateMailSettingsRequest;
use Artwork\Modules\Mail\Providers\MailSettingsServiceProvider;
use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Artwork\Modules\Mail\Settings\MailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MailSettingsController extends Controller
{
    public function __construct(
        private readonly MailSettings $settings,
        private readonly MailSettingsResolver $resolver,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('view', GeneralSettings::class);

        return Inertia::render('ToolSettings/Mail/Index', [
            'mailSettings' => [
                // Stored override values (empty = use .env fallback). Password is never exposed.
                'host' => $this->settings->host,
                'port' => $this->settings->port,
                'encryption' => $this->settings->encryption,
                'username' => $this->settings->username,
                'from_address' => $this->settings->from_address,
                'from_name' => $this->settings->from_name,
                'has_password' => ($this->settings->password ?? '') !== '',
                // Effective fallback values (from .env) to show as UI placeholders.
                'fallback' => [
                    'host' => $this->resolver->hostFallback(),
                    'port' => $this->resolver->portFallback(),
                    'encryption' => $this->resolver->encryptionFallback(),
                    'username' => $this->resolver->usernameFallback(),
                    'from_address' => $this->resolver->fromAddressFallback(),
                    'from_name' => $this->resolver->fromNameFallback(),
                ],
            ],
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->host = $this->nullableTrim($validated['host'] ?? null);
        $this->settings->port = $validated['port'] !== null && $validated['port'] !== ''
            ? (int) $validated['port']
            : null;
        $this->settings->encryption = $this->nullableTrim($validated['encryption'] ?? null);
        $this->settings->username = $this->nullableTrim($validated['username'] ?? null);
        $this->settings->from_address = $this->nullableTrim($validated['from_address'] ?? null);
        $this->settings->from_name = $this->nullableTrim($validated['from_name'] ?? null);

        // Only overwrite the password when a new value is provided; blank keeps the stored one.
        if (($validated['password'] ?? '') !== '') {
            $this->settings->password = $validated['password'];
        }

        $this->settings->save();

        Cache::forget(MailSettingsServiceProvider::CACHE_KEY);

        activity('mail_settings')
            ->causedBy($request->user())
            ->log('settings_updated');

        return redirect()->back()->with('status', __('Settings have been updated.'));
    }

    public function test(TestMailRequest $request): JsonResponse
    {
        $this->authorize('view', GeneralSettings::class);

        // Rebuild the effective config from the stored settings and force a fresh transport.
        Cache::forget(MailSettingsServiceProvider::CACHE_KEY);
        $config = $this->resolver->effectiveConfigArray();

        Config::set('mail.mailers.smtp.host', $config['host']);
        Config::set('mail.mailers.smtp.port', $config['port']);
        Config::set('mail.mailers.smtp.encryption', $config['encryption']);
        Config::set('mail.mailers.smtp.username', $config['username']);
        Config::set('mail.mailers.smtp.password', $config['password']);
        if ($config['from_address'] !== null && $config['from_address'] !== '') {
            Config::set('mail.from.address', $config['from_address']);
        }
        if ($config['from_name'] !== null && $config['from_name'] !== '') {
            Config::set('mail.from.name', $config['from_name']);
        }

        Mail::purge('smtp');

        $recipient = $request->validated('recipient');

        try {
            Mail::mailer('smtp')->raw(
                __('This is a test email from Artwork to verify the mail configuration.'),
                function ($message) use ($recipient): void {
                    $message->to($recipient)->subject(__('Artwork test email'));
                }
            );
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Test email sent to :recipient.', ['recipient' => $recipient]),
        ]);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
