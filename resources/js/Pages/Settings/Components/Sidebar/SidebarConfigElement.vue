<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import draggable from "vuedraggable";

import SingleSidebarElement from "@/Pages/Settings/Components/Sidebar/SingleSidebarElement.vue";
import AddEditSidebarTab from "@/Pages/Settings/Components/Sidebar/AddEditSidebarTab.vue";
import { IconCirclePlus } from "@tabler/icons-vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
    tab: { type: Object, required: true },
});

const { t } = useI18n();

const showAddEditSidebarTabModal = ref(false);
const dragging = ref(false);

// Lokale Kopie, um Prop-Mutationen zu vermeiden
const sidebarTabsLocal = ref([...(props.tab?.sidebar_tabs ?? [])]);
watch(
    () => props.tab?.sidebar_tabs,
    (val) => { sidebarTabsLocal.value = [...(val ?? [])]; }
);

const sidebarCount = computed(() => sidebarTabsLocal.value.length);

function updateComponentOrder(components) {
    components.forEach((component, index) => (component.order = index + 1));
    const minimal = components.map((c) => ({ id: c.id, order: c.order }));

    router.post(
        route("sidebar.tab.reorder"),
        { components: minimal },
        { preserveScroll: true }
    );
}
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white/70 backdrop-blur p-4 shadow-sm">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-dashed border-zinc-200">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-zinc-800 tracking-wide">
                    {{ t('Sidebar') }}
                </h3>
                <span
                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] leading-4
                 border-zinc-200 bg-white/70 text-zinc-600"
                >
          {{ sidebarCount }} {{ t('Tab') }}
        </span>
            </div>

            <button
                type="button"
                class="grid place-items-center size-9 rounded-xl border border-zinc-200/80 bg-white/70 hover:bg-white transition"
                @click="showAddEditSidebarTabModal = true"
                :aria-label="t('Add sidebar tab')"
            >
                <IconCirclePlus class="h-5 w-5 text-zinc-700" />
            </button>
        </div>

        <!-- Liste -->
        <div
            class="mt-3 transition"
            :class="dragging ? 'ring-2 ring-emerald-400/30' : ''"
        >
            <draggable
                ghost-class="opacity-50"
                key="draggableKey"
                item-key="id"
                :list="sidebarTabsLocal"
                @start="dragging = true"
                @end="dragging = false"
                @change="updateComponentOrder(sidebarTabsLocal)"
            >
                <template #item="{ element }">
                    <div :key="element.id" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                        <SingleSidebarElement :tab="props.tab" :sidebar-tab="element" />
                    </div>
                </template>
            </draggable>

            <!-- Empty State -->
            <div
                v-if="sidebarTabsLocal.length === 0"
                class="text-[12px] text-zinc-500 px-3 py-6 text-center border-2 border-dashed border-zinc-200 rounded-lg"
            >
                {{ t('No sidebar tabs yet. Click the plus to add one.') }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <AddEditSidebarTab
        v-if="showAddEditSidebarTabModal"
        :tab-to-edit="null"
        :tab="props.tab"
        @close="showAddEditSidebarTabModal = false"
    />
</template>

<style scoped>
/* Dezent: Fokus/Drag übernehmen die Hervorhebung */
</style>
