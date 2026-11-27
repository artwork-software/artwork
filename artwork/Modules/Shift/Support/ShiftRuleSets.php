<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Shift\Models\ShiftRule;

/**
 * Regelsätze, die sich in einem Schritt als ganz normale, danach frei anpassbare Regeln anlegen
 * lassen ("Gesetzliche Standardregeln", "NV Bühne (Bühnentechnik)"). Ein weiterer Regelsatz (z. B.
 * TVK, NV Bühne SR Chor) ist nur eine weitere Definitionsklasse und ein Eintrag in all().
 *
 * Einzige Definitionsquelle: Der Controller liefert all() als Inertia-Prop an ShiftWarnings/Index.vue
 * und legt beim Speichern nur Schlüssel aus dem gewählten Satz an.
 */
final class ShiftRuleSets
{
    public const LEGAL = 'legal';

    public const NV_BUEHNE_STAGE = 'nvBuehneStage';

    /**
     * Name und Beschreibung sind Übersetzungsschlüssel (englisch), die Regeln selbst deutsche
     * Stammdaten.
     *
     * @return array<string, array{key: string, name: string, description: string, icon: string,
     *               rules: list<array<string, mixed>>}>
     */
    public static function all(): array
    {
        return [
            self::LEGAL => [
                'key' => self::LEGAL,
                'name' => 'Create legal default rules',
                'description' => 'Legal defaults according to the German Working Hours Act (ArbZG).',
                'icon' => 'IconScale',
                'rules' => array_map(
                    static fn (array $definition): array => $definition + ['config' => null],
                    LegalDefaultShiftRules::all()
                ),
            ],
            self::NV_BUEHNE_STAGE => [
                'key' => self::NV_BUEHNE_STAGE,
                'name' => 'Create NV Bühne rules (stage technicians)',
                'description' => 'Working time rules of the NV Bühne collective agreement for stage '
                    . 'technicians, with the standard values of the agreement.',
                'icon' => 'IconMasksTheater',
                'rules' => NvBuehneStageShiftRules::all(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Schlüssel der Regeln eines Satzes (leer bei unbekanntem Satz).
     *
     * @return list<string>
     */
    public static function ruleKeys(string $set): array
    {
        return array_map(
            static fn (array $definition): string => $definition['key'],
            self::all()[$set]['rules'] ?? []
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $set, string $key): ?array
    {
        foreach (self::all()[$set]['rules'] ?? [] as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Attribute für ShiftRule::create() aus einer Definition.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public static function attributesFor(array $definition): array
    {
        return LegalDefaultShiftRules::attributesFor($definition) + [
            'config' => ShiftRuleConfig::normalize($definition['trigger_type'], $definition['config'] ?? null),
        ];
    }

    /**
     * Wie LegalDefaultShiftRules::matches(), zusätzlich müssen die Zusatzparameter übereinstimmen
     * (nach Normalisierung, eine Regel ohne config entspricht den Standardwerten). So gilt eine
     * Nachtruhe-Regel mit skip_same_day nicht als die gesetzliche Ruhezeit-Regel und umgekehrt.
     *
     * @param array<string, mixed> $definition
     */
    public static function matches(ShiftRule $rule, array $definition, bool $requireActive = true): bool
    {
        if (!LegalDefaultShiftRules::matches($rule, $definition, $requireActive)) {
            return false;
        }

        // Listen von Schichtgruppen trägt das Haus nach dem Anlegen selbst ein: Sie zählen nicht mit,
        // sonst entstünde beim erneuten Anlegen für einen weiteren Vertrag eine Dublette ohne Gruppen.
        return ShiftRuleConfig::equalsIgnoringIdLists(
            $definition['trigger_type'],
            is_array($rule->config) ? $rule->config : null,
            $definition['config'] ?? null
        );
    }
}
