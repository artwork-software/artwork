<template>
    <BaseModal @closed="emits.call(this, 'closeModal')">
        <div class="-mt-8">
            <ModalHeader :title="eventPropertyToEdit ? $t('Event-Eigenschaft bearbeiten') : $t('Event-Eigenschaft erstellen')"/>
        </div>
        <form @submit.prevent="addOrUpdateEventProperty" class="mt-5">
            <div class="flex items-center gap-x-4">
                <div class="col-span-1">
                    <IconSelector @update:modelValue="addIconToForm" :current-icon="eventPropertyToEdit ? eventPropertyToEdit.icon : null" />
                </div>
                <div class="w-full">
                    <BaseInput
                        id="name"
                        v-model="eventPropertyForm.name"
                        label="Name"
                        :show-label="true"
                        required
                        no-margin-top
                    />
                </div>
            </div>
            <div v-if="eventPropertyForm.errors">
                <div class="text-red-500 text-sm mt-2" v-for="error in eventPropertyForm.errors" :key="error">
                    {{ error }}
                </div>
            </div>
            <div class="flex items-center justify-center mt-5">
                <FormButton type="submit" :text="eventPropertyToEdit ? $t('Save') : $t('Create')" :disabled="eventPropertyForm.processing" />
            </div>

        </form>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useForm} from "@inertiajs/vue3";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
        eventPropertyToEdit: {
            type: Object,
            required: false,
            default: null
        }
    }),

    emits = defineEmits(["closeModal"]),
    eventPropertyForm = useForm({
        id: props.eventPropertyToEdit ? props.eventPropertyToEdit.id : null,
        name: props.eventPropertyToEdit ? props.eventPropertyToEdit.name : '',
        icon: props.eventPropertyToEdit ? props.eventPropertyToEdit.icon : null,
    }),
    addOrUpdateEventProperty = () => {


        let onFinish = () => {
            emits.call(this, 'closeModal');

            eventPropertyForm.reset();
        }

        if (props.eventPropertyToEdit === null) {
            eventPropertyForm.post(
                route('event_settings.event_properties.store'),
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: onFinish,
                }
            );

            return;
        }

        eventPropertyForm.patch(
            route(
                'event_settings.event_properties.update',
                {
                    id: props.eventPropertyToEdit.id
                }
            ),
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: onFinish,
            }
        );

        eventPropertyForm.reset();

        emits.call(this, 'closeModal');
    };

const addIconToForm = (icon) => {
    eventPropertyForm.icon = icon;
}
</script>
