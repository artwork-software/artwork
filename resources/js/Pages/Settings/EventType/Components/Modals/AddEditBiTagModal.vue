<template>
    <BaseModal @closed="$emit('close', false)">
        <ModalHeader
            :title="tag ? $t('Edit BI tag') : $t('New BI tag')"
        />
        <form @submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="col-span-1">
                    <div class="-mt-1">
                        <div class="xsLight flex items-center justify-start">
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
                <div v-if="errorMessage" class="col-span-full errorText">
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

const props = defineProps({
    tag: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const tagForm = ref({
    name: props.tag?.name ?? '',
    name_de: props.tag?.name_de ?? '',
    color: props.tag?.color ?? '#6366f1',
});

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
        errorMessage.value = error.response?.data?.message ?? error.message;
    } finally {
        saving.value = false;
    }
};
</script>
