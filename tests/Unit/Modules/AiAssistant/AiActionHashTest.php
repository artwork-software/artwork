<?php

namespace Tests\Unit\Modules\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\AiAssistant\Support\AiActionHashFixtures;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * core-confirm-actions: Kanonisierung des `args_hash` (contracts/action-contract.md).
 *
 * Beide Seiten — artwork-ai beim Anlegen, artwork beim Pruefen — muessen bitgleich rechnen.
 * Die Faelle halten die Regel fest: Schluesselreihenfolge egal, Listenreihenfolge bedeutsam.
 */
final class AiActionHashTest extends TestCase
{
    #[Test]
    public function theKeyOrderDoesNotChangeTheHash(): void
    {
        $first = ['project_id' => 7, 'series_spec' => ['to' => '2026-09-28', 'from' => '2026-09-14']];
        $second = ['series_spec' => ['from' => '2026-09-14', 'to' => '2026-09-28'], 'project_id' => 7];

        $this->assertSame(AiActionHash::of($first), AiActionHash::of($second));
    }

    #[Test]
    public function theListOrderChangesTheHash(): void
    {
        $this->assertNotSame(
            AiActionHash::of(['ids' => [1, 2]]),
            AiActionHash::of(['ids' => [2, 1]])
        );
    }

    #[Test]
    public function theHashIsLowercaseHexOfTheCanonicalForm(): void
    {
        $args = ['b' => 1, 'a' => ['z' => true, 'y' => null]];

        $this->assertSame(
            hash('sha256', '{"a":{"y":null,"z":true},"b":1}'),
            AiActionHash::of($args)
        );
    }

    #[Test]
    public function slashesAndUmlautsStayUnescaped(): void
    {
        $this->assertSame(
            hash('sha256', '{"name":"Haus/Bühne"}'),
            AiActionHash::of(['name' => 'Haus/Bühne'])
        );
    }

    #[Test]
    public function aWrongHashDoesNotMatch(): void
    {
        $args = ['ids' => [1]];

        $this->assertTrue(AiActionHash::matches($args, AiActionHash::of($args)));
        $this->assertFalse(AiActionHash::matches($args, str_repeat('0', 64)));
    }

    /**
     * Referenzfixtures des Contracts (`contracts/fixtures/args-hash/*.json`). Sie liegen im Repo,
     * weil sie ein erzeugtes Contract-Artefakt dieser Seite sind — wie `openapi.json`. Der Test
     * wird deshalb nie uebersprungen; erzeugt werden die Dateien mit `ai:export-action-fixtures`.
     */
    #[Test]
    public function theContractFixturesProduceTheDocumentedHashes(): void
    {
        $directory = base_path(AiActionHashFixtures::DIRECTORY);
        $files = glob($directory . '/*.json') ?: [];

        $expected = array_keys(AiActionHashFixtures::cases());
        $found = array_map(static fn (string $file): string => basename($file, '.json'), $files);
        sort($expected);
        sort($found);

        $this->assertSame($expected, $found, 'Die vier Fixture-Namen des Contracts muessen vorhanden sein.');

        foreach ($files as $file) {
            /** @var array{args: array<string, mixed>, hash: string} $fixture */
            $fixture = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame(strtolower($fixture['hash']), AiActionHash::of($fixture['args']), basename($file));
        }
    }

    /**
     * Die KI-Instanz liest dieselben Dateien aus dem Spec-Workspace. Liegt er lokal gemountet
     * daneben, muessen beide Staende byte-gleich sein; in der CI gibt es ihn nicht.
     */
    #[Test]
    public function theWorkspaceCopyIsIdentical(): void
    {
        $workspace = base_path('specs/' . AiActionHashFixtures::DIRECTORY);

        if (!is_dir($workspace)) {
            $this->markTestSkipped('Spec-Workspace nicht gemountet.');
        }

        foreach (array_keys(AiActionHashFixtures::cases()) as $name) {
            $this->assertFileEquals(
                base_path(AiActionHashFixtures::DIRECTORY . '/' . $name . '.json'),
                $workspace . '/' . $name . '.json',
                $name
            );
        }
    }
}
