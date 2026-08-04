<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import draggable from "vuedraggable";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import {IconCirclePlus, IconChevronDown} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

// Props
const props = defineProps({
    tabs: { type: Array, required: true },
    components: { type: Object, required: true },
    componentsSpecial: { type: Array, required: true },
    componentUsages: { type: Object, required: false, default: () => ({}) },
});

// i18n
const { t } = useI18n();

// Lokale States
const searchComponent = ref("");
const showAddEditModal = ref(false);
const dragging = ref(false);

// Lokale Kopie der Tabs (Vermeidung von Prop-Mutation)
const tabsLocal = ref([...props.tabs]);
watch(
    () => props.tabs,
    (val) => {
        tabsLocal.value = [...val];
    }
);

// Debouncing für Search (300ms Verzögerung) - vereinheitlicht für beide Filter
const debouncedSearch = ref("");
let debounceTimeout = null;
watch(searchComponent, (newVal) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        debouncedSearch.value = newVal;
    }, 300);
});

// Eingeklappte Kategorien in der Palette (bei aktiver Suche werden alle Gruppen angezeigt)
const closedGroups = ref(new Set());
function toggleGroup(key) {
    const next = new Set(closedGroups.value);
    next.has(key) ? next.delete(key) : next.add(key);
    closedGroups.value = next;
}
function isGroupClosed(key) {
    return !debouncedSearch.value.trim() && closedGroups.value.has(key);
}

// Gefilterte normale Komponenten (in Gruppen nach Key) - optimiert mit debouncedSearch
const filteredComponents = computed(() => {
    const search = debouncedSearch.value.toLowerCase().trim();

    // Early return wenn keine Suche - Original-Struktur direkt zurückgeben
    if (!search) {
        const result = {};
        for (const key in props.components) {
            result[key] = {
                name: key,
                components: props.components[key],
                closed: false
            };
        }
        return result;
    }

    // Filter mit Suche - optimiert ohne reduce
    const result = {};
    for (const key in props.components) {
        const components = props.components[key];
        const filtered = [];

        for (let i = 0; i < components.length; i++) {
            if (String(components[i].name).toLowerCase().includes(search)) {
                filtered.push(components[i]);
            }
        }

        // Nur nicht-leere Gruppen hinzufügen
        if (filtered.length > 0) {
            result[key] = {
                name: key,
                components: filtered,
                closed: false
            };
        }
    }
    return result;
});

// Vorab-berechnete Translations für Special Components (Performance-Optimierung)
const specialComponentsWithTranslations = computed(() => {
    return props.componentsSpecial.map(component => ({
        ...component,
        translatedName: t(component.name).toLowerCase()
    }));
});

// Gefilterte Special Components (optimiert mit gecachten Translations)
const filteredSpecialComponents = computed(() => {
    const search = debouncedSearch.value.toLowerCase().trim();

    // Early return wenn keine Suche
    if (!search) return props.componentsSpecial;

    const filtered = [];
    const components = specialComponentsWithTranslations.value;

    for (let i = 0; i < components.length; i++) {
        if (components[i].translatedName.includes(search)) {
            filtered.push(props.componentsSpecial[i]);
        }
    }

    return filtered;
});

// Anleitungs-Banner: Workflow-Schritte
const guideSteps = [
    {
        title: "Create components",
        text: "Building blocks are created in the “Component settings” tab — every component in the palette comes from there.",
        actionLabel: "Go to component settings",
        href: route("component.index"),
    },
    {
        title: "Build tabs",
        text: "Create tabs here and drag components from the palette into the tab content.",
    },
    {
        title: "Arrange the layout",
        text: "Adjust the order, group components into folders and fill the sidebar.",
    },
    {
        title: "Set the default tab",
        text: "Choose which tab opens first. The result is the project detail page.",
    },
];

// Reorder-Handler
function updateComponentOrder(components) {
    // lokale Reihenfolge aktualisieren
    components.forEach((component, index) => {
        component.order = index + 1;
    });

    // Minimal-Payload
    const minimalComponents = components.map((c) => ({
        id: c.id,
        order: c.order,
    }));

    router.post(
        route("tab.reorder"),
        { components: minimalComponents },
        { preserveScroll: true }
    );
}
</script>

