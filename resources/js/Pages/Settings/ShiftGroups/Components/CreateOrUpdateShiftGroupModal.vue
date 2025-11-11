<template>
    <ArtworkBaseModal @close="$emit('close')" :title="shiftGroup?.id ? $t('Edit Shift Group') : $t('Create Shift Group')" :description="shiftGroup?.id ? $t('Edit the details of the shift group.') : $t('Create a new shift group to categorize shifts.')" >

        <div class="mx-4">
            <div class="flex items-center gap-4">
                <IconSelector @update:modelValue="addIconToForm" :current-icon="shiftGroupForm ? shiftGroupForm.icon : null" />
                <ColorPickerComponent :color="shiftGroupForm.color" @updateColor="onPickColor"/>
                <div class="w-full">
                    <BaseInput
                        no-margin-top
                        id="name"
                        v-model="shiftGroupForm.name"
                        :label="$t('Name of the global qualification')"
                    />
                </div>
            </div>
            <div class="flex items-center justify-end mt-5">
                <BaseUIButton
                    @click="save"
                    :disabled="shiftGroupForm.name === null"
                    :label="!shiftGroup?.id ? $t('Create') : $t('Save')"
                    is-add-button
                />
            </div>
        </div>

    </ArtworkBaseModal>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'

const props = defineProps({
    shiftGroup: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            color: '#9E1C60',
            icon: null
        }),
    }
});

const emit = defineEmits(['close']);

const shiftGroupForm = useForm({
    id: props.shiftGroup.id,
    name: props.shiftGroup.name,
    color: props.shiftGroup.color,
    icon: props.shiftGroup.icon
})

const addIconToForm = (iconName) => {
    shiftGroupForm.icon = iconName;
}

const onPickColor = (color) => {
    shiftGroupForm.color = color;
}

const save = () => {
    if (shiftGroupForm.id) {
        shiftGroupForm.put(route('shift-groups.update', {shift_group: shiftGroupForm.id}), {
            onSuccess: () => {
                emit('close');
            }
        });
    } else {
        shiftGroupForm.post(route('shift-groups.store'), {
            onSuccess: () => {
                emit('close');
            }
        });
    }
}
</script>
<style scoped>

</style>
