<template>
    <ArtworkBaseModal @close="$emit('close')" :title="!globalQualificationForm ? $t('Create global qualification') : $t('Edit global qualification')"
                      :description="!globalQualificationForm ? $t('You can create a global qualification here.') : $t('Here you can edit the global qualification {0}.', [globalQualification.name])">
        <div class="mx-4">
            <div class="flex items-center gap-4">
                <IconSelector @update:modelValue="addIconToForm" :current-icon="globalQualificationForm ? globalQualificationForm.icon : null" />
                <div class="w-full">
                    <BaseInput
                        no-margin-top
                        id="name"
                        v-model="globalQualificationForm.name"
                        :label="$t('Name of the global qualification')"
                    />
                </div>
            </div>
            <div class="flex items-center justify-end mt-5">
                <BaseUIButton
                    @click="save"
                    :disabled="globalQualificationForm.icon === null || globalQualificationForm.name === null"
                    :label="!globalQualification ? $t('Create') : $t('Save')"
                    is-add-button
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    globalQualification: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            icon: null,
        }),
    }
})

const emit = defineEmits(['close']);


const globalQualificationForm = useForm({
    id: props.globalQualification.id,
    name: props.globalQualification.name,
    icon: props.globalQualification.icon,
    available: props.globalQualification.available ?? true,
});


const addIconToForm = (iconName) => {
    globalQualificationForm.icon = iconName;
}

const save = () => {
    if (props.globalQualification && props.globalQualification.id) {
        globalQualificationForm.patch(route('global-qualification.update', {globalQualification: props.globalQualification.id}), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                emit('close', false);
            },
        })
    } else {
        globalQualificationForm.post(route('global-qualification.store'),
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    emit('close', false);
                },
            });
    }
}
</script>
<style scoped>

</style>
