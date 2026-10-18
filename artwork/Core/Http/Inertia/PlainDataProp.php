<?php

namespace Artwork\Core\Http\Inertia;

use JsonSerializable;

/**
 * Hülle für große, reine Daten-Props (verschachtelte Arrays, Collections, Models ohne Closures/Lazy-Props).
 *
 * Inertia löst Props rekursiv auf (Response::resolvePropertyInstances) und erzeugt dabei für JEDEN Wert
 * im Baum eine Collection zur Typprüfung — bei mehreren MB Schicht-Daten kostete das Sekunden. Diese Hülle
 * ist weder Array noch Arrayable, wird also nicht durchlaufen; die Ausgabe entsteht erst beim JSON-Encoding
 * und ist identisch (Arrayable-Inhalte serialisieren über jsonSerialize/toArray genauso).
 *
 * Nur für Werte ohne Inertia-Konstrukte im Inneren verwenden (keine Closures, Inertia::lazy/defer/merge).
 */
final readonly class PlainDataProp implements JsonSerializable
{
    public function __construct(private mixed $value)
    {
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
