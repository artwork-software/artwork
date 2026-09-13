<template>
    <BaseModal @closed="$emit('close', false)">
        <ModalHeader
            :title="tag ? $t('Edit BI tag') : $t('New BI tag')"
        />
        <form @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="col-span-1">
                    <div class="-mt-1">
                        <div class="text-sm/5 font-bold text-text-subtle flex items-center justify-start">
                            {{ $t('Color') }}
                        </div>
                        <div class="mt-1 flex items-center justify-center">
                            <ColorPickerComponent @updateColor="updateColor" :color="tagForm.color" />
                        </div>
                    </div>
                </div>
                <div class="col-span-4">
                    <BaseInput id="bi_tag_name_de" v-model="tagForm.name_de" type="text" :label="$t('Display name (DE)') + '*'" required />
                </div>
                <div class="col-span-full">
                    <BaseInput id="bi_tag_name" v-model="tagForm.name" type="text" :label="$t('Internal name') + '*'" required />
                </div>
                <div class="col-span-full">
                    <ArtworkBaseListbox
                        :model-value="selectedRole"
                        @update:model-value="value => tagForm.kpi_role = value?.id ?? null"
                        :items="roleOptions"
                        by="id"
                        option-label="name"
                        :label="$t('Controls key figure')"
                    />
                    <p class="mt-1.5 text-xs text-text-subtle">
                        {{ $t('Exactly one tag can control “Performances” (also the capacity basis of the occupancy rate) and one can control “Event days”. Without an assigned tag these key figures stay empty.') }}
                    </p>
                </div>
                <div v-if="errorMessage" class="col-span-full text-sm/5 text-danger">
                    {{ errorMessage }}
                </div>
            </div>
            <div class="flex items-center justify-center mt-6">
                <BaseUIButton
                    type="submit"
                    :label="tag ? $t('Save') : $t('Add')"
                    is-add-button
                    :processing="saving"
                    :disabled="saving || !tagForm.name || !tagForm.name_de"
                />
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue';
import BaseModal from '@/Components/Modals/BaseModal.vue';
import ModalHeader from '@/Components/Modals/ModalHeader.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import { computed } from 'vue';
import { useTranslation } from '@/Composeables/Translation.js';

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
    if (!tagForm.value.name || !tagForm.value.name_de) return;
    saving.value = true;
    errorMessage.value = '';
    try {
        if (props.tag) {
            await axios.put(route('bi.tags.update', props.tag.id), tagForm.value);
        } else {
            await axios.post(route('bi.tags.store'), tagForm.value);
        }
        emit('close', true);
    } catch (error) {
        const errors = error.response?.data?.errors;
        errorMessage.value = (errors && Object.values(errors).flat()[0]) ?? error.response?.data?.message ?? error.message;
    } finally {
        saving.value = false;
    }
};
</script>
