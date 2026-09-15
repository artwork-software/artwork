<?php

namespace Artwork\Modules\AiAssistant\Support;

use Artwork\Modules\AiAssistant\Http\Requests\AiWriteRequest;

/**
 * Was ein Aufruf eines Schreib-Endpunkts will: Vorschau oder Ausfuehrung, mit welcher Aktion und
 * mit genau welchen Argumenten (core-confirm-actions; Konzept W2 §4).
 *
 * `args` ist der Rumpf des Aufrufs **ohne** die Steuerfelder `dry_run` und `action_id`, ergaenzt um
 * Werte aus dem Pfad (bei `PATCH` die Termin-ID). Genau ueber diese Menge laeuft der `args_hash` —
 * die Termin-ID gehoert dazu, sonst liesse sich eine bestaetigte Aenderung auf ein anderes Objekt
 * umbiegen.
 */
final class AiWriteIntent
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(
        public readonly bool $dryRun,
        public readonly ?string $actionId,
        public readonly array $args,
    ) {
    }

    /**
     * @param array<string, mixed> $fromPath
     */
    public static function fromRequest(AiWriteRequest $request, array $fromPath = []): self
    {
        $args = $request->validated();
        unset($args['dry_run'], $args['action_id']);

        return new self($request->isDryRun(), $request->actionId(), array_merge($args, $fromPath));
    }
}
