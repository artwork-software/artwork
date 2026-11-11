<?php

namespace Artwork\Modules\AiAssistant\Support;

use JsonSchema\Validator;
use RuntimeException;

/**
 * Validiert Stub-Events gegen die JSON-Schemata des SSE-Protokolls (contracts/sse-events/).
 *
 * Die Schemata gehoeren zum Spec-Workspace und liegen nicht in diesem Repository; lokal zeigt
 * `ai.dev_stub.schema_path` ueber den Symlink `specs/` darauf. Fehlt das Verzeichnis (CI, frische
 * Checkouts ohne Workspace), meldet available() false — der Stub laeuft dann ohne Validierung und
 * der zugehoerige Test wird uebersprungen.
 */
final class SseEventSchemaValidator
{
    public function __construct(private readonly string $schemaPath)
    {
    }

    public static function fromConfig(): self
    {
        return new self((string) config('ai.dev_stub.schema_path'));
    }

    public function available(): bool
    {
        return is_dir($this->schemaPath);
    }

    public function schemaPath(): string
    {
        return $this->schemaPath;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string> leere Liste = gueltig
     */
    public function errorsFor(string $event, array $data): array
    {
        $file = $this->schemaPath . '/' . $event . '.schema.json';

        if (!is_file($file)) {
            return ["Kein Schema fuer Event '{$event}' unter {$file}"];
        }

        $validator = new Validator();
        $payload = json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $validator->validate($payload, json_decode(file_get_contents($file) ?: '{}', false));

        return array_map(
            static fn (array $error): string => trim(($error['property'] ?? '') . ' ' . ($error['message'] ?? '')),
            $validator->getErrors(),
        );
    }

    /**
     * @param array<string, mixed> $data
     * @throws RuntimeException
     */
    public function assertValid(string $event, array $data): void
    {
        $errors = $this->errorsFor($event, $data);

        if ($errors !== []) {
            throw new RuntimeException("SSE-Event '{$event}' verletzt sein Schema: " . implode('; ', $errors));
        }
    }
}