<template>
    <ProjectSettingsHeader :title="t('Tab Settings')" :description="t('Define global settings for projects.')">
        <template #actions>
            <button class="ui-button-add" @click="showAddEditModal = true">
                <PropertyIcon name="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ t('Create tab') }}
            </button>
        </template>
        <SettingsGuideBanner
            class="mb-6"
            storage-key="settings-guide.project.tabs"
            title="How does this area work?"
            :steps="guideSteps"
            :paragraphs="[
                'The builder chain: create building blocks in the component settings, build the project detail page in the tab settings, arrange the blocks for the PDF in the print layout, and turn them into columns of the project list in the overview builder. All four use the same component pool with different filters — that is why you do not see every component everywhere.',
            ]"
        />
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <!-- Tab components -->
            <div class="w-full col-span-1">

                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
                    <draggable
                        ghost-class="opacity-50"
                        key="draggableKey"
                        item-key="id"
                        :list="tabsLocal"
                        @start="dragging = true"
                        @end="dragging = false"
                        @change="updateComponentOrder(tabsLocal)"
                    >
                        <template #item="{ element }">
                            <div class="mb-2">
                                <div :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                                    <SingleTabComponent :all-tabs="tabsLocal" :tab="element" />
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- Components List -->
            <div class="col-span-1 rounded-lg bg-surface border border-border-subtle shadow-raised p-5 sticky top-4 max-h-[calc(100vh-12rem)] overflow-y-auto">
                <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5 space-y-3">
                    <!-- Suche bleibt beim Scrollen der Palette sichtbar -->
                    <div class="sticky top-0 z-10 -mx-5 px-5 -mt-5 pt-5 pb-3 bg-white border-b border-border-subtle">
                        <div class="flex items-center justify-end w-full">
                            <div class="w-44 md:w-56 lg:w-72">
                                <BaseInput
                                    id="search"
                                    type="text"
                                    name="search"
                                    v-model="searchComponent"
                                    :label="t('Search')"
                                />
                            </div>
                        </div>
                    </div>

                    <SettingsGuideBanner
                        variant="inline"
                        storage-key="settings-guide.project.tabs.palette"
                        title="How to read the component palette"
                        :paragraphs="[
                            '“Used: N” means the component is placed in N tabs — in every one of them it shows the same data record per project.',
                            '“Requires configuration” means that when you drop the component you choose which tabs it collects its content from (documents, comments, checklists).',
                            'The plus button on a component is the alternative to drag & drop.',
                        ]"
                    />

                    <div v-for="componentsArray in filteredComponents" :key="componentsArray.name">
                        <div>
                            <button
                                type="button"
                                class="flex items-center gap-x-2 cursor-pointer w-full text-left"
                                @click="toggleGroup(componentsArray.name)"
                            >
                                <h2 class="text-md font-bold mb-2">
                                    {{ t(componentsArray.name) }}
                                    <span class="text-text-subtle font-normal">({{ componentsArray.components.length }})</span>
                                </h2>
                                <IconChevronDown
                                    class="size-4 text-text-subtle mb-2 transition-transform"
                                    :class="{ '-rotate-90': isGroupClosed(componentsArray.name) }"
                                />
                            </button>
                            <div v-if="!isGroupClosed(componentsArray.name)" class="grid grid-cols-1 2xl:grid-cols-2 gap-2">
                                <DragComponentElement
                                    v-for="component in componentsArray.components"
                                    :key="component.id"
                                    :component="component"
                                    :all-tabs="tabsLocal"
                                    :usages="componentUsages[component.id] ?? []"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <button
                            type="button"
                            class="flex items-center gap-x-2 cursor-pointer w-full text-left"
                            @click="toggleGroup('__special')"
                        >
                            <h2 class="text-md font-bold mb-2">
                                {{ t('Special components') }}
                                <span class="text-text-subtle font-normal">({{ filteredSpecialComponents.length }})</span>
                            </h2>
                            <IconChevronDown
                                class="size-4 text-text-subtle mb-2 transition-transform"
                                :class="{ '-rotate-90': isGroupClosed('__special') }"
                            />
                        </button>
                        <div v-if="!isGroupClosed('__special')" class="grid grid-cols-1 2xl:grid-cols-2 gap-2">
                            <DragComponentElement
                                v-for="component in filteredSpecialComponents"
                                :key="component.id || component.name"
                                :component="component"
                                :all-tabs="tabsLocal"
                                :usages="componentUsages[component.id] ?? []"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <AddEditTabModal v-if="showAddEditModal" @close="showAddEditModal = false" />
    </ProjectSettingsHeader>
</template>

<style scoped>
/* optional: styles bleiben wie gehabt */
</style>
