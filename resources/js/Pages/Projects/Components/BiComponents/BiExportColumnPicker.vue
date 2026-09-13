<template>
    <div class="space-y-3">
        <!-- Vorlagen: auswählen, speichern, aktualisieren, löschen (nur Ersteller*in/Admin) -->
        <div class="rounded-md border border-border-subtle bg-surface-sunken/50 p-3 space-y-2">
            <div class="flex flex-wrap items-end gap-3">
                <ArtworkBaseListbox
                    class="w-64"
                    :model-value="selectedPreset"
                    @update:model-value="applyPreset"
                    :items="presetList"
                    by="id"
                    option-label="name"
                    :label="$t('Column preset')"
                    :placeholder="$t('Select preset')"
                />
                <BaseInput
                    :id="idPrefix + '_preset_name'"
                    v-model="newPresetName"
                    :label="$t('Save as preset')"
                    :placeholder="$t('Name of the new preset')"
                    class="w-56"
                    @keydown.enter.prevent="savePreset"
                />
                <BaseUIButton
                    :label="$t('Save preset')"
                    hide-icon
                    variant="secondary"
                    :disabled="!newPresetName || modelValue.length === 0 || presetBusy"
                    @click="savePreset"
                />
                <template v-if="selectedPreset">
                    <BaseUIButton
                        v-if="selectedPreset.can_manage && presetDiffers"
                        :label="$t('Update preset')"
                        hide-icon
                        variant="secondary"
                        :disabled="presetBusy"
                        @click="updatePreset"
                    />
                    <button
                        v-if="selectedPreset.can_manage"
                        type="button"
                        class="pb-2 text-xs text-danger hover:underline"
                        @click="showDeletePreset = true"
                    >
                        {{ $t('Delete preset') }}
                    </button>
                    <span v-else class="pb-2 text-xs text-text-subtle">
                        {{ $t('Only the creator or an admin can change this preset.') }}
                    </span>
                </template>
            </div>
            <p v-if="presetError" class="text-xs text-danger">{{ presetError }}</p>
            <p v-else-if="presetNotice" class="text-xs text-success">{{ presetNotice }}</p>
            <p v-else class="text-xs text-text-subtle">
                {{ $t('Presets are shared with everyone who can export.') }}
            </p>
        </div>

        <!-- Spalten nach Gruppen, mit Suche -->
        <div>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                <h4 class="text-sm font-medium text-text-muted">
                    {{ $t('Columns') }}
                    <span class="text-xs font-normal text-text-subtle">({{ modelValue.length }}/{{ allKeys.length }})</span>
                </h4>
                <div class="flex items-center gap-3">
                    <BaseInput
                        :id="idPrefix + '_column_search'"
                        v-model="search"
                        :label="$t('Search column')"
                        :show-label="false"
                        :placeholder="$t('Search column')"
                        class="w-48"
                    />
                    <button type="button" class="text-xs text-accent-600 hover:underline" @click="selectAll">
                        {{ $t('Select all') }}
                    </button>
                    <button type="button" class="text-xs text-text-subtle hover:underline" @click="$emit('update:modelValue', [])">
                        {{ $t('Clear') }}
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto space-y-3 pr-1" :class="maxHeightClass">
                <section
                    v-for="group in visibleGroups"
                    :key="group.key"
                    class="rounded-md border border-border-subtle p-3"
                >
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <BaseCheckbox
                            :id="idPrefix + '_group_' + group.key"
                            :model-value="groupState(group) === 'all' ? true : (groupState(group) === 'some' ? 'indeterminate' : false)"
                            :label="$t(group.label)"
                            @update:model-value="checked => toggleGroup(group, checked)"
                        />
                        <span class="text-[11px] text-text-subtle whitespace-nowrap">
                            {{ selectedInGroup(group) }}/{{ group.columns.length }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-3 gap-y-1.5 pl-1">
                        <BaseCheckbox
                            v-for="col in group.columns"
                            :key="col.key"
                            :id="idPrefix + '_col_' + col.key"
                            :model-value="modelValue.includes(col.key)"
                            :label="col.translate === false ? col.label : $t(col.label)"
                            :disabled="col.key === 'project_id'"
                            @update:model-value="v => toggleColumn(col.key, v)"
                        />
                    </div>
                </section>
                <p v-if="visibleGroups.length === 0" class="py-4 text-center text-xs text-text-subtle">
                    {{ $t('No column matches the search.') }}
                </p>
            </div>
        </div>

        <ArtworkBaseDeleteModal
            v-if="showDeletePreset && selectedPreset"
            :title="$t('Delete preset?')"
            :description="`${selectedPreset.name} — ${$t('The preset is removed for everyone.')}`"
            @close="showDeletePreset = false"
            @delete="deletePreset"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import { extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    // [{ key, label, default, columns: [{ key, label, translate }] }] — aus bi.export.options
    groups: { type: Array, required: true },
    modelValue: { type: Array, required: true },
    presets: { type: Array, default: () => [] },
    idPrefix: { type: String, default: 'bi_export' },
    maxHeightClass: { type: String, default: 'max-h-80' },
});

