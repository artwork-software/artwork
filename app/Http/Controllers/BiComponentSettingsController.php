<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\ModuleSettings\Services\ModuleSettingsService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class BiComponentSettingsController extends Controller
{
    public function __construct(
        private readonly ModuleSettingsService $moduleSettingsService,
        private readonly ProjectTabService $projectTabService,
        private readonly GeneralSettings $generalSettings
    ) {
    }

    public function index(): Response
    {
        $biFields = Component::isBiField()
            ->select(['id', 'name', 'type', 'data', 'special', 'sidebar_enabled', 'permission_type', 'is_bi_field', 'bi_order'])
            ->orderBy('bi_order')
            ->get();

        // Reuse the canonical type definitions ({name, availableFields}) so the shared
        // ComponentModal receives objects (not plain strings) and its type listbox works.
        $allowedKeys = [
            ProjectTabComponentEnum::TEXT_FIELD->value,
            ProjectTabComponentEnum::TEXT_AREA->value,
            ProjectTabComponentEnum::CHECKBOX->value,
            ProjectTabComponentEnum::DROPDOWN->value,
        ];
        $allowedTypes = array_intersect_key(
            ProjectTabComponentEnum::getValues(),
            array_flip($allowedKeys)
        );

        return Inertia::render('Settings/BiSettings/Index', [
            'biFields' => $biFields,
            'tabComponentTypes' => $allowedTypes,
            'setup' => $this->setupChecklist(),
        ]);
    }

    /**
     * Einrichtungs-Checkliste: alles, was das BI-Modul braucht, an EINER Stelle
     * mit Status und Direktlink — die Voraussetzungen liegen sonst an sieben Orten.
     *
     * @return array<int, array<string, mixed>>
     */
    private function setupChecklist(): array
    {
        $moduleEnabled = $this->moduleSettingsService->isModuleVisible('business_intelligence');

        $performanceLinked = BiEventTypeTag::query()
            ->where('kpi_role', BiEventTypeTag::KPI_ROLE_PERFORMANCE)->has('eventTypes')->exists();
        $eventDayLinked = BiEventTypeTag::query()
            ->where('kpi_role', BiEventTypeTag::KPI_ROLE_EVENT_DAY)->has('eventTypes')->exists();

        $activeCategories = BiAudienceCategory::query()->where('is_active', true)->count();

        $biTab = $this->projectTabService->findFirstProjectTabWithBusinessIntelligenceComponent();

        $roomsTotal = Room::query()->count();
        $roomsWithCapacity = Room::query()->where('capacity', '>', 0)->count();

        $seasonSet = !empty($this->generalSettings->playing_time_window_start)
            && !empty($this->generalSettings->playing_time_window_end);

        $dashboardUsers = User::permission(PermissionEnum::BI_DASHBOARD->value)->count();
        $exportUsers = User::permission(PermissionEnum::BI_EXPORT->value)->count();

        return [
            [
                'key' => 'module',
                'title' => 'Enable the BI module',
                'done' => $moduleEnabled,
                'detail' => $moduleEnabled ? 'Module is enabled.' : 'The module is disabled — menu and dashboard are hidden.',
                'href' => route('tool.module-settings.index'),
                'actionLabel' => 'Open module settings',
            ],
            [
                'key' => 'kpi_tags',
                'title' => 'Assign the key-figure tags to event types',
                'done' => $performanceLinked && $eventDayLinked,
                'detail' => ($performanceLinked && $eventDayLinked)
                    ? 'Tags for “Performances” and “Event days” are assigned.'
                    : (!$performanceLinked && !$eventDayLinked
                        ? 'Neither tag is assigned — performances, event days and occupancy stay empty.'
                        : (!$performanceLinked
                            ? 'The tag for “Performances” has no event types — performances and occupancy stay empty.'
                            : 'The tag for “Event days” has no event types — event days stay empty.')),
                'href' => route('event_types.bi_tags'),
                'actionLabel' => 'Open BI tags',
            ],
            [
                'key' => 'component',
                'title' => 'Place the BI component in a project tab',
                'done' => $biTab !== null,
                'detail' => $biTab !== null
                    ? __('Placed in tab') . ' „' . $biTab->getAttribute('name') . '“.'
                    : 'Without the component, figures cannot be entered on projects.',
                'href' => route('tab.index'),
                'actionLabel' => 'Open tab settings',
            ],
            [
                'key' => 'capacities',
                'title' => 'Enter seat capacities for rooms',
                'done' => $roomsTotal > 0 && $roomsWithCapacity === $roomsTotal,
                'detail' => $roomsTotal === 0
                    ? 'No rooms yet.'
                    : $roomsWithCapacity . ' / ' . $roomsTotal . ' ' . __('rooms have a capacity — needed for the occupancy rate.'),
                'href' => route('areas.management'),
                'actionLabel' => 'Open rooms',
                // Teilweise erledigt ist hier normal (nicht jeder Raum hat Publikum)
                'soft' => $roomsWithCapacity > 0,
            ],
            [
                'key' => 'categories',
                'title' => 'Check audience categories',
                'done' => $activeCategories > 0,
                'detail' => $activeCategories > 0
                    ? $activeCategories . ' ' . __('active categories (full, reduced, free).')
                    : 'Without categories, quotas such as free-ticket rate cannot be calculated.',
                'href' => null,
                'actionLabel' => null,
            ],
            [
                'key' => 'season',
                'title' => 'Set the season window',
                'done' => $seasonSet,
                'detail' => $seasonSet
                    ? 'Default period for dashboard and exports is set.'
                    : 'Without a season window, dashboard and exports evaluate all periods.',
                'href' => route('tool.communication-and-legal'),
                'actionLabel' => 'Open communication & legal',
            ],
            [
                'key' => 'permissions',
                'title' => 'Grant BI permissions',
                'done' => $dashboardUsers > 0,
                'detail' => $dashboardUsers . ' ' . __('people can view the dashboard,') . ' ' . $exportUsers . ' ' . __('can export.'),
                'href' => route('users'),
                'actionLabel' => 'Open users',
            ],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'permission_type' => ['nullable', 'string'],
        ]);

        $maxOrder = Component::isBiField()->max('bi_order') ?? 0;

        /** @var Component $component */
        $component = Component::create([
            'name' => $request->name,
            'type' => $request->type,
            'data' => $request->data,
            'permission_type' => $request->permission_type,
            'special' => false,
            'sidebar_enabled' => false,
            'is_bi_field' => true,
            'bi_order' => $maxOrder + 1,
        ]);

        foreach ($request->get('users', []) as $user) {
            $component->users()->attach($user['user_id'], ['can_write' => $user['can_write']]);
        }

        foreach ($request->get('departments', []) as $department) {
            $component->departments()->attach($department['department_id'], ['can_write' => $department['can_write']]);
        }

        $this->clearCaches();

        return redirect()->back();
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'permission_type' => ['nullable', 'string'],
        ]);

        $component->users()->detach();
        foreach ($request->get('users', []) as $user) {
            $component->users()->attach($user['user_id'], ['can_write' => $user['can_write']]);
        }

        $component->departments()->detach();
        foreach ($request->get('departments', []) as $department) {
            $component->departments()->attach($department['department_id'], ['can_write' => $department['can_write']]);
        }

        $component->update($request->only('name', 'data', 'permission_type'));

        $this->clearCaches();

        return redirect()->back();
    }

    public function destroy(Component $component): RedirectResponse
    {
        $component->users()->detach();
        $component->departments()->detach();

        if ($component->projectValue) {
            $component->projectValue->delete();
        }

        $component->delete();

        $this->clearCaches();

        return redirect()->back();
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:components,id'],
        ]);

        foreach ($request->ordered_ids as $index => $id) {
            Component::where('id', $id)->update(['bi_order' => $index + 1]);
        }

        $this->clearCaches();

        return redirect()->back();
    }

    private function clearCaches(): void
    {
        // BI-Felder erscheinen in der Tab-Palette; der Einstellungs-Cache wird der Vollständigkeit halber mit geleert
        Cache::forget('settings_components_not_special_tab_palette');
        Cache::forget('settings_components_not_special_component_settings');
        Cache::forget('settings_components_special');
        Cache::forget('print_layout_components_not_special');
        Cache::forget('print_layout_components_special');
        Cache::forget('print_layout_all_components');
    }
}
