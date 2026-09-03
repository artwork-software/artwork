<?php

namespace Artwork\Modules\Permission\Catalog;

use Artwork\Modules\Permission\Enums\PermissionEnum;

/**
 * Ein Recht im Katalog. Alle Texte sind englische Übersetzungsschlüssel (lang/de.json übersetzt).
 *
 * - title:    Anzeigename ("Plan shifts")
 * - effect:   Ein-Satz-Wirkung als Handlung, max. ~60 Zeichen ("Creates shifts and assigns people")
 * - unlocks:  was sichtbar wird (Menüpunkte, Seiten, Tabs, Buttons) – Menünamen in Anführungszeichen
 * - allows:   konkrete Aktionen
 * - requires: Voraussetzungen (Requirement)
 * - implies:  Rechte, die dieses Recht enthält (Superset); werden beim Speichern automatisch mitgesetzt
 * - personas: typische Rollenbilder
 * - note:     Hinweis (Nebenwirkungen, Abgrenzung), optional
 * - defaultChecked: Standard-Recht für neue Personen
 * - hidden:   nicht anzeigen (Feature deaktiviert)
 */
final readonly class PermissionDefinition
{
    /**
     * @param string[] $unlocks
     * @param string[] $allows
     * @param Requirement[] $requires
     * @param PermissionEnum[] $implies
     * @param Persona[] $personas
     */
    public function __construct(
        public PermissionEnum $name,
        public string $title,
        public string $effect,
        public array $unlocks = [],
        public array $allows = [],
        public array $requires = [],
        public array $implies = [],
        public array $personas = [],
        public ?string $note = null,
        public bool $defaultChecked = false,
        public bool $hidden = false,
    ) {
    }

    /** @return string[] */
    public function impliedNames(): array
    {
        return array_map(static fn (PermissionEnum $permission): string => $permission->value, $this->implies);
    }

    /** @return string[] alle Übersetzungsschlüssel dieses Rechts */
    public function translationKeys(): array
    {
        $keys = [$this->title, $this->effect, ...$this->unlocks, ...$this->allows];
        if ($this->note !== null) {
            $keys[] = $this->note;
        }
        foreach ($this->requires as $requirement) {
            if ($requirement->type !== Requirement::TYPE_PERMISSION) {
                $keys[] = $requirement->label;
            }
        }

        return $keys;
    }

    /** @return array<string, mixed> */
    public function toArray(string $tier): array
    {
        return [
            'name' => $this->name->value,
            'tier' => $tier,
            'title' => $this->title,
            'effect' => $this->effect,
            'unlocks' => $this->unlocks,
            'allows' => $this->allows,
            'requires' => array_map(
                static fn (Requirement $requirement): array => $requirement->toArray(),
                $this->requires
            ),
            'implies' => $this->impliedNames(),
            'personas' => array_map(static fn (Persona $persona): string => $persona->value, $this->personas),
            'note' => $this->note,
            'default_checked' => $this->defaultChecked,
            'hidden' => $this->hidden,
        ];
    }
}
