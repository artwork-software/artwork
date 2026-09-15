<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\DevStubScripts;
use Artwork\Modules\AiAssistant\Support\SseEventSchemaValidator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-ki-bar-ui: Jedes Event jedes Stub-Skripts muss seinem JSON-Schema entsprechen
 * (contracts/sse-events/, Konzept 0.3 §4–5).
 *
 * Die Schemata liegen im Spec-Workspace, nicht in diesem Repository. Fehlt das Verzeichnis
 * (CI, Checkout ohne Workspace), wird der Test uebersprungen statt rot — die Alternative waere,
 * Spec-Text ins Repo zu kopieren (README §2 Regel 4).
 */
final class AiDevStubScriptsTest extends TestCase
{
    private SseEventSchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = SseEventSchemaValidator::fromConfig();

        if (!$this->validator->available()) {
            $this->markTestSkipped(
                'SSE-Schemata nicht vorhanden (' . $this->validator->schemaPath() . '). '
                . 'Pfad ueber AI_DEV_STUB_SCHEMA_PATH setzen oder specs/ einhaengen.'
            );
        }
    }

    #[Test]
    #[DataProvider('scriptKeywords')]
    public function every_event_of_every_script_matches_its_schema(string $keyword): void
    {
        $events = DevStubScripts::build($keyword, (string) Str::uuid(), (string) Str::uuid());

        $this->assertNotEmpty($events, "Skript {$keyword} ist leer");

        foreach ($events as $index => $event) {
            $errors = $this->validator->errorsFor($event['event'], $event['data']);

            $this->assertSame(
                [],
                $errors,
                "Skript {$keyword}, Event {$index} ({$event['event']}): " . implode('; ', $errors)
            );
        }
    }

    #[Test]
    #[DataProvider('scriptKeywords')]
    public function every_script_starts_with_meta_and_ends_with_done(string $keyword): void
    {
        $events = DevStubScripts::build($keyword, (string) Str::uuid(), (string) Str::uuid());

        $this->assertSame('meta', $events[0]['event']);
        $this->assertSame(1, $events[0]['data']['protocol']);
        $this->assertSame('done', $events[array_key_last($events)]['event']);
    }

    #[Test]
    public function the_confirm_execution_script_matches_its_schema(): void
    {
        $events = DevStubScripts::buildConfirmExecution((string) Str::uuid(), (string) Str::uuid());

        foreach ($events as $event) {
            $this->assertSame([], $this->validator->errorsFor($event['event'], $event['data']));
        }
    }

    /** Der Brief nennt 13 Skripte; laeuft die Liste auseinander, faellt es hier auf. */
    #[Test]
    public function the_stub_offers_the_thirteen_scripts_from_the_brief(): void
    {
        $expected = [
            'ok', 'tools', 'slow', 'expired', 'rate', 'budget', 'disabled',
            'provider', 'transcript', 'internal', 'confirm', 'absolute', 'html',
        ];

        $actual = DevStubScripts::keywords();
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function the_first_word_of_the_message_selects_the_script(): void
    {
        $this->assertSame('tools', DevStubScripts::resolveKeyword('tools bitte die Schichten'));
        $this->assertSame('rate', DevStubScripts::resolveKeyword('  RATE  '));
        $this->assertSame('ok', DevStubScripts::resolveKeyword('Wann ist die naechste Probe?'));
        $this->assertSame('ok', DevStubScripts::resolveKeyword(''));
        $this->assertSame('ok', DevStubScripts::resolveKeyword(null));
    }

    /** @return array<string, array{0: string}> */
    public static function scriptKeywords(): array
    {
        return array_reduce(
            DevStubScripts::keywords(),
            static function (array $carry, string $keyword): array {
                $carry[$keyword] = [$keyword];

                return $carry;
            },
            []
        );
    }
}
