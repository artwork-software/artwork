<?php

namespace App\Http\Controllers;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class BiComponentSettingsController extends Controller
{
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
        ]);
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
        Cache::forget('settings_components_not_special');
        Cache::forget('settings_components_special');
        Cache::forget('print_layout_components_not_special');
        Cache::forget('print_layout_components_special');
        Cache::forget('print_layout_all_components');
    }
}
