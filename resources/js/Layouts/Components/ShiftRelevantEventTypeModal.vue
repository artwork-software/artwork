<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
            <div class="headline1">
                {{ $t('Shift-relevant dates') }}
            </div>
            <div class="xsLight my-4">
                {{ $t('Define the appointment types for which shifts are to be assigned in this project.') }}
            </div>
            <Menu as="div" class="inline-block text-left w-full">
                <div>
                    <MenuButton
                        class="h-12 inputMain w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
                    >
                        <span class="float-left flex xsLight subpixel-antialiased">
                            {{ $t('Select appointment properties') }}
                        </span>
                        <ChevronDownIcon
                            class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                            aria-hidden="true"
                        />
                    </MenuButton>
                </div>
                <transition
                    enter-active-class="transition duration-50 ease-out"
                    enter-from-class="transform scale-100 opacity-100"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <MenuItems
                        class="absolute overflow-y-auto h-48 mt-2 w-[88%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                        <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                            <div class="flex w-full mb-4" v-for="eventType in accessibleEventTypes">
                                <input v-model="shiftRelevantEventTypeIds"
                                       :id="eventType.id"
                                       :value="eventType.id"
                                       type="checkbox"
                                       class="checkBoxOnDark"/>
                                <div :class="[shiftRelevantEventTypeIds.includes(eventType.id) ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                    {{ eventType.name }}
                                </div>
                            </div>
                        </div>
                    </MenuItems>
                </transition>
            </Menu>
            <div>
                <div class="flex py-2">
                    <div v-for="id in shiftRelevantEventTypeIds">
                        <TagComponent :displayed-text="this.eventTypes.find(eventType => eventType.id === id).name"
                                      hideX="true"
                        />
                    </div>
                </div>
            </div>
            <div class="flex justify-center mt-2">
                <FormButton :text="$t('Save')" @click="changeShiftRelevantEventTypes"/>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon, DownloadIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "ShiftRelevantEventTypeModal",
    props: {
        show: Boolean,
        project: Object,
        eventTypes: Array
    },
    components: {
        BaseModal,
        FormButton,
        TagComponent,
        ChevronDownIcon, Input,
        JetDialogModal,
        JetInputError,
        XIcon,
        DownloadIcon,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem
    },
    computed:{
        accessibleEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(type.id !== 1){
                    types.push(type)
                }
            })
            return types;
        }
    },
    emits: ['closeModal'],
    data() {
        return {
            shiftRelevantEventTypeIds: [],
        }
    },
    created() {
        if (this.project.shift_relevant_event_types) {
            this.shiftRelevantEventTypeIds = this.project.shift_relevant_event_types.map(eventType => eventType.id);
        }
    },
    methods: {
        changeShiftRelevantEventTypes() {
            this.$inertia.patch(route('projects.update.shift_event_types', {project: this.project.id}), {
                shiftRelevantEventTypeIds: this.shiftRelevantEventTypeIds
            });
            this.$emit('closeModal')        }
    }
}
</script>
