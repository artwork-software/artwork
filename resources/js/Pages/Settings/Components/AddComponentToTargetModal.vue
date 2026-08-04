<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import SelectTabsModal from "@/Pages/Settings/Components/SelectTabsModal.vue";
import SelectTabsModalForDisclosure from "@/Pages/Settings/Components/SelectTabsModalForDisclosure.vue";
import { IconFolder, IconLayoutSidebarRight, IconCirclePlus } from "@tabler/icons-vue";

const props = defineProps({
    component: { type: Object, required: true },
    tabs: { type: Array, required: true },
});

const emit = defineEmits(["close"]);

// Komponenten, die nach dem Hinzufügen eine Tab-Auswahl (Scope) benötigen
const scopeTypes = ["ProjectDocumentsComponent", "CommentTab", "ChecklistComponent"];

// Große Layout-Komponenten funktionieren nicht in Ordnern (gleiche Regeln wie beim Drag & Drop)
const blockedInDisclosure = [
    "CalendarTab",
    "ShiftTab",
    "BudgetTab",
    "BulkBody",
    "ChecklistAllComponent",
    "CommentAllTab",
    "ProjectAllDocumentsComponent",
];

const needsScope = computed(() => scopeTypes.includes(props.component.type));
const isFolder = computed(() => props.component.type === "DisclosureComponent");

const folderDisabledReason = computed(() => {
    if (isFolder.value) return "Folders cannot be nested inside folders";
    if (blockedInDisclosure.includes(props.component.type)) return "This component cannot be placed inside a folder";
    return null;
});

const sidebarDisabledReason = computed(() => {
    if (isFolder.value) return "Folder components cannot be placed in the sidebar";
    const enabled = props.component.sidebar_enabled;
    if (enabled === false || enabled === 0 || enabled === "0") return "This component cannot be placed in the sidebar";
    return null;
});

function foldersOfTab(tab) {
    return (tab.components ?? []).filter((c) => c.component?.type === "DisclosureComponent");
}

function folderLabel(folderElement) {
    return folderElement.component?.data?.label || folderElement.component?.name;
}

// Scope-Auswahl-Modals
const scopeModalTab = ref(null);
const scopeModalPosition = ref(1);
const scopeModalDisclosureId = ref(null);
const showScopeModal = ref(false);
const showScopeModalForDisclosure = ref(false);

function onScopeModalClosed() {
    showScopeModal.value = false;
    showScopeModalForDisclosure.value = false;
    emit("close");
}

function addToTab(tab) {
    const order = (tab.components?.length || 0) + 1;

    if (needsScope.value) {
        scopeModalTab.value = tab;
        scopeModalPosition.value = order;
        showScopeModal.value = true;
        return;
    }

    router.post(
        route("tab.add.component", { projectTab: tab.id }),
        { component_id: props.component.id, order },
        { preserveScroll: true, onSuccess: () => emit("close") }
    );
}

function addToFolder(tab, folderElement) {
    if (folderDisabledReason.value) return;
    const order = (folderElement.disclosure_components?.length || 0) + 1;

    if (needsScope.value) {
        scopeModalTab.value = tab;
        scopeModalPosition.value = order;
        scopeModalDisclosureId.value = folderElement.component.id;
        showScopeModalForDisclosure.value = true;
        return;
    }

    router.post(
        route("project-management-builder.add.disclosure.component"),
        {
            component_id: props.component.id,
            disclosure_id: folderElement.component.id,
            order,
        },
        { preserveScroll: true, onSuccess: () => emit("close") }
    );
}

function addToSidebarTab(sidebarTab) {
    if (sidebarDisabledReason.value) return;
    const order = (sidebarTab.components_in_sidebar?.length || 0) + 1;

    router.post(
        route("tab.add.component.sidebar", { projectTabSidebarTab: sidebarTab.id }),
        { component_id: props.component.id, order },
        { preserveScroll: true, onSuccess: () => emit("close") }
    );
}
</script>

