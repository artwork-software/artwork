<script setup>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";
import AddComponentToTargetModal from "@/Pages/Settings/Components/AddComponentToTargetModal.vue";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { IconCirclePlus, IconFolder } from "@tabler/icons-vue";

const props = defineProps({
    component: { type: Object, required: true },
    // Für die Klick-Alternative ("+"): alle Tabs als mögliche Ziele
    allTabs: { type: Array, required: false, default: null },
    // Einsatzorte dieser Komponente: [{tab, folder|null, sidebar|null}]
    usages: { type: Array, required: false, default: () => [] },
});

const { t } = useI18n();
const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();

const isDragging = ref(false);
const showAddModal = ref(false);

const payload = computed(() => ({
    id: props.component.id,
    type: props.component.type,
    name: props.component.name,
    drop_type: "component",
    sidebar_enabled: props.component.sidebar_enabled,
    special: props.component.special,
}));

const tooltipText = computed(() =>
    props.component.special ? t(props.component.name) : props.component.name
);

function onDragStart(event) {
    try {
        const json = JSON.stringify(payload.value);
        event.dataTransfer?.setData("application/json", json);
        // Fallback für manche Browser/Integrationen
        event.dataTransfer?.setData("text/plain", json);
        event.dataTransfer.effectAllowed = "copyMove";
    } catch (e) {
        // no-op
    }
    isDragging.value = true;
    dispatchEventStart(payload.value);
}

function onDragEnd() {
    isDragging.value = false;
    dispatchEventEnd();
}

// Sofort sichtbarer Drag-State beim Drücken (ohne auf DragStart warten)
function onMouseDown() {
    isDragging.value = true;
    dispatchEventStart(payload.value);
}
function onMouseUp() {
    isDragging.value = false;
    dispatchEventEnd();
}

// Meta-Infos sicher prüfen
const hasHeight = computed(() => props.component?.data?.height !== undefined);
const hasTitleSize = computed(
    () => props.component?.data?.title_size !== undefined
);
const showLine = computed(() => props.component?.data?.showLine === true);

// Übersetzter Name
const displayName = computed(() => t(props.component.name));

const isFolder = computed(() => props.component.type === "DisclosureComponent");
// Ordner-Titel, wie er im Projekt angezeigt wird (data.label, nicht der interne Name)
const folderLabel = computed(() => props.component?.data?.label || null);

// Komponenten, die nach dem Ablegen eine Tab-Auswahl (Scope) benötigen
const needsScope = computed(() =>
    ["ProjectDocumentsComponent", "CommentTab", "ChecklistComponent"].includes(props.component.type)
);

const usageTooltip = computed(() =>
    props.usages
        .map((u) => {
            if (u.folder) return `${u.tab} → ${t("Folder")} "${u.folder}"`;
            if (u.sidebar) return `${u.tab} → ${t("Sidebar")} "${u.sidebar}"`;
            return u.tab;
        })
        .join("\n")
);
</script>

