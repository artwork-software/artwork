<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectTab;
use Database\Seeders\DefaultComponentSeeder;
use Illuminate\Database\Seeder;

/**
 * Tab-Zusammenstellung: Die globale Tab-Struktur ist Konfiguration und wird
 * bewusst NICHT umgebaut. Fehlt sie komplett (frisches System), wird der
 * Standard-Seeder nachgezogen; ansonsten werden nur kuratierte
 * Custom-Komponenten ergänzt (idempotent per Name+Typ).
 */
class DemoTabCompositionSeeder extends Seeder
{
    /** @var array<int, array{name: string, type: ProjectTabComponentEnum, data: array<string, mixed>}> */
    private const CUSTOM_COMPONENTS = [
        [
            'name' => 'Produktionsdetails',
            'type' => ProjectTabComponentEnum::TITLE,
            'data' => ['title' => 'Produktionsdetails', 'title_size' => '15'],
        ],
        [
            'name' => 'Sparte',
            'type' => ProjectTabComponentEnum::DROPDOWN,
            'data' => [
                'label' => 'Sparte',
                'options' => [
                    ['value' => 'Tanz'], ['value' => 'Schauspiel'], ['value' => 'Konzert'],
                    ['value' => 'Performance'], ['value' => 'Musiktheater'], ['value' => 'Lesung'],
                ],
                'selected' => '',
            ],
        ],
        [
            'name' => 'Technische Anforderungen',
            'type' => ProjectTabComponentEnum::TEXT_AREA,
            'data' => ['label' => 'Technische Anforderungen', 'text' => '', 'placeholder' => 'Rider, Sonderbedarfe, Sicherheitsauflagen …'],
        ],
        [
            'name' => 'Ansprechperson extern',
            'type' => ProjectTabComponentEnum::TEXT_FIELD,
            'data' => ['label' => 'Ansprechperson extern', 'text' => '', 'placeholder' => 'Name, Kontakt'],
        ],
        [
            'name' => 'Barrierefrei',
            'type' => ProjectTabComponentEnum::CHECKBOX,
            'data' => ['label' => 'Barrierefreie Vorstellung', 'checked' => false],
        ],
        [
            'name' => 'Pressematerial',
            'type' => ProjectTabComponentEnum::LINK,
            'data' => ['label' => 'Pressematerial', 'text' => '', 'placeholder' => 'https://…'],
        ],
    ];

    public function run(): void
    {
        if (ProjectTab::query()->count() === 0) {
            $this->command?->info('Keine Projekt-Tabs vorhanden – Standard-Tab-Struktur wird angelegt.');
            $this->call(DefaultComponentSeeder::class);
        }

        $targetTab = ProjectTab::query()->where('name', 'Project Information')->first()
            ?? ProjectTab::query()->orderBy('order')->first();
        if ($targetTab === null) {
            $this->command?->error('Tab-Struktur konnte nicht ermittelt werden – Custom-Komponenten übersprungen.');

            return;
        }

        $nextOrder = (int) $targetTab->components()->max('order');
        $created = 0;
        foreach (self::CUSTOM_COMPONENTS as $definition) {
            $component = Component::firstOrCreate(
                ['name' => $definition['name'], 'type' => $definition['type']->value],
                [
                    'data' => $definition['data'],
                    'special' => false,
                    'sidebar_enabled' => false,
                    'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value,
                ]
            );
            if (!$component->wasRecentlyCreated) {
                continue;
            }
            $created++;

            $targetTab->components()->create([
                'component_id' => $component->id,
                'order' => ++$nextOrder,
                'scope' => [],
            ]);
        }

        $this->command?->info(sprintf(
            'Tab "%s": %d Custom-Komponenten ergänzt (Sparte, Technische Anforderungen, …).',
            $targetTab->name,
            $created
        ));
    }
}