const emit = defineEmits(['update:modelValue']);

// --- Katalogreihenfolge: Auswahl wird immer in Katalog-Reihenfolge gehalten ---

const allColumns = computed(() => props.groups.flatMap(group => group.columns));
const allKeys = computed(() => allColumns.value.map(col => col.key));
const orderIndex = computed(() => new Map(allKeys.value.map((key, index) => [key, index])));

const emitOrdered = (keys) => {
    const valid = new Set(allKeys.value);
    const unique = [...new Set(keys.filter(key => valid.has(key)))];
    unique.sort((a, b) => (orderIndex.value.get(a) ?? 0) - (orderIndex.value.get(b) ?? 0));
    emit('update:modelValue', unique);
};

const toggleColumn = (key, checked) => {
    if (checked) {
        emitOrdered([...props.modelValue, key]);
    } else {
        emitOrdered(props.modelValue.filter(k => k !== key));
    }
};

const selectAll = () => emitOrdered(allKeys.value);

// --- Gruppen ---

const search = ref('');

const visibleGroups = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.groups;
    return props.groups
        .map(group => ({
            ...group,
            columns: group.columns.filter(col =>
                (col.translate === false ? col.label : t(col.label)).toLowerCase().includes(term)
            ),
        }))
        .filter(group => group.columns.length > 0);
});

const selectedInGroup = (group) => group.columns.filter(col => props.modelValue.includes(col.key)).length;

const groupState = (group) => {
    const count = selectedInGroup(group);
    if (count === 0) return 'none';
    return count === group.columns.length ? 'all' : 'some';
};

const toggleGroup = (group, checked) => {
    const keys = group.columns.map(col => col.key);
    if (checked) {
        emitOrdered([...props.modelValue, ...keys]);
    } else {
        const remove = new Set(keys);
        emitOrdered(props.modelValue.filter(key => !remove.has(key)));
    }
};

// --- Vorlagen ---

const presetList = ref([...props.presets]);
watch(() => props.presets, (value) => { presetList.value = [...value]; });

const selectedPreset = ref(null);
const newPresetName = ref('');
const presetBusy = ref(false);
const presetError = ref(null);
const presetNotice = ref(null);
const showDeletePreset = ref(false);

let noticeTimer = null;
const notice = (message) => {
    presetNotice.value = message;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => { presetNotice.value = null; }, 3000);
};

const sameColumns = (a, b) => a.length === b.length && a.every(key => b.includes(key));

// Auswahl weicht von der gewählten Vorlage ab → "Vorlage aktualisieren" anbieten
const presetDiffers = computed(() =>
    selectedPreset.value ? !sameColumns(selectedPreset.value.columns ?? [], props.modelValue) : false
);

const applyPreset = (preset) => {
    selectedPreset.value = preset;
    presetError.value = null;
    if (preset?.columns) {
        emitOrdered(preset.columns);
    }
};

const savePreset = async () => {
    if (!newPresetName.value || props.modelValue.length === 0) return;
    presetBusy.value = true;
    presetError.value = null;
    try {
        const response = await axios.post(route('bi.export.presets.store'), {
            name: newPresetName.value,
            columns: props.modelValue,
        });
        presetList.value = [...presetList.value, response.data].sort((a, b) => a.name.localeCompare(b.name));
        selectedPreset.value = response.data;
        newPresetName.value = '';
        notice(t('Preset saved.'));
    } catch (error) {
        presetError.value = extractSaveErrorMessage(error) ?? t('The preset could not be saved.');
    } finally {
        presetBusy.value = false;
    }
};

const updatePreset = async () => {
    if (!selectedPreset.value) return;
    presetBusy.value = true;
    presetError.value = null;
    try {
        const response = await axios.put(route('bi.export.presets.update', selectedPreset.value.id), {
            columns: props.modelValue,
        });
        presetList.value = presetList.value.map(preset => preset.id === response.data.id ? response.data : preset);
        selectedPreset.value = response.data;
        notice(t('Preset updated.'));
    } catch (error) {
        presetError.value = extractSaveErrorMessage(error) ?? t('The preset could not be saved.');
    } finally {
        presetBusy.value = false;
    }
};

const deletePreset = async () => {
    const preset = selectedPreset.value;
    showDeletePreset.value = false;
    if (!preset) return;
    presetBusy.value = true;
    presetError.value = null;
    try {
        await axios.delete(route('bi.export.presets.destroy', preset.id));
        presetList.value = presetList.value.filter(p => p.id !== preset.id);
        selectedPreset.value = null;
        notice(t('Preset deleted.'));
    } catch (error) {
        presetError.value = extractSaveErrorMessage(error) ?? t('The preset could not be deleted.');
    } finally {
        presetBusy.value = false;
    }
};
</script>
