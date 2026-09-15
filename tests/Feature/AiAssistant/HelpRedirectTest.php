<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-help-redirect: `/help/{pfad}` fuehrt ins Wiki, ohne zum offenen Redirect zu werden.
 */
final class HelpRedirectTest extends TestCase
{
    private const BASE = 'https://wiki.example.test/de/docs';

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.wiki_base_url' => self::BASE]);
    }

    #[Test]
    public function it_redirects_to_the_wiki_page(): void
    {
        $this->actingAs($this->germanUser());

        $this->get('/help/shift-plan/committing')
            ->assertRedirect('https://wiki.example.test/de/docs/shift-plan/committing');
    }

    #[Test]
    public function the_bare_path_leads_to_the_wiki_root(): void
    {
        $this->actingAs($this->germanUser());

        $this->get('/help')->assertRedirect('https://wiki.example.test/de/docs');
    }

    /** Die Sprache des Benutzers ersetzt das Sprachsegment der Basis. */
    #[Test]
    public function the_language_of_the_user_selects_the_wiki_language(): void
    {
        $this->actingAs(User::factory()->create(['language' => 'en']));

        $this->get('/help/shift-plan')
            ->assertRedirect('https://wiki.example.test/en/docs/shift-plan');
    }

    /** Punkte sind im Routen-Muster nicht erlaubt — `..` trifft die Route gar nicht. */
    #[Test]
    public function traversal_does_not_match_the_route(): void
    {
        $this->actingAs($this->germanUser());

        $this->get('/help/../evil')->assertNotFound();
        $this->get('/help/evil.html')->assertNotFound();
    }

    /** Kein anonymer Redirect-Dienst: ohne Sitzung geht es zum Login. */
    #[Test]
    public function guests_are_sent_to_the_login(): void
    {
        $this->get('/help/shift-plan')->assertRedirect(route('login'));
    }

    #[Test]
    public function the_redirect_is_not_cached(): void
    {
        $this->actingAs($this->germanUser());

        $response = $this->get('/help/shift-plan');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    private function germanUser(): User
    {
        return User::factory()->create(['language' => 'de']);
    }
}
