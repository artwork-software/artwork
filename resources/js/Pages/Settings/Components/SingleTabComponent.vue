<script setup>
import draggable from "vuedraggable";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import AddEditTabModal from "@/Pages/Settings/Components/AddEditTabModal.vue";
import SidebarConfigElement from "@/Pages/Settings/Components/Sidebar/SidebarConfigElement.vue";
import SingleComponent from "@/Pages/Settings/Components/SingleComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

import {MenuItem, Switch} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";
import {ref, computed} from "vue";
import {IconChevronDown, IconChevronUp, IconEdit, IconTrash} from "@tabler/icons-vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    tab: {type: Object, required: true},
    allTabs: {type: Array, required: true},
});

const dragging = ref(false);
const showMenu = ref(null);
const showAddEditModal = ref(false);
const tabClosed = ref(true);
const showComponentTabCannotBeDeletedModal = ref(false);

const lastComponentOrder = computed(() => (props.tab?.components?.length || 0) + 1);
const componentCount = computed(() => props.tab?.components?.length || 0);

function updateComponentOrder(components) {
    components.forEach((component, index) => {
        component.order = index + 1;
    });
    const minimalComponents = components.map((c) => ({id: c.id, order: c.order}));
    router.post(
        route("tab.update.component.order", {projectTab: props.tab.id}),
        {components: minimalComponents},
        {preserveScroll: true}
    );
}

function removeTab() {
    if ((props.allTabs?.length || 0) === 1) {
        showComponentTabCannotBeDeletedModal.value = true;
        return;
    }
    router.delete(route("tab.destroy", {projectTab: props.tab.id}));
}

function editTab() {
    showAddEditModal.value = true;
}

function openTab() {
    tabClosed.value = false;
}

function updateDefaultTab() {
    router.patch(route("tab.update.default", {projectTab: props.tab.id}), {
        default: !props.tab.default,
    });
}
</script>

<template>
    <!-- Container -->
    <div class="rounded-lg border border-zinc-200/80 bg-white/70 backdrop-blur px-4 py-5 shadow-sm transition"
        :class="dragging ? 'ring-2 ring-emerald-400/40' : ''">
        <!-- Header (modernisiert) -->
        <div class="relative">
            <div class="flex items-start justify-between gap-4 pl-3 pb-3 border-b border-dashed border-zinc-200 select-none" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                <!-- Linke Infozone -->
                <button
                    type="button"
                    class="flex items-start gap-3 outline-none"
                    @click="tabClosed = !tabClosed"
                    :aria-expanded="!tabClosed">
                    <!-- Chevron im Pill-Button -->
                    <span class="grid place-items-center size-7 rounded-xl border bg-white/70 border-zinc-200/80 transition" aria-hidden="true">
                        <IconChevronDown v-if="tabClosed" class="h-4 w-4 text-zinc-700 transition-transform duration-200"/>
                        <IconChevronUp v-else class="h-4 w-4 text-zinc-700 transition-transform duration-200"/>
                    </span>

                    <div class="flex flex-col items-start min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="headline3 text-zinc-900 truncate" :title="tab.name">{{ tab.name }}</h3>
                            <!-- Default Badge -->
                            <span v-if="tab.default"
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-emerald-300/70 bg-emerald-50/70 text-emerald-700">
                                Default
                            </span>

                            <!-- Komponentenanzahl -->
                            <span
                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4 border-zinc-200 bg-white/70 text-zinc-600">
                                {{ componentCount }} {{ $t('components') }}
                              </span>
                        </div>

                        <!-- Subline -->
                        <p class="mt-0.5 text-[11px] text-zinc-500">
                            {{ $t('Build and order components for this tab') }}
                        </p>
                    </div>
                </button>

                <!-- Rechte Actions -->
                <div class="flex items-center gap-3">
                    <!-- Compact Default Switch -->
                    <div class="flex items-center gap-2">
                        <Switch
                            v-model="tab.default"
                            @click="updateDefaultTab"
                            :class="[
                              tab.default ? 'bg-artwork-buttons-create' : 'bg-zinc-200',
                                'relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create/70 focus:ring-offset-1'
                              ]"
                            :aria-label="$t('Set as default tab')"
                        >
                          <span
                              :class="[
                              tab.default ? 'translate-x-5' : 'translate-x-0',
                              'pointer-events-none relative inline-block size-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                            ]"
                          />
                        </Switch>
                        <span class="text-[11px] text-zinc-600">{{ $t('Default') }}</span>
                    </div>

                    <!-- Menü -->
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" title="Edit" @click="editTab" white-menu-background />
                        <BaseMenuItem :icon="IconTrash" title="Delete" @click="removeTab" white-menu-background />
                    </BaseMenu>
                </div>
            </div>
        </div>

        <!-- Offen -->
        <div v-if="!tabClosed" class="mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between my-2">
                        <h3 class="text-sm font-semibold text-zinc-700 tracking-wide">
                            {{ $t('Components') }}
                        </h3>
                    </div>

                    <DropNewComponent :is-sidebar="false" :all-tabs="allTabs" :tab="tab" :order="1"
                                      @tab-opened="openTab"/>

                    <div
                        class="transition"
                        :class="dragging ? 'ring-2 ring-emerald-400/30' : ''"
                    >
                        <draggable
                            ghost-class="opacity-50"
                            key="draggableKey"
                            item-key="id"
                            :list="tab.components"
                            @start="dragging = true"
                            @end="dragging = false"
                            @change="updateComponentOrder(tab.components)"
                        >
                            <template #item="{ element }">
                                <div v-show="!element.temporary" @mouseover="showMenu = element.id"
                                     @mouseout="showMenu = null" :key="element.id" class="rounded-lg">
                                    <SingleComponent :element="element" :tab="tab" :all-tabs="allTabs"/>
                                    <DropNewComponent
                                        :is-sidebar="false"
                                        :all-tabs="allTabs"
                                        :tab="tab"
                                        :order="element.order + 1"
                                        @tab-opened="openTab"
                                    />
                                </div>
                            </template>
                        </draggable>

                        <div v-if="!tab.components || tab.components.length === 0"
                             class="text-[12px] text-zinc-500 px-2 py-4 text-center">
                            {{ $t('Drop components here to build your tab layout') }}
                        </div>
                    </div>
                </div>

                <SidebarConfigElement :tab="tab"/>
            </div>
        </div>

        <!-- Geschlossen -->
        <div v-else class="mt-4">
            <DropNewComponent :all-tabs="allTabs" :is-sidebar="false" :tab="tab" :order="lastComponentOrder"
                              @tab-opened="openTab"/>
        </div>
    </div>

    <!-- Modals -->
    <AddEditTabModal v-if="showAddEditModal" :tab-to-edit="tab" @close="showAddEditModal = false"/>

    <error-component
        v-if="showComponentTabCannotBeDeletedModal"
        :titel="$t('An error has occurred')"
        :description="$t('At least one project tab must be available')"
        :confirm="$t('Close message')"
        @closed="showComponentTabCannotBeDeletedModal = false"
    />
</template>
