<?php

namespace Tests\Browser;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Test;
use Tests\DuskTestCase;

/**
 * core-ki-bar-stub-test: Rauchtest der KI-Bar gegen den Entwicklungs-Stub (Konzept C4 §9, §10).
 *
 * Die Anwendung unter Test laeuft unter DUSK_APP_URL mit `AI_ENDPOINT_URL=/ai/dev-stub`; das
 * Skript waehlt das erste Wort der Nachricht (core-ki-bar-ui §2).
 *
 * Kein Truncate und kein Refresh der Datenbank: der Test legt Benutzer und Gespraech selbst an
 * und raeumt beides in tearDown() wieder ab, die Haus-Einstellung wird auf ihren vorherigen Wert
 * zurueckgedreht.
 */
final class AiAssistantBarTest extends DuskTestCase
{
    private ?User $user = null;

    private ?bool $previousEnabled = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Der Stub existiert nur in `local`. Laeuft die Anwendung unter Test ohne ihn — in
        // `production`, oder wenn DUSK_APP_URL auf eine fremde Instanz zeigt —, wird uebersprungen
        // statt rot (Akzeptanzkriterium des Briefs).
        $this->skipWithoutStub();

        $settings = app(AiAssistantSettings::class);
        $this->previousEnabled = $settings->enabled;

        if (!$settings->enabled) {
            $settings->enabled = true;
            $settings->save();
        }

        // POST /ai/session braucht den ai-session-Client (core-session-token). Der Befehl ist
        // idempotent; in der Testdatenbank existiert der Client sonst nicht.
        $this->artisan('ai:install')->assertSuccessful();

        // Die Oberflaeche wird auf Deutsch geprueft; die Sprache haengt am Benutzer.
        $this->user = User::factory()->withAiAccess()->create(['language' => 'de']);
        UserCalendarSettings::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        AiConversation::query()->where('user_id', $this->user?->id)->delete();
        $this->user?->forceDelete();

        if ($this->previousEnabled !== null) {
            $settings = app(AiAssistantSettings::class);
            $settings->enabled = $this->previousEnabled;
            $settings->save();
        }

