<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { Disclosure, DisclosureButton, DisclosurePanel, MenuItem } from "@headlessui/vue";

import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DropComponentInDisclosureComponentElement from "@/Pages/Settings/Components/DropComponentInDisclosureComponentElement.vue";
import EditComponentScopeModal from "@/Pages/Settings/Components/EditComponentScopeModal.vue";

import { IconDragDrop, IconTrash, IconDotsVertical, IconChevronDown, IconRadiusBottomLeft } from "@tabler/icons-vue";

const props = defineProps({
    element: { type: Object, required: true },
    tab: { type: Object, required: true },
    allTabs: { type: Array, required: false, default: () => [] },
});

const dragging = ref(false);

const showEditScope = ref(false);
const isDocuments = computed(() => props.element?.component?.type === 'ProjectDocumentsComponent');
const scopeTabNames = computed(() => {
    const scopeIds = Array.isArray(props.element?.scope) ? props.element.scope : [];
    const tabsArr = props.allTabs || [];
    if ((!scopeIds || scopeIds.length === 0) && props.tab) {
        return [props.tab.name];
    }
    if (!tabsArr.length) return [];
    const map = new Map(tabsArr.map((t) => [t.id, t.name]));
    return scopeIds.map((id) => map.get(id)).filter(Boolean);
});
function openEditScopeModal() { showEditScope.value = true }
function closeEditScopeModal() { showEditScope.value = false }

// Aktionen
function removeComponentFromTab() {
    router.delete(route("tab.remove.component", { projectTab: props.tab.id }), {
        data: { component_id: props.element.id },
    });
}

function updateNote() {
    router.patch(
        route("tab.update.component.note", { componentInTab: props.element.id }),
        { note: props.element.note },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                props.element.openNoteInput = false; // wie bisher (nested prop)
            },
        }
    );
}

