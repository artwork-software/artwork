<template>
    <ArtworkBaseModal
        title="Assign event types"
        description="Select the event types whose events should be counted under this tag."
        modal-size="sm:max-w-lg"
        @close="$emit('close', false)"
    >
        <div class="space-y-3">
            <div class="flex items-center gap-2 rounded-md border border-border-subtle bg-surface-sunken px-3 py-2">
                <span v-if="tag.color" class="size-3.5 shrink-0 rounded-full" :style="{ backgroundColor: tag.color }" />
                <span class="truncate font-lexend text-sm font-medium text-text">{{ tag.name_de }}</span>
                <span class="truncate text-xs text-text-muted">({{ tag.name }})</span>
            </div>

            <BaseInput
                v-if="eventTypes.length > 8"
                id="bi_tag_event_type_search"
                v-model="searchQuery"
                label="Search event types"
            />

            <div class="flex items-center gap-1 text-xs">
                <BaseUIButton label="Select all" variant="ghost" size="sm" hide-icon @click="selectAll" />
                <BaseUIButton label="Deselect all" variant="ghost" size="sm" hide-icon @click="deselectAll" />
                <span class="ml-auto tabular-nums text-text-muted">{{ selectedIds.size }} / {{ eventTypes.length }}</span>
            </div>

            <ul
                v-if="filteredEventTypes.length > 0"
                class="max-h-80 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li
                    v-for="eventType in filteredEventTypes"
                    :key="eventType.id"
                    class="px-3 py-2.5 transition-colors duration-150 hover:bg-surface-hover motion-reduce:transition-none"
                >
                    <BaseCheckbox
                        :id="'bi_tag_event_type_' + eventType.id"
                        :model-value="selectedIds.has(eventType.id)"
                        @update:model-value="toggle(eventType.id)"
                    >
                        <template #label>
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="size-3 shrink-0 rounded-full" :style="{ backgroundColor: eventType.hex_code }" />
                                <span class="truncate font-lexend text-text">{{ eventType.name }}</span>
                                <span class="text-xs font-normal text-text-muted">({{ eventType.abbreviation }})</span>
                            </span>
                        </template>
                    </BaseCheckbox>
                </li>
            </ul>
            <EmptyState
                v-else
                icon="IconSearch"
                :title="$t('No results found')"
            />

            <BaseAlertComponent v-if="errorMessage" type="error" :message="errorMessage" />
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="saving"
                @click="$emit('close', false)"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="saving"
                :disabled="saving"
                @click="save"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import EmptyState from '@/Artwork/Feedback/EmptyState.vue';
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import { BI_REQUEST_CONFIG, extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js';

const props = defineProps({
    tag: { type: Object, required: true },
    eventTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const selectedIds = ref(new Set((props.tag.event_types ?? []).map((eventType) => eventType.id)));
const searchQuery = ref('');
const saving = ref(false);
const errorMessage = ref('');

const filteredEventTypes = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.eventTypes;
    return props.eventTypes.filter(
        (eventType) => (eventType.name ?? '').toLowerCase().includes(query)
            || (eventType.abbreviation ?? '').toLowerCase().includes(query)
    );
});

const toggle = (id) => {
    const next = new Set(selectedIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selectedIds.value = next;
};

const selectAll = () => {
    selectedIds.value = new Set(filteredEventTypes.value.map((eventType) => eventType.id));
};

const deselectAll = () => {
    selectedIds.value = new Set();
};

const save = async () => {
    if (saving.value) {
        return;
    }
    saving.value = true;
    errorMessage.value = '';
    try {
        await axios.post(route('bi.tags.sync-event-types', props.tag.id), {
            event_type_ids: Array.from(selectedIds.value),
        }, BI_REQUEST_CONFIG);
        emit('close', true);
    } catch (error) {
        // Fehler steht im Modal (BI_REQUEST_CONFIG unterdrückt den globalen Toast)
        errorMessage.value = extractSaveErrorMessage(error) ?? error.message;
    } finally {
        saving.value = false;
    }
};
</script>
