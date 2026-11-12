<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <!-- Verfügbare Bausteine: ziehen oder per Plus übernehmen -->
        <div class="flex flex-col rounded-xl border border-border-subtle bg-surface p-3">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">
                {{ $t('Available') }}
            </div>
            <BaseInput
                id="projectContentColumnSearch"
                v-model="search"
                label="Search"
                is-small
            />
            <draggable
                :list="availableColumns"
                :group="{ name: 'project-content-columns', pull: 'clone', put: false }"
                :sort="false"
                :clone="toSelected"
                item-key="key"
                class="mt-2 max-h-80 space-y-1 overflow-y-auto pr-1"
            >
                <template #item="{ element }">
                    <div
                        class="group flex cursor-grab items-center justify-between rounded-lg border border-border-subtle bg-white px-2.5 py-1.5 text-xs text-text hover:border-accent-200"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <IconGripVertical class="size-3.5 shrink-0 text-text-subtle" stroke-width="1.5" />
                            <span class="truncate">{{ element.name }}</span>
                            <span v-if="element.tabs?.length" class="truncate text-text-subtle" :title="tabsOf(element.key)">
                                {{ tabsOf(element.key) }}
                            </span>
                            <span v-if="element.kind === 'system'" class="shrink-0 rounded bg-surface-sunken px-1.5 py-0.5 text-[10px] text-text-muted">
                                {{ $t('System') }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="rounded p-0.5 text-text-subtle hover:text-accent-600"
                            :title="$t('Add column')"
                            :aria-label="$t('Add column')"
                            @click="add(element)"
                        >
                            <IconPlus class="size-4" stroke-width="1.5" />
                        </button>
                    </div>
                </template>
            </draggable>
            <p v-if="availableColumns.length === 0" class="mt-2 text-xs text-text-subtle">
                {{ $t('No further columns available') }}
            </p>
        </div>

        <!-- Gewählte Spalten in Export-Reihenfolge -->
        <div class="flex flex-col rounded-xl border border-dashed border-accent-200 bg-accent-50/40 p-3">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">
                {{ $t('Columns in the export') }} ({{ modelValue.length }})
            </div>
            <draggable
                :list="modelValue"
                group="project-content-columns"
                handle=".column-handle"
                item-key="key"
                ghost-class="opacity-50"
                class="min-h-24 flex-1 space-y-1"
                @change="emitUpdate"
            >
                <template #item="{ element, index }">
                    <div class="rounded-lg border border-border-subtle bg-white px-2.5 py-1.5">
                        <div class="flex items-center gap-2 text-xs text-text">
                            <IconGripVertical class="column-handle size-3.5 shrink-0 cursor-grab text-text-subtle" stroke-width="1.5" />
                            <span class="min-w-0 flex-1 truncate">
                                {{ element.title || nameOf(element.key) }}
                                <span v-if="element.title" class="text-text-subtle">({{ nameOf(element.key) }})</span>
                                <span v-if="tabsOf(element.key)" class="text-text-subtle">· {{ tabsOf(element.key) }}</span>
                            </span>
                            <button
                                type="button"
                                class="rounded p-0.5 text-text-subtle hover:text-text"
                                :title="$t('Column settings')"
                                :aria-label="$t('Column settings')"
                                @click="toggleSettings(element.key)"
                            >
                                <IconSettings class="size-4" stroke-width="1.5" />
                            </button>
                            <button
                                type="button"
                                class="rounded p-0.5 text-text-subtle hover:text-danger"
                                :title="$t('Remove column')"
                                :aria-label="$t('Remove column')"
                                @click="remove(index)"
                            >
                                <IconX class="size-4" stroke-width="1.5" />
                            </button>
                        </div>
                        <div v-if="openSettings[element.key]" class="mt-2 space-y-2 border-t border-border-subtle pt-2">
                            <BaseInput
                                :id="`projectContentColumnTitle-${element.key}`"
                                v-model="element.title"
                                label="Column heading"
                                :placeholder="nameOf(element.key)"
                                is-small
                                @update:model-value="emitUpdate"
                            />
                            <BaseCheckbox
                                v-if="showLabelOption"
                                :id="`projectContentColumnLabel-${element.key}`"
                                v-model="element.show_label"
                                :label="$t('Show heading above the value')"
                                @update:model-value="emitUpdate"
                            />
                        </div>
                    </div>
                </template>
            </draggable>
            <p v-if="modelValue.length === 0" class="text-center text-xs text-text-subtle">
                {{ $t('Drag columns here or add them with the plus button') }}
            </p>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import draggable from "vuedraggable";
import {IconGripVertical, IconPlus, IconSettings, IconX} from "@tabler/icons-vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";

/**
 * Spaltenwahl des Projektinhalte-Exports: links alle Bausteine, rechts die gewählten in Export-Reihenfolge.
 * Spalten sind {key, title, show_label}; die Bezeichnungs-Option gibt es nur im Word-Export.
 */
const props = defineProps({
    modelValue: {type: Array, required: true},
    columns: {type: Array, required: true},
    showLabelOption: {type: Boolean, default: false},
});
const emit = defineEmits(["update:modelValue"]);

const search = ref("");
const openSettings = ref({});

const selectedKeys = computed(() => new Set(props.modelValue.map((column) => column.key)));

const availableColumns = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.columns.filter((column) => !selectedKeys.value.has(column.key) &&
        (query === "" || [column.name, ...(column.tabs ?? [])].some((text) => text.toLowerCase().includes(query))));
});

const columnOf = (key) => props.columns.find((column) => column.key === key);
const nameOf = (key) => columnOf(key)?.name ?? key;
// Tabs der Komponente, damit gleichnamige Komponenten (z. B. aus kopierten Tabs) unterscheidbar sind
const tabsOf = (key) => (columnOf(key)?.tabs ?? []).join(", ");

// Systembausteine wie Projektname stehen in Word ohne Bezeichnung, eigene Komponenten mit
const toSelected = (column) => ({key: column.key, title: null, show_label: column.kind === "component"});

const emitUpdate = () => emit("update:modelValue", [...props.modelValue]);

const add = (column) => emit("update:modelValue", [...props.modelValue, toSelected(column)]);

const remove = (index) => emit("update:modelValue", props.modelValue.filter((_, position) => position !== index));

const toggleSettings = (key) => {
    openSettings.value = {...openSettings.value, [key]: !openSettings.value[key]};
};
</script>
