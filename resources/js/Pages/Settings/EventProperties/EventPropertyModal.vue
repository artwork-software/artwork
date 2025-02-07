<template>
    <BaseModal @closed="emits.call(this, 'closeModal')">
        <div class="-mt-8">
            <ModalHeader :title="eventPropertyToEdit ? $t('Event-Eigenschaft bearbeiten') : $t('Event-Eigenschaft erstellen')" description="sdggsjdgoföasjdg"/>
        </div>
        <form @submit.prevent="addOrUpdateEventProperty" class="mt-5">
            <div class="flex items-center gap-x-4">
                <div class="col-span-1">
                    <IconSelector @update:modelValue="addIconToForm" :current-icon="eventPropertyToEdit ? eventPropertyToEdit.icon : null" />
                </div>
                <div class="w-full">
                    <TextInputComponent
                        id="name"
                        v-model="eventPropertyForm.name"
                        label="Name"
                        :show-label="true"
                        required
                        no-margin-top
                    />
                </div>
            </div>
            <div class="flex items-center justify-center mt-5">
                <FormButton type="submit" :text="eventPropertyToEdit ? $t('Save') : $t('Create')" />
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
import {ChevronDownIcon} from "@heroicons/vue/outline";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import Label from "@/Jetstream/Label.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {inject} from "vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
const props = defineProps({
        eventPropertyToEdit: {
            type: Object,
            required: false,
            default: null
        }
    }),
    icons = [
        'IconUsersGroup',
        'IconSpeakerphone'
    ],
    availableEventProperties = inject('event_properties'),
    emits = defineEmits(["closeModal"]),
    eventPropertyForm = useForm({
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
                    onFinish: onFinish,
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
                onFinish: onFinish,
            }
        );

        eventPropertyForm.reset();

        emits.call(this, 'closeModal');
    };

const addIconToForm = (icon) => {
    eventPropertyForm.icon = icon;
}
</script>
