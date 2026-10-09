<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hält die Komponententypen in ProjectTabComponentEnum und die Frontend-Registry
 * (projectTabComponentRules.js / projectTabComponents.js) synchron: ein neuer Typ ohne Renderer,
 * Icon oder mit abweichenden Ordner-/Scope-Regeln fällt hier auf.
 */
final class ProjectTabComponentRegistryParityTest extends TestCase
{
    /** Typen, die bewusst nicht in Projekt-Tabs gerendert werden */
    private const NOT_RENDERED_IN_TABS = [
        // nur noch für Altkonfigurationen im Enum
        ProjectTabComponentEnum::RELEVANT_DATES_FOR_SHIFT_PLANNING,
        // nur Druck-Layout und Projektübersicht (Builder-Komponenten)
        ProjectTabComponentEnum::BI_KEY_FIGURES,
        ProjectTabComponentEnum::PROJECT_PERIOD,
    ];

    private function source(string $file): string
    {
        return (string) file_get_contents(resource_path('js/Pages/Projects/Tab/' . $file));
    }

    /**
     * @return array<int, string>
     */
    private function jsStringArray(string $source, string $constant): array
    {
        $this->assertSame(1, preg_match('/export const ' . $constant . ' = \[(.*?)\]/s', $source, $match));
        preg_match_all("/'([A-Za-z]+)'/", $match[1], $values);

        return $values[1];
    }

    /**
     * @return array<int, string>
     */
    private function jsObjectKeys(string $source, string $constant): array
    {
        $this->assertSame(1, preg_match('/export const ' . $constant . ' = \{(.*?)\n\}/s', $source, $match));
        preg_match_all('/^\s+([A-Za-z]+)\s*[:,]/m', $match[1], $keys);

        return $keys[1];
    }

    #[Test]
    public function folder_and_scope_rules_match_the_backend(): void
    {
        $rules = $this->source('projectTabComponentRules.js');

        $this->assertEqualsCanonicalizing(
            ProjectTabComponentEnum::folderBlockedValues(),
            $this->jsStringArray($rules, 'FOLDER_BLOCKED_COMPONENT_TYPES')
        );
        $this->assertEqualsCanonicalizing(
            ProjectTabComponentEnum::scopedValues(),
            $this->jsStringArray($rules, 'SCOPE_COMPONENT_TYPES')
        );
    }

    #[Test]
    public function every_component_type_has_an_icon(): void
    {
        $icons = $this->jsObjectKeys($this->source('projectTabComponentRules.js'), 'COMPONENT_ICONS');

        $this->assertSame([], array_values(array_diff(array_column(ProjectTabComponentEnum::cases(), 'value'), $icons)));
    }

    #[Test]
    public function every_component_type_has_a_tab_renderer(): void
    {
        $rendered = $this->jsObjectKeys($this->source('projectTabComponents.js'), 'projectTabComponents');
        $expected = array_map(
            static fn (ProjectTabComponentEnum $case): string => $case->value,
            array_filter(
                ProjectTabComponentEnum::cases(),
                static fn (ProjectTabComponentEnum $case): bool => !in_array($case, self::NOT_RENDERED_IN_TABS, true)
            )
        );

        $this->assertEqualsCanonicalizing(array_values($expected), $rendered);
    }
}
