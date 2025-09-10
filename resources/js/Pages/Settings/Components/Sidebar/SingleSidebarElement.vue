<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import draggable from "vuedraggable";

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import DropNewComponent from "@/Pages/Settings/Components/DropNewComponent.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import { MenuItem } from "@headlessui/vue";

import {
    IconChevronDown,
    IconChevronUp,
    IconEdit,
    IconTrash,
    IconDragDrop,
} from "@tabler/icons-vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    tab: { type: Object, required: true },
    sidebarTab: { type: Object, required: true },
});

const tabClosed = ref(false);
const dragging = ref(false);
const showMenu = ref(null);
const showAddEditModal = ref(false);

const componentCount = computed(
    () => props.sidebarTab?.components_in_sidebar?.length ?? 0
);

function onDragStart(event) {
    const minimalData = {
        id: props.sidebarTab.id,
        name: props.sidebarTab.name,
        order: props.sidebarTab.order,
    };
    const json = JSON.stringify(minimalData);
    event.dataTransfer?.setData("application/json", json);
    event.dataTransfer?.setData("text/plain", json); // Fallback
    event.dataTransfer.effectAllowed = "copyMove";
}

function removeComponentFromSidebar(id) {
    router.delete(route("sidebar.component.remove", { sidebarTabComponent: id }), {
        preserveState: true,
        preserveScroll: true,
    });
}

function updateComponentOrder(components) {
    components.forEach((c, i) => (c.order = i + 1));
    const minimal = components.map((c) => ({ id: c.id, order: c.order }));
    router.post(
        route("sidebar.tab.update.component.order", {
            projectTabSidebarTab: props.sidebarTab.id,
        }),
        { components: minimal },
        { preserveScroll: true }
    );
}

function openTab() {
    tabClosed.value = false;
}

function editTab() {
    showAddEditModal.value = true;
}

function removeTab() {
    router.delete(
        route("sidebar.tab.destroy", { projectTabSidebarTab: props.sidebarTab.id })
    );
}
</script>

<template>
    <!-- Gesamt-Card (Sidebar-Tab) -->
    <div
        class="mb-3 rounded-2xl border border-zinc-200/80 bg-white/70 backdrop-blur p-4 shadow-sm transition"
        draggable="true"
        @dragstart="onDragStart"
        :class="dragging ? 'ring-2 ring-emerald-400/30' : ''"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between gap-3 pb-3 border-b border-dashed border-zinc-200"
            :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
        >
            <button
                type="button"
                class="group/button inline-flex items-center gap-2 px-2 py-1.5 transition focus:outline-none focus:ring-2 focus:ring-indigo-400/30 focus:ring-offset-0"
                @click="tabClosed = !tabClosed"
                :aria-expanded="!tabClosed"
                :aria-controls="`sidebar-panel-${sidebarTab.id}`">
                <h3
                    class="text-xs font-medium text-zinc-800 truncate max-w-[12rem]"
                    :title="sidebarTab.name">
                    {{ sidebarTab.name }}
                </h3>

                <!-- Ein Icon, rotiert je nach State -->
                <IconChevronDown
                    class="h-4 w-4 text-zinc-600 transition-transform duration-200"
                    :class="{'-rotate-180': !tabClosed}"
                    aria-hidden="true"
                />

                <!-- Counter-Badge (extra kompakt) -->
                <span class="ml-1 inline-flex whitespace-nowrap items-center rounded-full border px-1.5 py-0.5 text-[8px] leading-4 border-zinc-200 bg-white/80 text-zinc-600">
                    {{ componentCount }} {{ $t('items') }}
                </span>
            </button>

            <BaseMenu has-no-offset white-menu-background>
                <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="editTab" />
                <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="removeTab" />
            </BaseMenu>

        </div>

        <!-- Dropzone oben -->
        <DropNewComponent
            :is-sidebar="true"
            :all-tabs="null"
            :tab="sidebarTab"
            :order="1"
            @tab-opened="openTab"
            class="mt-3"
        />

        <!-- Inhalt -->
        <div v-if="!tabClosed" class="mt-2">
            <draggable
                ghost-class="opacity-50"
                key="draggableKey"
                item-key="id"
                :list="sidebarTab.components_in_sidebar"
                @start="dragging = true"
                @end="dragging = false"
                @change="updateComponentOrder(sidebarTab.components_in_sidebar)"
            >
                <template #item="{ element }">
                    <div
                        v-show="!element.temporary"
                        :key="element.id"
                        class="group my-1"
                        @mouseover="showMenu = element.id"
                        @mouseout="showMenu = null"
                    >
                        <div
                            class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/80 bg-white/60 px-4 py-4 transition"
                            :class="dragging ? 'ring-2 ring-emerald-400/30' : ''"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="grid place-items-center size-9 rounded-lg border border-zinc-200/80 bg-white/70 shrink-0">
                                    <ComponentIcons :type="element.component.type" />
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-zinc-900 truncate">
                                        {{ element.component.name }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500">
                                        {{ $t(element.component.type) }}
                                        <template v-if="element.component?.data?.height !== undefined">
                                            · {{ element.component.data.height }} px
                                        </template>
                                        <template v-if="element.component?.data?.showLine === true">
                                            · {{ $t('Show a separator line') }}
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <IconDragDrop class="h-5 w-5 text-zinc-400 invisible group-hover:visible" aria-hidden="true" />
                                <div class="invisible group-hover:visible">
                                    <BaseMenu has-no-offset white-menu-background>
                                        <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="removeComponentFromSidebar(element.id)" />
                                    </BaseMenu>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone zwischen Items -->
                        <DropNewComponent
                            :is-sidebar="true"
                            :all-tabs="null"
                            :tab="sidebarTab"
                            :order="element.order + 1"
                            @tab-opened="openTab"
                            class="mt-2"
                        />
                    </div>
                </template>
            </draggable>
        </div>
    </div>

    <!-- Modal: Add/Edit Sidebar-Tab -->
    <AddEditSidebarTab
        v-if="showAddEditModal"
        :tab="null"
        :tab-to-edit="sidebarTab"
        @close="showAddEditModal = false"
    />
</template>

<style scoped>
/* Dezent, Fokus-/Drag-States übernehmen die Hervorhebung */
</style>
