<?php

namespace Tests\Feature\Lang;

use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Mail-Vorlagen kamen fest auf Deutsch; jetzt über __() – mit HasLocalePreference landen sie in
 * der Sprache der Empfänger:in.
 */
final class TranslatedTemplatesTest extends FeatureTestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function locales(): array
    {
        return [
            'deutsch' => ['de', 'Passwort zurücksetzen für', 'Willkommen bei'],
            'englisch' => ['en', 'Reset password for', 'Welcome to'],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function mails_render_in_the_active_locale(string $locale, string $resetHeading, string $welcome): void
    {
        App::setLocale($locale);
        $user = User::factory()->create(['first_name' => 'Lia', 'last_name' => 'Licht']);

        $reset = (string) (new ResetPassword('token'))->toMail($user)->render();
        $imported = view('emails.external_user_imported', [
            'name' => 'Lia',
            'url' => 'https://example.test/login',
            'page_title' => 'Haus',
            'sender_email' => 'info@example.test',
        ])->render();
        $invitation = view('emails.invitations', [
            'page_title' => 'Haus',
            'email' => 'info@example.test',
            'token' => 'abc',
            'invitation' => (object) ['email' => 'neu@example.test'],
        ])->render();

        $this->assertStringContainsString($resetHeading, $reset);
        $this->assertStringContainsString($welcome . ' Haus', $imported);
        $this->assertStringContainsString($locale === 'de' ? 'Registrierung abschließen' : 'Complete registration', $invitation);
    }
}
