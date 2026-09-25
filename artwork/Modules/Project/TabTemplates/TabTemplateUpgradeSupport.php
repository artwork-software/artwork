<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Services\ComponentUsageService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Gemeinsame Bausteine der Vorlagen-Umstellungen (laufen als Datenmigration auf Kundeninstanzen):
 * sicher löschen, Sichtrechte übernehmen, Sprache des Bestands-Tabs treffen, Caches leeren.
 */
class TabTemplateUpgradeSupport
{
    /**
     * Löscht eine ersetzte Komponente nur, wenn sie nirgends mehr steckt (Tab, Ordner, Drucklayout,
     * Seitenleiste) und keine erfassten Inhalte hat. Drucklayout- und Ordner-Verweise haben keine
     * Fremdschlüssel-Kaskade — ein blindes delete() würde die Migration abbrechen.
     */
    public function deleteIfUnused(Component $component): bool
    {
        $stillUsed = $component->tabComponent()->exists()
            || $component->componentInDisclosures()->exists()
            || $component->componentInPrintLayouts()->exists()
            || $component->sidebarTabComponent()->exists();

        if ($stillUsed || $this->hasEnteredText($component)) {
            return false;
        }

        $component->users()->detach();
        $component->departments()->detach();
        ProjectComponentValue::query()->where('component_id', $component->id)->delete();
        $component->delete();

        return true;
    }

    public function hasEnteredText(Component $component): bool
    {
        return ProjectComponentValue::query()
            ->where('component_id', $component->id)
            ->get()
            ->contains(fn (ProjectComponentValue $value) => trim((string) ($value->data['text'] ?? '')) !== '');
    }

    /**
     * Die Ersatz-Komponente bekommt dieselben Sicht-/Schreibrechte wie die ersetzte.
     */
    public function copyPermissions(Component $from, Component $to): void
    {
        $to->update(['permission_type' => $from->permission_type]);

        $to->users()->sync(
            $from->users()->get()
                ->mapWithKeys(fn ($user) => [$user->id => ['can_write' => $user->pivot->can_write]])
                ->all()
        );
        $to->departments()->sync(
            $from->departments()->get()
                ->mapWithKeys(fn ($department) => [$department->id => ['can_write' => $department->pivot->can_write]])
                ->all()
        );
    }

    /**
     * Sprache, in der ein Vorlagen-Eintrag angelegt wurde: Stimmt der gespeicherte Name mit der
     * englischen Fassung des Schlüssels überein (und nicht mit der deutschen), war es Englisch.
     */
    public function localeOf(string $storedName, string $translationKey): string
    {
        $english = __($translationKey, [], 'en');
        $german = __($translationKey, [], 'de');

        return trim($storedName) === $english && $english !== $german ? 'en' : 'de';
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function withLocale(string $locale, callable $callback): mixed
    {
        $previous = App::getLocale();
        App::setLocale($locale);

        try {
            return $callback();
        } finally {
            App::setLocale($previous);
        }
    }

    public function placementCountInTab(Component $component, int $tabId): int
    {
        return ComponentInTab::query()
            ->where('component_id', $component->id)
            ->where('project_tab_id', $tabId)
            ->count();
    }

    /**
     * Gleiche Cache-Schlüssel wie ComponentController nach Änderungen an Komponenten.
     */
    public function clearComponentCaches(): void
    {
        $cacheKeys = [
            'settings_components_not_special_component_settings',
            'settings_components_not_special_tab_palette',
            'settings_components_special',
            'settings_tabs_with_relations',
            'print_layout_components_not_special',
            'print_layout_components_special',
            'print_layout_all_components',
            'print_layout_components_not_special_v2',
            'print_layout_components_special_v2',
            'print_layout_all_components_v2',
        ];
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        ComponentUsageService::clearCache();
    }
}
