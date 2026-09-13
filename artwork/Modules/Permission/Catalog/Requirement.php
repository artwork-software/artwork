<?php

namespace Artwork\Modules\Permission\Catalog;

use Artwork\Modules\Permission\Enums\PermissionEnum;

/**
 * Voraussetzung, damit ein Recht überhaupt wirkt. Wird auf der Rechteseite als Chip angezeigt und –
 * soweit prüfbar – live gegen den Zustand der Person bzw. der Instanz ausgewertet.
 */
final readonly class Requirement
{
    public const TYPE_MODULE = 'module';          // module_settings-Schalter
    public const TYPE_PERMISSION = 'permission';  // anderes Recht derselben Person
    public const TYPE_SETTING = 'setting';        // Instanz-Einstellung (Key wird im Presenter aufgelöst)
    public const TYPE_FEATURE = 'feature';        // Schnittstelle/Feature aktiv (z. B. Sage)
    public const TYPE_PROJECT_TEAM = 'project_team'; // nur Hinweis, projektabhängig, nicht prüfbar
    public const TYPE_ROLE = 'role';              // Rolle (artwork admin)

    private function __construct(
        public string $type,
        public string $value,
        public string $label,
        /** hard = ohne diese Voraussetzung ist das Recht wirkungslos; soft = Einschränkung/Hinweis */
        public bool $hard = true,
    ) {
    }

    public static function module(string $moduleKey, string $label): self
    {
        return new self(self::TYPE_MODULE, $moduleKey, $label);
    }

    public static function permission(PermissionEnum $permission, bool $hard = true): self
    {
        return new self(self::TYPE_PERMISSION, $permission->value, $permission->value, $hard);
    }

    public static function setting(string $settingKey, string $label): self
    {
        return new self(self::TYPE_SETTING, $settingKey, $label);
    }

    public static function feature(string $featureKey, string $label): self
    {
        return new self(self::TYPE_FEATURE, $featureKey, $label);
    }

    public static function projectTeam(string $label): self
    {
        return new self(self::TYPE_PROJECT_TEAM, 'project_team', $label, false);
    }

    public static function role(string $role, string $label): self
    {
        return new self(self::TYPE_ROLE, $role, $label);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
            'label' => $this->label,
            'hard' => $this->hard,
        ];
    }
}
