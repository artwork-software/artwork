<?php

namespace Artwork\Modules\Permission\Catalog;

/**
 * Ein Modul (= Karte auf der Rechteseite) in Navigationsreihenfolge.
 *
 * - tiers:     Stufenleiter (1 → 2 → 3), in Reihenfolge; jede höhere Stufe sollte die niedrigere via implies enthalten
 * - extras:    Zusatzrechte quer zur Leiter, in Anzeige-Reihenfolge
 * - advanced:  Feinrechte / Altbestand, standardmäßig eingeklappt
 * - adminOnly: Bereiche, die nur artwork-Admins sehen (reine Anzeige, damit klar ist, warum kein Recht existiert)
 * - hint:      Modul-Hinweis (z. B. "Ohne Rechte kann eine Person nur in Räumen buchen, die für alle buchbar sind")
 */
final readonly class PermissionModuleDefinition
{
    /**
     * @param PermissionDefinition[] $tiers
     * @param PermissionDefinition[] $extras
     * @param PermissionDefinition[] $advanced
     * @param string[] $adminOnly
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $icon,
        public int $navOrder,
        public ?string $moduleSetting = null,
        public ?string $hint = null,
        public array $tiers = [],
        public array $extras = [],
        public array $advanced = [],
        public array $adminOnly = [],
        public ?string $advancedTitle = null,
        public ?string $advancedHint = null,
    ) {
    }

    /** @return PermissionDefinition[] */
    public function all(): array
    {
        return [...$this->tiers, ...$this->extras, ...$this->advanced];
    }

    /** @return string[] */
    public function translationKeys(): array
    {
        $keys = [$this->title, ...$this->adminOnly];
        if ($this->hint !== null) {
            $keys[] = $this->hint;
        }
        if ($this->advancedTitle !== null) {
            $keys[] = $this->advancedTitle;
        }
        if ($this->advancedHint !== null) {
            $keys[] = $this->advancedHint;
        }
        foreach ($this->all() as $definition) {
            $keys = [...$keys, ...$definition->translationKeys()];
        }

        return array_values(array_unique($keys));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'icon' => $this->icon,
            'nav_order' => $this->navOrder,
            'module_setting' => $this->moduleSetting,
            'hint' => $this->hint,
            'advanced_title' => $this->advancedTitle,
            'advanced_hint' => $this->advancedHint,
            'admin_only' => $this->adminOnly,
            'tiers' => array_map(static fn (PermissionDefinition $d): array => $d->toArray('tier'), $this->tiers),
            'extras' => array_map(static fn (PermissionDefinition $d): array => $d->toArray('extra'), $this->extras),
            'advanced' => array_map(
                static fn (PermissionDefinition $d): array => $d->toArray('advanced'),
                $this->advanced
            ),
        ];
    }
}