function requestDeleteComponentInDisclosure(componentId) {
    router.delete(route("tab.remove.component.in.disclosure"), {
        data: { id: componentId },
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <div class="group w-full">
        <!-- Card -->
        <div
            class="w-full rounded-xl border border-zinc-200/80 bg-white/70 backdrop-blur px-4 py-4 my-2 shadow-sm transition"
            :class="dragging ? 'ring-2 ring-emerald-400/30 rounded-lg' : ''"
            :key="element.id"
        >
            <div class="flex items-start justify-between gap-3">
                <!-- Left: Icon + Content -->
                <div class="flex items-start gap-3 min-w-0">
                    <!-- Icon-Box -->
                    <div
                        class="grid place-items-center size-10 rounded-xl border bg-white/60 border-zinc-200/80 shrink-0"
                        aria-hidden="true"
                    >
                        <ComponentIcons :type="element.component.type" />
                    </div>

                    <!-- Text -->
                    <div class="min-w-0">
                        <!-- Titel + Badges -->
                        <div class="">
                            <div class="text-sm font-semibold text-zinc-900 truncate">
                                {{ element.component.name }}
                            </div>

                            <!-- Höhe -->
                            <span
                                v-if="element.component?.data?.height !== undefined"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-zinc-200 bg-white/70 text-zinc-600"
                            >
                            {{ element.component.data.height }} px
                          </span>

                            <!-- Separatorline -->
                            <span
                                v-if="element.component?.data?.showLine === true"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-zinc-200 bg-white/70 text-zinc-600"
                            >
                {{ $t('Show a separator line') }}
              </span>

                            <!-- Title Size -->
                            <span
                                v-if="element.component?.data?.title_size !== undefined"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-zinc-200 bg-white/70 text-zinc-600"
                            >
                {{ element.component.data.title_size }} px
              </span>
                        </div>

                        <!-- Typ -->
                        <div class="text-[11px] text-zinc-500 mt-0.5">
                            {{ $t(element.component.type) }}
                        </div>

                        <!-- Documents Scope Info + Edit -->
                        <div v-if="isDocuments" class="mt-2 text-[12px] text-zinc-600 flex items-center gap-2">
                            <span>
                                zeigt Dokumente von Tabs: {{ scopeTabNames.join(', ') }}
                            </span>
                            <button
                                type="button"
                                class="rounded-md px-2 py-0.5 text-xs ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50"
                                @click="openEditScopeModal"
                            >
                                Edit
                            </button>
                        </div>

                        <!-- Note -->
                        <div class="mt-3">
                            <div class="xxsDarkBold text-[11px] text-zinc-600">
                                Tooltip Text (optional):
                            </div>

                            <div
                                v-if="!element.openNoteInput"
                                class="cursor-pointer mt-1.5 text-[12px] leading-5"
                                :class="element.note ? 'text-zinc-700' : 'text-zinc-400'"
                                @click="element.openNoteInput = !element.openNoteInput"
                            >
                                {{ element.note ?? $t('Click here to add text') }}
                            </div>

                            <div v-else class="mt-1.5">
                                <TextareaComponent
                                    v-model="element.note"
                                    id="note"
                                    :label="$t('Enter text here')"
                                    :show-label="false"
                                    @focusout="updateNote"
                                />
                            </div>
                        </div>

                        <!-- Disclosure-Container -->
                        <div v-if="element.component.type === 'DisclosureComponent'" class="mt-3">
                            <Disclosure v-slot="{ open }" as="div">
                                <DisclosureButton class="xsDark flex items-center gap-2 text-sm">
                                    {{ $t('Components in Disclosure') }}
                                    <IconChevronDown class="size-4 text-zinc-600 transition-transform" :class="{ 'rotate-180': open }" />
                                </DisclosureButton>

                                <!-- Dropzone sichtbar, wenn geschlossen -->
                                <DropComponentInDisclosureComponentElement v-if="!open" :element="element" :index="1" class="mt-2" />

                                <DisclosurePanel class="mt-2">
                                    <DropComponentInDisclosureComponentElement :element="element" :index="1" class="mb-2" />
                                    <div v-for="(component, index) in element.disclosure_components" :key="component.id" class="mb-2">
                                        <div class="flex items-center justify-between gap-4 group/component">
                                            <div>
                                                <div class="flex items-center gap-1 text-sm text-zinc-800">
                                                    <IconRadiusBottomLeft class="size-3 -mt-1 text-zinc-500" />
                                                    <ComponentIcons :type="component.component.type" />
                                                    {{ $t(component.component.name) }}
                                                </div>
                                                <div class="text-[11px] text-zinc-500">
                                                    {{ $t(component.component.type) }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="invisible group-hover/component:visible rounded-md p-1 hover:bg-red-50"
                                                @click="requestDeleteComponentInDisclosure(component.id)"
                                                :aria-label="$t('Delete')"
                                            >
                                                <IconTrash class="size-5 text-zinc-500 hover:text-red-500" />
                                            </button>
                                        </div>

                                        <DropComponentInDisclosureComponentElement
                                            :element="element"
                                            :index="component.order + 1"
                                            class="mt-2"
                                        />
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                        </div>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-start gap-3 shrink-0 pl-2">
                    <!-- Drag-Handle (visuell) -->
                    <IconDragDrop class="h-5 w-5 text-zinc-400 hidden group-hover:block" aria-hidden="true" />

                    <!-- Menu -->
                    <div class="invisible group-hover:visible">
                        <BaseMenu has-no-offset>
                            <MenuItem v-slot="{ active }">
                                <a
                                    href="#"
                                    @click.prevent="removeComponentFromTab(element.id)"
                                    :class="[
                    active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary',
                    'group flex items-center px-4 py-2 text-sm rounded-md'
                  ]"
                                >
                                    <IconTrash stroke-width="1.5" class="mr-3 h-5 w-5 text-primaryText" aria-hidden="true" />
                                    {{ $t('Delete') }}
                                </a>
                            </MenuItem>
                        </BaseMenu>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Scope Modal -->
    <EditComponentScopeModal
        v-if="showEditScope && isDocuments"
        :all-tabs="allTabs"
        :component-in-tab-id="element.id"
        :initial-selection="Array.isArray(element.scope) ? element.scope : []"
        :current-tab="tab"
        @close="closeEditScopeModal"
    />
</template>

<style scoped>
/* bewusst dezent: keine kräftigen Hover; Fokus/Drag übernehmen die Hervorhebung */
</style>
