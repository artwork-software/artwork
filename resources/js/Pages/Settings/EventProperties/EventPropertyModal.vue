<template>
    <BaseModal @closed="emits.call(this, 'closeModal')">
        <ModalHeader :title="eventPropertyToEdit ? $t('Event-Eigenschaft bearbeiten') : $t('Event-Eigenschaft erstellen')"/>
        <form @submit.prevent="addOrUpdateEventProperty" class="mt-5">
            <div class="flex items-center gap-x-4">
                <div class="col-span-1">
                    <Menu as="div" class="relative col-span-1">
                        <div>
                            <MenuButton :class="[eventPropertyForm.icon === '' ? 'border border-gray-400' : '']" class="menu-button">
                                <label v-if="eventPropertyForm.icon === null" class="cursor-pointer text-gray-400 text-xs">
                                    {{$t('Icon')}}*
                                </label>
                                <component v-if="eventPropertyForm.icon"
                                           as="div"
                                           class="h-6 w-6 "
                                           width="16" height="16"
                                           :is="eventPropertyForm.icon"
                                           stroke-width="2"/>
                                <ChevronDownIcon class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from" enter-to-class="transition-enter-to" leave-active-class="transition-leave-active" leave-from-class="transition-leave-from" leave-to-class="transition-leave-to">
                            <MenuItems class="z-40 origin-top-right absolute h-56 w-24 overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="icon in icons"
                                          v-slot="{ active }">

                                    <div @click="eventPropertyForm.icon = icon;"
                                         :class="[
                                             active ?
                                             'bg-primaryHover text-secondaryHover' :
                                             'text-secondary',
                                             'cursor-pointer group px-3 py-2 text-sm subpixel-antialiased flex items-center justify-center'
                                         ]">
                                        <component as="div" class="h-12 w-12 rounded rounded-full border border-gray-300 p-2"
                                                   width="16" height="16"
                                                   :is="icon"
                                                   stroke-width="2"/>
                                    </div>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
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
</script>
