<?php

namespace Tests\Feature\Modules\Invitation;

use Artwork\Core\Mail\MailService;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Neue Konten übernehmen die Sprache, in der die Einladungsseite angezeigt wurde. Vorher griff der
 * Spalten-Default 'de': die Seite war englisch (Browsersprache), Konto und Einstellungen aber deutsch.
 */
final class AcceptInvitationLanguageTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mail::fake() (FeatureTestCase) liefert einen MailFake, an dem der typisierte MailService-Konstruktor scheitert.
        $this->spy(MailService::class);
        // TestCase legt 'en' in die Session; die hätte Vorrang vor dem Accept-Language-Header.
        Session::forget('locale');
    }

    #[Test]
    public function an_english_browser_creates_an_english_account(): void
    {
        $this->acceptInvitation('english@example.test', 'en-US,en;q=0.9');

        $user = User::query()->where('email', 'english@example.test')->firstOrFail();
        $this->assertSame('en', $user->language);

        $this->get(route('dashboard'))->assertSee('<html lang="en">', false);
    }

    #[Test]
    public function a_german_browser_creates_a_german_account(): void
    {
        $this->acceptInvitation('german@example.test', 'de-DE,de;q=0.9');

        $this->assertSame('de', User::query()->where('email', 'german@example.test')->value('language'));
    }

    #[Test]
    public function an_unsupported_browser_language_falls_back_to_the_instance_language(): void
    {
        $this->acceptInvitation('french@example.test', 'fr-FR,fr;q=0.9');

        $this->assertSame(
            config('app.locale'),
            User::query()->where('email', 'french@example.test')->value('language')
        );
    }

    private function acceptInvitation(string $email, string $acceptLanguage): void
    {
        $token = Str::random(20);
        Invitation::factory()->create([
            'email' => $email,
            'token' => Hash::make($token),
            'permissions' => [],
            'roles' => [],
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        $this->withHeader('Accept-Language', $acceptLanguage)
            ->post(route('invitation.accept'), [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => $email,
                'password' => 'Str0ng!Passw0rd#2026',
                'password_confirmation' => 'Str0ng!Passw0rd#2026',
                'token' => $token,
            ])
            ->assertRedirect(route('dashboard'));
    }
}
