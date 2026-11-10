<?php

namespace Artwork\Modules\Mail\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Mail\Http\Requests\TestMailRequest;
use Artwork\Modules\Mail\Http\Requests\UpdateMailSettingsRequest;
use Artwork\Modules\Mail\Providers\MailSettingsServiceProvider;
use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Artwork\Modules\Mail\Settings\MailSettings;
use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Illuminate\Validation\ValidationException;
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
                'can_manage' => request()->user()->hasRole(RoleEnum::ARTWORK_ADMIN->value),
                // Effektiver Stand der SMTP-Anmeldung, damit ein unvollständiges Paar auffällt.
                'authentication' => [
                    'source' => $this->resolver->authenticationSource(),
                    // Nur für hier gepflegte Benutzernamen — die .env verantwortet der Serverbetrieb.
                    'password_missing' => $this->resolver->authenticationSource() === 'settings'
                        && $this->resolver->password() === null,
                ],
                // Effective fallback values (from .env) to show as UI placeholders.
                // Deliberately without username: the .env account name is itself a
                // sensitive credential detail and must not be shown in the UI.
                'fallback' => [
                    'host' => $this->resolver->hostFallback(),
                    'port' => $this->resolver->portFallback(),
                    'encryption' => $this->resolver->encryptionFallback(),
                    // Wie bei echten Mails: Support-Adresse bzw. system_mail vor MAIL_FROM_ADDRESS.
                    'from_address' => $this->resolver->defaultSenderAddress(),
                    'from_name' => $this->resolver->fromNameFallback(),
                ],
            ],
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $newHost = $this->nullableTrim($validated['host'] ?? null);
        $newPassword = $validated['password'] ?? '';
        $hostChanged = strcasecmp((string) $this->settings->host, (string) $newHost) !== 0;

        // Das gespeicherte Passwort gehört zum bisherigen Server und darf nie an einen neuen Host
        // gehen: bei Hostwechsel mit Benutzername neu verlangen, ohne Benutzername (kein SMTP-AUTH)
        // verwerfen.
        if (
            $hostChanged
            && $newPassword === ''
            && !$request->boolean('clear_password')
            && ($this->settings->password ?? '') !== ''
        ) {
            if ($this->nullableTrim($validated['username'] ?? null) !== null) {
                throw ValidationException::withMessages([
                    'password' => __('Enter the password again when changing the mail server.'),
                ]);
            }

            $this->settings->password = null;
        }

        // Benutzername und Passwort gehören zusammen: Ohne gespeichertes oder neues Passwort
        // würde mit leerem (oder fremdem .env-)Passwort angemeldet → „535 authentication failed“.
        $newUsername = $this->nullableTrim($validated['username'] ?? null);
        $resultingPassword = match (true) {
            $request->boolean('clear_password') => null,
            $newPassword !== '' => $newPassword,
            default => $this->settings->password,
        };
        if (
            $newUsername !== null
            && ($resultingPassword ?? '') === ''
            && !$this->resolver->mayUseFallbackPassword($newHost, $newUsername)
        ) {
            throw ValidationException::withMessages([
                'password' => __('Enter the password for this user name.'),
            ]);
        }

        $port = $validated['port'] ?? null;
        $this->settings->host = $newHost;
        $this->settings->port = $port !== null && $port !== ''
            ? (int) $port
            : null;
        $this->settings->encryption = $this->nullableTrim($validated['encryption'] ?? null);
        $this->settings->username = $newUsername;
        $this->settings->from_address = $this->nullableTrim($validated['from_address'] ?? null);
        $this->settings->from_name = $this->nullableTrim($validated['from_name'] ?? null);

        if ($request->boolean('clear_password')) {
            // Explizites Entfernen: zurück auf den .env-Fallback.
            $this->settings->password = null;
        } elseif (($validated['password'] ?? '') !== '') {
            // Only overwrite the password when a new value is provided; blank keeps the stored one.
            $this->settings->password = $validated['password'];
        }

        $this->settings->save();

        $this->reapplyMailConfig();

        activity('mail_settings')
            ->causedBy($request->user())
            ->log('settings_updated');

        // HandleInertiaRequests shared nur den Session-Key 'success' als Flash.
        return redirect()->back()->with('success', __('Settings have been updated.'));
    }

    public function test(TestMailRequest $request): JsonResponse
    {
        // Dieselbe Konfiguration wie beim echten Versand (inkl. scheme/require_tls und Absender-Kette),
        // sonst sagt der Test nichts über echte Mails aus.
        $this->reapplyMailConfig();

        $recipient = $request->validated('recipient');

        try {
            Mail::mailer('smtp')->raw(
                __('This is a test email from Artwork to verify the mail configuration.'),
                function ($message) use ($recipient): void {
                    $message->to($recipient)->subject(__('Artwork test email'));
                }
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $this->describeTestMailFailure($e),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Test email sent to :recipient.', ['recipient' => $recipient]),
        ]);
    }

    /**
     * Gespeicherte Einstellungen frisch in config('mail.*') übernehmen und den SMTP-Transport neu aufbauen.
     */
    private function reapplyMailConfig(): void
    {
        $provider = app()->getProvider(MailSettingsServiceProvider::class);
        if ($provider instanceof MailSettingsServiceProvider) {
            $provider->applyMailConfig(true);
        }

        Mail::purge('smtp');
    }

    /**
     * Übersetzt Antworten eines erreichten Mailservers in eine verständliche Ursache. Verbindungsfehler
     * bleiben bewusst generisch: Die rohe Exception würde Host/Port-Erreichbarkeit ausplaudern
     * (Port-Scan-Oracle); Details landen nur im Log.
     */
    private function describeTestMailFailure(Throwable $exception): string
    {
        $generic = __('Test email could not be sent. Please check the mail configuration and the log.');

        if (!$exception instanceof TransportExceptionInterface) {
            return $generic;
        }

        $message = strtolower($exception->getMessage());

        return match (true) {
            $exception->getCode() === 535 || str_contains($message, 'failed to authenticate')
                => __('The mail server rejected the login. Please check the user name and password.'),
            str_contains($message, 'sender address rejected')
                => __(
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- wörtlicher Schlüssel, damit BackendTranslationKeysTest ihn prüft
                    'The mail server rejected the sender address :address. Enter a sender address the mail account may send from.',
                    ['address' => (string) $this->resolver->fromAddress()]
                ),
            str_contains($message, 'client host rejected')
                || str_contains($message, 'relay access denied')
                || str_contains($message, 'authentication required')
                || $exception->getCode() === 530
                => __('The mail server only accepts emails after login. Please enter user name and password.'),
            str_contains($message, 'certificate verify failed')
                => __('The TLS certificate of the mail server could not be verified.'),
            default => $generic,
        };
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
