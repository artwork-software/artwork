<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProjectSettingsHeader from "@/Pages/Settings/Components/ProjectSettingsHeader.vue";
import draggable from "vuedraggable";
import SingleTabComponent from "@/Pages/Settings/Components/SingleTabComponent.vue";
import DragComponentElement from "@/Pages/Settings/Components/DragComponentElement.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import {IconPlus} from "@tabler/icons-vue";

// Props
const props = defineProps({
    tabs: { type: Array, required: true },
    components: { type: Object, required: true },
    componentsSpecial: { type: Array, required: true },
});

// i18n
const { t } = useI18n();

// Lokale States
const searchComponent = ref("");
const debouncedSearch = ref("");
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

// Debouncing für Search (300ms Verzögerung)
let debounceTimeout = null;
watch(searchComponent, (newVal) => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        debouncedSearch.value = newVal;
    }, 300);
});

// Gefilterte normale Komponenten (in Gruppen nach Key) - mit Early Return
const filteredComponents = computed(() => {
    const search = searchComponent.value.toLowerCase().trim();

    // Early return wenn keine Suche
    if (!search) {
        return Object.keys(props.components).reduce((acc, key) => {
            acc[key] = {
                name: key,
                components: props.components[key],
                closed: false
            };
            return acc;
        }, {});
    }

    // Filter mit Suche - nur Gruppen mit Treffern zurückgeben
    return Object.keys(props.components).reduce((acc, key) => {
        const filtered = props.components[key].filter((component) =>
            String(component.name).toLowerCase().includes(search)
        );

        // Nur nicht-leere Gruppen hinzufügen
        if (filtered.length > 0) {
            acc[key] = {
                name: key,
                components: filtered,
                closed: false
            };
        }
        return acc;
    }, {});
});

// Gefilterte Special Components (mit Debouncing für Translation)
const filteredSpecialComponents = computed(() => {
    const search = debouncedSearch.value.toLowerCase().trim();

    // Early return wenn keine Suche
    if (!search) return props.componentsSpecial;

    return props.componentsSpecial.filter((component) =>
        t(component.name).toLowerCase().includes(search)
    );
});

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
                <component :is="IconPlus" stroke-width="1" class="size-5" />
                {{ t('Create tab') }}
            </button>
        </template>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <!-- Tab components -->
                <div class="w-full col-span-1">

                    <div class="card white p-5">
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
                <div class="col-span-1 card glassy p-5">
                    <div class="card white p-5 space-y-3">
                        <div class="flex items-center justify-end w-full mb-3">
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

                        <div v-for="componentsArray in filteredComponents" :key="componentsArray.name">
                            <div>
                                <div class="flex items-center gap-x-4 cursor-pointer">
                                    <h2 class="text-md font-bold mb-2">{{ t(componentsArray.name) }}</h2>
                                </div>
                                <div class="grid grid-cols-1 2xl:grid-cols-2 gap-2">
                                    <DragComponentElement
                                        v-for="component in componentsArray.components"
                                        :key="component.id"
                                        :component="component"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-md font-bold mb-2">{{ t('Special components') }}</h2>
                            <div class="grid grid-cols-1 2xl:grid-cols-2 gap-2">
                                <DragComponentElement
                                    v-for="component in filteredSpecialComponents"
                                    :key="component.id || component.name"
                                    :component="component"
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
