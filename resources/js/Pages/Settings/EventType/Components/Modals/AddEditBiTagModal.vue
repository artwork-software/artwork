<template>
    <ArtworkBaseModal
        :title="tag ? 'Edit BI tag' : 'New BI tag'"
        modal-size="sm:max-w-lg"
        @close="$emit('close', false)"
    >
        <form id="biTagForm" class="space-y-4" @submit.prevent="save">
            <div class="flex items-end gap-x-4">
                <div class="shrink-0">
                    <span class="mb-1 block font-lexend text-xs font-medium text-text-muted">{{ $t('Color') }}</span>
                    <ColorPickerComponent :color="tagForm.color" @update-color="updateColor" />
                </div>
                <div class="w-full">
                    <BaseInput id="bi_tag_name_de" v-model="tagForm.name_de" label="Display name (DE)" required autofocus />
                </div>
            </div>
            <BaseInput id="bi_tag_name" v-model="tagForm.name" label="Internal name" required />
            <div>
                <ArtworkBaseListbox
                    :model-value="selectedRole"
                    :items="roleOptions"
                    by="id"
                    option-label="name"
                    label="Controls key figure"
                    :enable-search="false"
                    @update:model-value="value => tagForm.kpi_role = value?.id ?? null"
                />
                <p class="mt-1.5 text-xs text-text-muted">
                    {{ $t('Exactly one tag can control “Performances” (also the capacity basis of the occupancy rate) and one can control “Event days”. Without an assigned tag these key figures stay empty.') }}
                </p>
            </div>
            <BaseAlertComponent v-if="errorMessage" type="error" :message="errorMessage" />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="saving"
                @click="$emit('close', false)"
            />
            <BaseUIButton
                type="submit"
                form="biTagForm"
                :label="tag ? 'Save' : 'Add'"
                variant="primary"
                :icon="tag ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="saving"
                :disabled="saving || !tagForm.name.trim() || !tagForm.name_de.trim()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import { useTranslation } from '@/Composeables/Translation.js';
import { BI_REQUEST_CONFIG, extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js';

const props = defineProps({
    tag: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const t = useTranslation();

const tagForm = ref({
    name: props.tag?.name ?? '',
    name_de: props.tag?.name_de ?? '',
    color: props.tag?.color ?? '#6366f1',
    kpi_role: props.tag?.kpi_role ?? null,
});

const roleOptions = [
    { id: null, name: t('No key figure (counting only)') },
    { id: 'performance', name: t('Performances') },
    { id: 'event_day', name: t('Event days') },
];
const selectedRole = computed(() => roleOptions.find(option => option.id === tagForm.value.kpi_role) ?? roleOptions[0]);

const saving = ref(false);
const errorMessage = ref('');

const updateColor = (color) => {
    tagForm.value.color = color;
};

const save = async () => {
    if (saving.value || !tagForm.value.name.trim() || !tagForm.value.name_de.trim()) {
        return;
    }
    saving.value = true;
    errorMessage.value = '';
    try {
        if (props.tag) {
            await axios.put(route('bi.tags.update', props.tag.id), tagForm.value, BI_REQUEST_CONFIG);
        } else {
            await axios.post(route('bi.tags.store'), tagForm.value, BI_REQUEST_CONFIG);
        }
        emit('close', true);
    } catch (error) {
        // Fehler steht im Modal (BI_REQUEST_CONFIG unterdrückt den globalen Toast)
        errorMessage.value = extractSaveErrorMessage(error) ?? t('An error has occurred');
    } finally {
        saving.value = false;
    }
};
</script>