        parent::tearDown();
    }

    /** 1. Oeffnen per Shortcut, Antwort streamt, Markdown mit relativem Link. */
    #[Test]
    public function the_bar_opens_with_the_shortcut_and_streams_a_markdown_answer(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->user)
                ->visit('/dashboard')
                ->waitFor('[data-ai-open]');

            // Shortcut als Dokument-Ereignis: Dusk stellt jedem Selektor 'body' voran ('body body'
            // gibt es nicht), und weder das Inhalts-div noch der Seitenleisten-Knopf nehmen im
            // Headless-Chromium Tasten an ("element not interactable"). Geprueft wird damit genau
            // das, worauf die Bar hoert: ein keydown mit ctrlKey am Dokument.
            $browser->script(
                "document.dispatchEvent(new KeyboardEvent('keydown',"
                . " {key: 'k', ctrlKey: true, bubbles: true, cancelable: true}))"
            );

            $browser->waitFor('[data-ai-bar]')
                ->type('[data-ai-input]', 'ok Was steht an?')
                ->click('[data-ai-send]')
                ->waitForText('Naechste Schritte')
                ->waitUntilMissing('[data-ai-streaming]');

            $html = $this->answerHtml($browser);

            $this->assertStringContainsString('<a href="/shifts/plan">', $html);
            $this->assertStringContainsString('<ul>', $html);
            $this->assertStringContainsString('<strong>', $html);
        });
    }

    /** 2. Absolute Ziele erscheinen als Text, nicht als Link. */
    #[Test]
    public function absolute_links_are_rendered_as_text(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'absolute Zeig mir was')
                ->waitForText('Externe Quelle');

            $html = $this->answerHtml($browser);
            $hrefs = $browser->script(
                "return Array.from(document.querySelectorAll('[data-ai-answer] a')).map((a) => a.getAttribute('href'))"
            )[0];

            // Der relative Treffer im selben Skript bleibt ein Link — nur die absoluten nicht.
            $this->assertSame(['/projects'], $hrefs);
            $this->assertStringContainsString('https://artwork.art', $html);
            $this->assertStringContainsString('//evil.example/steal', $html);
        });
    }

    /** 3. Rohes HTML aus der Antwort wird escaped, nicht gerendert. */
    #[Test]
    public function raw_html_from_the_model_is_escaped(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'html Zeig mir was')
                ->waitForText('Rohes HTML im Antworttext');

            $html = $this->answerHtml($browser);
            $elements = $browser->script(
                "return document.querySelectorAll('[data-ai-answer] script, [data-ai-answer] b, "
                . "[data-ai-answer] img').length"
            )[0];

            $this->assertSame(0, $elements);
            $this->assertStringContainsString('&lt;script&gt;', $html);
        });
    }

    /** 4. Ein laufender Turn laesst sich abbrechen; danach ist die Bar wieder bedienbar. */
    #[Test]
    public function a_running_turn_can_be_cancelled(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'slow Bitte langsam')
                ->waitFor('[data-ai-streaming]')
                ->click('[data-ai-cancel]')
                ->waitUntilMissing('[data-ai-streaming]')
                ->waitForText('Abgebrochen');

            $this->assertTrue($browser->script('return !document.querySelector("[data-ai-input]").disabled')[0]);
        });
    }

    /** 5. Nach `token_expired` wird genau einmal wiederholt — mit derselben client_turn_id. */
    #[Test]
    public function an_expired_token_leads_to_exactly_one_retry_with_the_same_turn_id(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'expired Frag mal')
                ->waitFor('[data-ai-error]')
                ->waitUntilMissing('[data-ai-streaming]');

            $turnId = $browser->attribute('[data-ai-turn]', 'data-ai-turn');

            $this->assertNotEmpty($turnId);
            $this->assertSame(2, $this->stubCalls($turnId));
        });
    }

    /** 6. `tenant_disabled` sperrt die Eingabe und zeigt den Hinweis. */
    #[Test]
    public function a_disabled_tenant_locks_the_input(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'disabled Frag mal')
                ->waitFor('[data-ai-error]')
                ->waitForText('für diese Instanz abgeschaltet');

            $this->assertTrue($browser->script('return document.querySelector("[data-ai-input]").disabled')[0]);
        });
    }

    /** 7. Der Confirm-Vertrag ist sichtbar, die Knoepfe bleiben gesperrt (W2 fuellt sie). */
    /**
     * Seit core-ki-bar-confirm sind die Knoepfe scharf; der Ablauf selbst steht in
     * `AiActionConfirmTest`. Hier bleibt die Vorschau samt Konflikten und Frist.
     */
    #[Test]
    public function the_confirm_dialog_shows_the_preview_and_offers_both_buttons(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'confirm Leg das an')
                ->waitFor('[data-ai-confirm]')
                ->assertSeeIn('[data-ai-confirm]', 'Probe am 20.09.')
                ->assertSeeIn('[data-ai-confirm]', 'Studio 1 ist von 08:00 bis 09:00 belegt')
                ->assertVisible('[data-ai-confirm-expiry]');

            $enabled = $browser->script(
                "return Array.from(document.querySelectorAll('[data-ai-confirm] button'))"
                . '.every((button) => !button.disabled)'
            )[0];

            $this->assertTrue($enabled);
        });
    }

    /**
     * 9. Auswahl-Liste (core-ki-bar-choice, Protokoll 1.1): erscheint trotz eines unbekannten
     * Events im Skript, der Filter grenzt ein, ein Klick sendet das Label als normale Nachricht.
     */
    #[Test]
    public function the_choice_list_filters_and_sends_the_chosen_label(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'choice Welcher Raum?')
                ->waitFor('[data-ai-choice]')
                ->waitUntilMissing('[data-ai-streaming]');

            // Das unbekannte Event vor der Frage hat die Bar nicht aus dem Tritt gebracht: die
            // Liste steht vollstaendig da, es gibt keinen Fehlerzustand.
            $browser->assertMissing('[data-ai-error]')
                ->assertSeeIn('[data-ai-choice]', 'Studio 1')
                ->assertSeeIn('[data-ai-choice]', 'Werkstatt');

            $this->assertSame(3, $this->choiceOptions($browser));

            // `awaiting_input`: die Eingabe bleibt offen, tippen ist weiter moeglich.
            $this->assertTrue($browser->script('return !document.querySelector("[data-ai-input]").disabled')[0]);

            $browser->type('[data-ai-choice-filter]', 'stud');
            $browser->waitUntil('document.querySelectorAll("[data-ai-choice-option]").length === 2');

            $this->assertSame(2, $this->choiceOptions($browser));

            $browser->click('[data-ai-choice-option]')
                ->waitUntilMissing('[data-ai-choice]')
                ->waitUntilMissing('[data-ai-streaming]');

            // Die Wahl ist als ganz normale Nachricht rausgegangen — mit dem Label als Text.
            $questions = $browser->script(
                "return Array.from(document.querySelectorAll('[data-ai-turn] p')).map((p) => p.textContent.trim())"
            )[0];

            $this->assertContains('Studio 1', $questions);
        });
    }

    /**
     * 10. Gekuerzte Liste: Hinweis sichtbar, und der unbekannte `done.reason` am Ende des Skripts
     * laesst die Bar trotzdem sauber auf `idle` zurueckfallen (Protokoll 1.1).
     */
    #[Test]
    public function a_truncated_choice_shows_the_hint_and_survives_an_unknown_done_reason(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'choice-truncated Welcher Raum?')
                ->waitFor('[data-ai-choice-truncated]')
                ->waitUntilMissing('[data-ai-streaming]')
                ->assertSeeIn('[data-ai-choice-truncated]', 'enger eingrenzen')
                ->assertMissing('[data-ai-error]');

            $this->assertTrue($browser->script('return !document.querySelector("[data-ai-input]").disabled')[0]);

            // Kein Fehler heisst auch: der naechste Turn laesst sich absenden.
            $browser->type('[data-ai-input]', 'ok Und weiter?')
                ->click('[data-ai-send]')
                ->waitForText('Naechste Schritte');
        });
    }

    /** 11. Abbrechen an der Liste sendet nichts. */
    #[Test]
    public function dismissing_the_choice_list_sends_nothing(): void
    {
        $this->browse(function (Browser $browser): void {
            $this->ask($browser, 'choice Welcher Raum?')
                ->waitFor('[data-ai-choice]')
                ->waitUntilMissing('[data-ai-streaming]')
                ->click('[data-ai-choice-dismiss]')
                ->waitUntilMissing('[data-ai-choice]');

            // Genau ein Turn: der eigene. Ein Klick auf „Abbrechen" darf keinen zweiten ausloesen.
            $turns = $browser->script("return document.querySelectorAll('[data-ai-turn]').length")[0];

            $this->assertSame(1, $turns);
        });
    }

    /** 8. Panel: Gespraech erscheint, Loeschen fragt nach und entfernt es. */
    #[Test]
    public function the_panel_lists_and_deletes_conversations(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->user->id,
            'title' => 'Frage von gestern',
            'status' => AiConversation::STATUS_ACTIVE,
            'last_turn_at' => now()->subDay(),
        ]);

        $this->browse(function (Browser $browser) use ($conversation): void {
            $this->openBar($browser)
                ->click('[data-ai-panel-toggle]')
                ->waitForText('Frage von gestern')
                ->click('[data-ai-conversation-delete]')
                ->waitForText('Gespräch löschen?')
                ->click('[data-ai-conversation-delete-confirm]')
                ->waitUntilMissingText('Frage von gestern');

            $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        });
    }

    private function choiceOptions(Browser $browser): int
    {
        return (int) $browser->script("return document.querySelectorAll('[data-ai-choice-option]').length")[0];
    }

    private function skipWithoutStub(): void
    {
        $probe = (string) Str::uuid();

        try {
            $response = Http::timeout(5)->get($this->baseUrl() . '/ai/dev-stub/calls/' . $probe);
        } catch (ConnectionException $exception) {
            $this->markTestSkipped(
                'Anwendung unter Test nicht erreichbar (' . $this->baseUrl() . '): ' . $exception->getMessage()
            );
        }

        if (!$response->successful()) {
            $this->markTestSkipped(
                'Kein Entwicklungs-Stub unter ' . $this->baseUrl() . ' (HTTP ' . $response->status() . '). '
                . 'Die Stub-Routen gibt es nur in der Umgebung local.'
            );
        }
    }

    private function openBar(Browser $browser): Browser
    {
        return $browser->loginAs($this->user)
            ->visit('/dashboard')
            ->waitFor('[data-ai-open]')
            ->click('[data-ai-open]')
            ->waitFor('[data-ai-bar]');
    }

    private function ask(Browser $browser, string $message): Browser
    {
        return $this->openBar($browser)
            ->type('[data-ai-input]', $message)
            ->click('[data-ai-send]');
    }

    private function answerHtml(Browser $browser): string
    {
        return (string) $browser->script("return document.querySelector('[data-ai-answer]').innerHTML")[0];
    }

    private function stubCalls(string $clientTurnId): int
    {
        return (int) Http::get($this->baseUrl() . '/ai/dev-stub/calls/' . $clientTurnId)->json('count');
    }
}