<template>
    <DropComponentsToolTip :top="true" :tooltip-text="tooltipText">
        <div
            class="group relative w-full select-none"
            draggable="true"
            @mousedown="onMouseDown"
            @mouseup="onMouseUp"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            :aria-grabbed="isDragging"
        >
            <!-- Karte -->
            <div
                class="flex items-center gap-3 rounded-2xl border bg-white/70 backdrop-blur px-3 py-3 shadow-sm transition focus-within:ring-2 focus-within:ring-accent-600/50 active:scale-[0.99]"
                :class="[ isDragging
            ? 'ring-2 ring-success-border border-success-border bg-success-surface shadow rounded-lg'
            : 'border-border-subtle'
        ]"
                role="button"
                tabindex="0"
            >
                <!-- Icon -->
                <div
                    class="shrink-0 grid place-items-center size-10 rounded-xl border bg-white/60"
                    :class="isDragging ? 'border-success-border/70' : 'border-border-subtle/80'"
                    aria-hidden="true"
                >
                    <ComponentIcons :type="component.type" />
                </div>

                <!-- Text & Meta -->
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold truncate" :title="displayName">
                        {{ displayName }}
                    </div>

                    <!-- Ordner-Titel, wie er im Projekt erscheint -->
                    <div
                        v-if="isFolder && folderLabel"
                        class="mt-0.5 flex items-center gap-1 text-[11px] text-text-muted min-w-0"
                        :title="t('This title is displayed in the project')"
                    >
                        <IconFolder class="size-3.5 shrink-0 text-text-subtle" />
                        <span class="truncate font-medium">{{ folderLabel }}</span>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <!-- Verwendung -->
                        <span
                            v-if="usages.length > 0"
                            class="inline-flex items-center rounded-full border border-success-border bg-success-surface/70 px-2 py-0.5 text-[10px] leading-4 text-success whitespace-pre-line"
                            :title="usageTooltip"
                        >
                            {{ t('Used') }}: {{ usages.length }}
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center rounded-full border border-border-subtle bg-surface-sunken/70 px-2 py-0.5 text-[10px] leading-4 text-text-subtle"
                        >
                            {{ t('Not used') }}
                        </span>

                        <!-- Scope-Konfiguration nötig -->
                        <span
                            v-if="needsScope"
                            class="inline-flex items-center rounded-full border border-warning-border bg-warning-surface/70 px-2 py-0.5 text-[10px] leading-4 text-warning"
                            :title="t('When adding, you choose which tabs are included')"
                        >
                            {{ t('Requires configuration') }}
                        </span>
                        <!-- Höhe -->
                        <span v-if="hasHeight"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-text-muted"
                            :class="isDragging ? 'border-success-border bg-success-surface' : 'border-border-subtle bg-white/70'">
                          {{ component.data.height }} px
                        </span>

                        <!-- Separator -->
                        <span v-if="showLine"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-text-muted"
                            :class="isDragging ? 'border-success-border bg-success-surface' : 'border-border-subtle bg-white/70'">
                            {{ t('Show a separator line') }}
                        </span>

                        <!-- Title Size -->
                        <span
                            v-if="hasTitleSize"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-text-muted"
                            :class="isDragging ? 'border-success-border bg-success-surface' : 'border-border-subtle bg-white/70'">
                          {{ component.data.title_size }} px
                        </span>
                    </div>
                </div>

                <!-- Rechts: Klick-Alternative + Drag-Hinweis -->
                <div class="ml-auto flex items-center gap-1.5 shrink-0">
                    <!-- Plus-Button: Hinzufügen ohne Drag & Drop -->
                    <button
                        v-if="allTabs"
                        type="button"
                        class="grid place-items-center size-8 rounded-md border border-border-subtle bg-white/60 hover:bg-success-surface hover:border-success-border transition"
                        :title="t('Add without drag and drop')"
                        :aria-label="t('Add component')"
                        draggable="false"
                        @mousedown.stop
                        @click.stop="showAddModal = true"
                    >
                        <IconCirclePlus class="size-4 text-text-muted" />
                    </button>

                    <div
                        class="h-8 w-5 rounded-md border"
                        :class="isDragging ? 'border-success-border/70 bg-success-surface/50 rounded-lg' : 'border-border-subtle bg-white/60'"
                        aria-hidden="true"
                    >
                        <div class="h-full w-full grid place-items-center text-[10px] text-text-subtle">⋮⋮</div>
                    </div>
                </div>
            </div>
        </div>
    </DropComponentsToolTip>

    <AddComponentToTargetModal
        v-if="showAddModal && allTabs"
        :component="component"
        :tabs="allTabs"
        @close="showAddModal = false"
    />
</template>

<style scoped>
/* Keine extra Hover-Effekte – Fokus & Drag sind ausreichend hervorgehoben */
</style>
