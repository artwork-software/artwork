<template>
    <BaseModal @closed="$emit('close', false)">
        <ModalHeader
            :title="$t('Assign event types')"
            :description="$t('Select the event types whose events should be counted under this tag.')"
        />
        <div class="flex items-center gap-2 mb-4">
            <span v-if="tag.color" class="w-4 h-4 rounded-full shrink-0" :style="{ backgroundColor: tag.color }" />
            <span class="font-medium text-sm font-lexend">{{ tag.name_de }}</span>
            <span class="text-xs text-text-subtle">({{ tag.name }})</span>
        </div>

        <BaseInput
            v-if="eventTypes.length > 8"
            id="bi_tag_event_type_search"
            v-model="searchQuery"
            type="text"
            :label="$t('Search event types')"
        />

        <div class="flex items-center gap-3 mt-4 mb-2 text-xs">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover" @click="selectAll">
                {{ $t('Select all') }}
            </button>
            <span class="text-text-subtle">|</span>
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover" @click="deselectAll">
                {{ $t('Deselect all') }}
            </button>
            <span class="ml-auto text-text-subtle">{{ selectedIds.size }} / {{ eventTypes.length }}</span>
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-border-subtle border border-border-subtle rounded-lg">
            <label
                v-for="eventType in filteredEventTypes"
                :key="eventType.id"
                :for="'bi_tag_event_type_' + eventType.id"
                class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-surface-sunken"
            >
                <input
                    :id="'bi_tag_event_type_' + eventType.id"
                    type="checkbox"
                    class="input-checklist"
                    :checked="selectedIds.has(eventType.id)"
                    @change="toggle(eventType.id)"
                />
                <span class="w-3.5 h-3.5 rounded-full shrink-0" :style="{ backgroundColor: eventType.hex_code }" />
                <span class="text-sm font-lexend text-text">{{ eventType.name }}</span>
                <span class="text-xs text-text-subtle">({{ eventType.abbreviation }})</span>
            </label>
            <p v-if="filteredEventTypes.length === 0" class="px-3 py-4 text-sm text-text-subtle">
                {{ $t('No results found') }}
            </p>
        </div>

        <div v-if="errorMessage" class="errorText mt-3">
            {{ errorMessage }}
        </div>

        <div class="flex items-center justify-center mt-6">
            <BaseUIButton
                :label="$t('Save')"
                is-add-button
                :processing="saving"
                :disabled="saving"
                @click="save"
            />
        </div>
    </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import BaseModal from '@/Components/Modals/BaseModal.vue';
import ModalHeader from '@/Components/Modals/ModalHeader.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

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
        (eventType) => eventType.name.toLowerCase().includes(query)
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
    saving.value = true;
    errorMessage.value = '';
    try {
        await axios.post(route('bi.tags.sync-event-types', props.tag.id), {
            event_type_ids: Array.from(selectedIds.value),
        });
        emit('close', true);
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? error.message;
    } finally {
        saving.value = false;
    }
};
</script>
