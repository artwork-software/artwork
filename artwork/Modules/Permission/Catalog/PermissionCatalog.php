<?php

namespace Artwork\Modules\Permission\Catalog;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Support\Collection;

/**
 * Die eine Quelle für alle Rechte: Struktur (Modul, Stufe), Texte (Übersetzungsschlüssel),
 * Voraussetzungen, Implikationen und Rollenbilder. Gespeist aus Catalog/Modules/*.php.
 *
 * Genutzt von: RolesAndPermissionsSeeder / BaseDataProvider (Seed), UpdatePermissionsCommand (Update),
 * PermissionCatalogPresenter (Rechteseite, Presets, Einladung, Referenz), PermissionImplicationService
 * (Backend-Implikation beim Speichern), PermissionPresetSeeder (Rollenbilder).
 */
class PermissionCatalog
{
    /** @var PermissionModuleDefinition[]|null */
    private ?array $modules = null;

    /** @var array<string, PermissionDefinition>|null name => definition */
    private ?array $definitions = null;

    /** @var array<string, string>|null name => module key */
    private ?array $moduleByName = null;

    /** @return PermissionModuleDefinition[] in Navigationsreihenfolge */
    public function modules(): array
    {
        if ($this->modules === null) {
            $modules = [];
            foreach (glob(__DIR__ . '/Modules/*.php') ?: [] as $file) {
                $module = require $file;
                if (!$module instanceof PermissionModuleDefinition) {
                    throw new \RuntimeException("Catalog module file {$file} must return a PermissionModuleDefinition");
                }
                $modules[$module->key] = $module;
            }
            uasort(
                $modules,
                static fn (PermissionModuleDefinition $a, PermissionModuleDefinition $b): int
                    => $a->navOrder <=> $b->navOrder
            );
            $this->modules = array_values($modules);
        }

        return $this->modules;
    }

    public function module(string $key): ?PermissionModuleDefinition
    {
        foreach ($this->modules() as $module) {
            if ($module->key === $key) {
                return $module;
            }
        }

        return null;
    }

    /** @return array<string, PermissionDefinition> name => definition */
    public function definitions(): array
    {
        if ($this->definitions === null) {
            $this->definitions = [];
            $this->moduleByName = [];
            foreach ($this->modules() as $module) {
                foreach ($module->all() as $definition) {
                    if (isset($this->definitions[$definition->name->value])) {
                        throw new \RuntimeException(
                            "Permission {$definition->name->value} is defined twice in the catalog"
                        );
                    }
                    $this->definitions[$definition->name->value] = $definition;
                    $this->moduleByName[$definition->name->value] = $module->key;
                }
            }
        }

        return $this->definitions;
    }

    public function definition(string|PermissionEnum $name): ?PermissionDefinition
    {
        $name = $name instanceof PermissionEnum ? $name->value : $name;

        return $this->definitions()[$name] ?? null;
    }

    public function moduleKeyFor(string|PermissionEnum $name): ?string
    {
        $name = $name instanceof PermissionEnum ? $name->value : $name;
        $this->definitions();

        return $this->moduleByName[$name] ?? null;
    }

    /** @return string[] alle Rechtenamen des Katalogs */
    public function names(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * Ergänzt eine Rechteliste um alle transitiv implizierten Rechte
     * ("stärkstes Recht setzt die kleineren Stufen").
     *
     * @param iterable<string> $names
     * @return string[]
     */
    public function expandWithImplied(iterable $names): array
    {
        $result = [];
        $queue = [];
        foreach ($names as $name) {
            $queue[] = (string) $name;
        }

        while ($queue !== []) {
            $name = array_shift($queue);
            if (isset($result[$name])) {
                continue;
            }
            $result[$name] = true;
            $definition = $this->definition($name);
            if ($definition === null) {
                continue;
            }
            foreach ($definition->impliedNames() as $implied) {
                if (!isset($result[$implied])) {
                    $queue[] = $implied;
                }
            }
        }

        return array_keys($result);
    }

    /**
     * Rechte, die das gegebene Recht (direkt) enthalten – für den "enthalten in"-Marker.
     *
     * @return string[]
     */
    public function impliedBy(string $name): array
    {
        $result = [];
        foreach ($this->definitions() as $definition) {
            if (in_array($name, $definition->impliedNames(), true)) {
                $result[] = $definition->name->value;
            }
        }

        return $result;
    }

    /**
     * Zeilen im Format der permissions-Tabelle (für Seed und Update-Command).
     * group = Modul-Titel (Übersetzungsschlüssel), damit ältere Oberflächen weiter sinnvoll gruppieren.
     * Texte kommen aus lang/*.json (translation_key/tooltipKey).
     *
     * @return array<int, array<string, mixed>>
     */
    public function seedRows(): array
    {
        $rows = [];
        foreach ($this->modules() as $module) {
            foreach ($module->all() as $definition) {
                $rows[] = [
                    'name' => $definition->name->value,
                    'translation_key' => $definition->title,
                    'group' => $module->title,
                    'tooltipKey' => $definition->effect,
                    'checked' => $definition->defaultChecked,
                ];
            }
        }

        return $rows;
    }

    /** @return string[] alle Übersetzungsschlüssel des Katalogs (inkl. Rollenbilder) */
    public function translationKeys(): array
    {
        $keys = array_map(static fn (Persona $persona): string => $persona->value, Persona::cases());
        foreach ($this->modules() as $module) {
            $keys = [...$keys, ...$module->translationKeys()];
        }

        return array_values(array_unique($keys));
    }

    /** @return Collection<int, array<string, mixed>> */
    public function toArray(): Collection
    {
        return collect($this->modules())->map(static fn (PermissionModuleDefinition $m): array => $m->toArray());
    }
}