<template>
    <ArtworkBaseModal
        :title="$t('Add component')"
        :description="$t('Choose where this component should be added. It will be placed at the end of the selected area.')"
        @close="emit('close')"
    >
        <!-- Welche Komponente wird hinzugefügt -->
        <div class="flex items-center gap-3 rounded-xl border border-border-subtle bg-surface-sunken px-3 py-2.5 mb-4">
            <div class="grid place-items-center size-9 rounded-lg border border-border-subtle bg-white shrink-0">
                <ComponentIcons :type="component.type" />
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-text truncate">
                    {{ component.special ? $t(component.name) : component.name }}
                </div>
                <div class="text-[11px] text-text-subtle flex items-center gap-2">
                    <span>{{ $t(component.type) }}</span>
                    <span
                        v-if="needsScope"
                        class="inline-flex items-center rounded-full border border-warning-border bg-warning-surface px-1.5 py-0.5 text-[10px] text-warning"
                    >
                        {{ $t('Requires configuration') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="max-h-[50vh] overflow-y-auto space-y-2 pr-1">
            <div
                v-for="tab in tabs"
                :key="tab.id"
                class="rounded-xl border border-border-subtle bg-white/70"
            >
                <!-- Tab selbst -->
                <button
                    type="button"
                    class="w-full flex items-center justify-between gap-2 px-3 py-2.5 hover:bg-success-surface rounded-t-xl transition text-left"
                    :class="foldersOfTab(tab).length === 0 && (tab.sidebar_tabs ?? []).length === 0 ? 'rounded-b-xl' : ''"
                    @click="addToTab(tab)"
                >
                    <span class="text-sm font-medium text-text truncate">{{ tab.name }}</span>
                    <span class="inline-flex items-center gap-1 text-[11px] text-success shrink-0">
                        <IconCirclePlus class="size-4" />
                        {{ $t('Add to this tab') }}
                    </span>
                </button>

                <!-- Ordner im Tab -->
                <div
                    v-for="folderElement in foldersOfTab(tab)"
                    :key="'folder-' + folderElement.id"
                    class="border-t border-dashed border-border-subtle"
                >
                    <button
                        type="button"
                        class="w-full flex items-center justify-between gap-2 pl-8 pr-3 py-2 text-left transition"
                        :class="folderDisabledReason ? 'text-text-subtle cursor-not-allowed' : 'hover:bg-success-surface'"
                        :title="folderDisabledReason ? $t(folderDisabledReason) : undefined"
                        :disabled="!!folderDisabledReason"
                        @click="addToFolder(tab, folderElement)"
                    >
                        <span class="flex items-center gap-1.5 min-w-0">
                            <IconFolder class="size-4 text-text-subtle shrink-0" />
                            <span class="text-sm text-text truncate">{{ folderLabel(folderElement) }}</span>
                        </span>
                        <span v-if="!folderDisabledReason" class="inline-flex items-center gap-1 text-[11px] text-success shrink-0">
                            <IconCirclePlus class="size-4" />
                            {{ $t('Add to folder') }}
                        </span>
                    </button>
                </div>

                <!-- Sidebar-Tabs -->
                <div
                    v-for="sidebarTab in (tab.sidebar_tabs ?? [])"
                    :key="'sidebar-' + sidebarTab.id"
                    class="border-t border-dashed border-border-subtle"
                >
                    <button
                        type="button"
                        class="w-full flex items-center justify-between gap-2 pl-8 pr-3 py-2 text-left transition"
                        :class="sidebarDisabledReason ? 'text-text-subtle cursor-not-allowed' : 'hover:bg-success-surface'"
                        :title="sidebarDisabledReason ? $t(sidebarDisabledReason) : undefined"
                        :disabled="!!sidebarDisabledReason"
                        @click="addToSidebarTab(sidebarTab)"
                    >
                        <span class="flex items-center gap-1.5 min-w-0">
                            <IconLayoutSidebarRight class="size-4 text-text-subtle shrink-0" />
                            <span class="text-sm text-text truncate">{{ $t('Sidebar') }}: {{ sidebarTab.name }}</span>
                        </span>
                        <span v-if="!sidebarDisabledReason" class="inline-flex items-center gap-1 text-[11px] text-success shrink-0">
                            <IconCirclePlus class="size-4" />
                            {{ $t('Add to sidebar') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>

    <!-- Scope-Auswahl für Dokumente/Kommentare/Checklisten -->
    <SelectTabsModal
        v-if="showScopeModal"
        :tabs="tabs"
        :current-tab="scopeModalTab"
        :position="scopeModalPosition"
        :component-data="component"
        @close="onScopeModalClosed"
    />
    <SelectTabsModalForDisclosure
        v-if="showScopeModalForDisclosure"
        :tabs="tabs"
        :current-tab="scopeModalTab"
        :position="scopeModalPosition"
        :component-data="component"
        :disclosure-id="scopeModalDisclosureId"
        @close="onScopeModalClosed"
    />
</template>
