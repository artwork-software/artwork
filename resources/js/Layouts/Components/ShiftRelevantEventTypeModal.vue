<template>
    <ArtworkBaseModal @close="$emit('closeModal')" v-if="show" :title="$t('Shift-relevant events')"
                      :description="$t('Define the appointment types for which shifts are to be assigned in this project.')">
        <Menu as="div" class="inline-block text-left relative w-full">
            <div>
                <MenuButton
                    class="menu-button"
                >
                        <span class="float-left flex text-sm/5 font-bold text-text-subtle subpixel-antialiased">
                            {{ $t('Select appointment properties') }}
                        </span>
                    <IconChevronDown
                        class="ml-2 -mr-1 h-5 w-5 text-text float-right"
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
                    class="absolute overflow-y-auto h-48 mt-2 rounded-lg w-full origin-top-left divide-y divide-border-subtle bg-surface-inverse ring-1 ring-black p-2 text-white opacity-100 z-50">
                    <div class="mx-auto w-full bg-surface-inverse border-none mt-2">
                        <div class="flex w-full mb-4" v-for="eventType in accessibleEventTypes">
                            <input v-model="shiftRelevantEventTypeIds"
                                   :id="eventType.id"
                                   :value="eventType.id"
                                   type="checkbox"
                                   class="input-checklist-dark"/>
                            <div
                                :class="[shiftRelevantEventTypeIds.includes(eventType.id) ? 'text-sm/5 font-bold text-white' : 'text-sm/5 font-bold text-text-subtle', 'my-auto ml-2']">
                                {{ eventType.name }}
                            </div>
                        </div>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
        <div class="flex">
            <div class="py-2">
                <div v-for="id in shiftRelevantEventTypeIds">
                    <TagComponent :displayed-text="this.eventTypes.find(eventType => eventType.id === id).name"
                                  hideX="true"
                    />
                </div>
            </div>
        </div>
        <div class="flex justify-end mt-2">
            <BaseUIButton :label="$t('Save')" is-add-button @click="changeShiftRelevantEventTypes"/>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import {IconChevronDown, IconDownload, IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import Permissions from "@/Mixins/Permissions.vue";
import Input from "@/Jetstream/Input.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    mixins: [Permissions],
    name: "ShiftRelevantEventTypeModal",
    props: {
        show: Boolean,
        project: Object,
        eventTypes: Array
    },
    components: {
        BaseUIButton,
        ArtworkBaseModal,
        ModalHeader,
        BaseModal,
        FormButton,
        TagComponent,
        IconChevronDown, Input,
        JetDialogModal,
        JetInputError,
        IconX,
        IconDownload,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem
    },
    computed: {
        accessibleEventTypes() {
            const types = [];
            this.eventTypes.forEach((type) => {
                if (type.id !== 1) {
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
            this.$emit('closeModal')
        }
    }
}
</script>
