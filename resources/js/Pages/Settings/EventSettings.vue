<template>
    <app-layout :title="$t('Event Settings')">

           <EventSettingHeader>
               <div>
                   <div class="flex items-center justify-between mb-5">
                       <h2 class="headline2 my-2">{{$t('Event Types')}}</h2>
                       <GlassyIconButton icon="IconPlus" @click="openAddEventTypeModal" :text="$t('New Event Type')"/>
                       <div v-if="this.$page.props.show_hints" class="flex mt-1">
                           <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                           <span class="hind ml-1 my-auto">{{$t('Create new Event Types')}}</span>
                       </div>
                   </div>
                   <div class="xsLight mt-2">
                       {{ $t('Define event types to which events can be assigned later. You can also define whether they must be assigned to projects or whether they can have their own individual appointment name.')}}
                   </div>
               </div>
               <ul role="list" class="mt-5">
                   <li v-for="(eventType,index) in event_types" :key="eventType.id" class="flex justify-between">
                       <div class="flex my-4">
                           <div>
                               <div class="block w-16 h-16 rounded-full" :style="{'backgroundColor' : eventType.hex_code }" />
                           </div>
                           <div class="ml-5 my-auto w-full justify-start mr-6">
                               <div class="flex my-auto">
                                   <p class="mDark">{{ eventType.name }}</p>
                                   <div class="ml-2 mDark">({{eventType.abbreviation}})</div>
                               </div>
                               <div class="flex mt-2 divide-x space-x-2">
                                   <div class="xxsLight">
                                       {{
                                           eventType.project_mandatory ? $t('Project assignment mandatory') : $t('Project assignment optional')
                                       }}
                                   </div>
                                   <div class="xxsLight pl-2">
                                       {{
                                           eventType.individual_name ? $t('individual event name mandatory') : $t('individual event name optional')
                                       }}
                                   </div>
                                   <div class="xxsLight pl-2" v-if="eventType.relevant_for_project_period">
                                       {{
                                            $t('Relevant for project period')
                                       }}
                                   </div>
                               </div>
                               <div class="text-gray-400 font-lexend font-extralight text-xs">
                                   {{ $t('Verification mode')}}: {{ $t(eventType.verification_mode) }}<span v-if="eventType.users?.length > 0">: </span>
                                   <span>
                                        {{ eventType.users.map((user) => { return user.name }).join(', ') }}
                                    </span>
                               </div>
                           </div>
                       </div>
                       <div class="flex items-center">
                           <BaseMenu has-no-offset white-menu-background>
                               <BaseMenuItem title="Edit event type" white-menu-background @click="openEditEventTypeModal(eventType)" />
                               <BaseMenuItem v-if="index !== 0" title="Delete event type" icon="IconTrash" white-menu-background @click="openDeleteEventTypeModal(eventType)" />
                           </BaseMenu>
                       </div>
                   </li>
               </ul>
           </EventSettingHeader>

        <AddEditEventTypeModal
            :event-type="eventTypeToEdit"
            v-if="addingEventType"
            @close="closeAddEventTypeModal"
        />

        <DeleteEventTypeConfirmationModal
            v-if="deletingEventType"
            :event-type="eventTypeToDelete"
            @closed="closeDeleteEventTypeModal"
            @delete="deleteEventType"
        />
        <DeleteStandardEventTypeModal
            v-if="deletingUndefined"
            :event-type="eventTypeToDelete"
            @closed="closeDeletingUndefined"
        />
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import EventSettingHeader from "@/Pages/Settings/EventSettingComponents/EventSettingHeader.vue";
import {ColorPicker} from "vue3-colorpicker";
import AddEditEventTypeModal from "@/Pages/Settings/EventType/Components/Modals/AddEditEventTypModal.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import DeleteEventTypeConfirmationModal from "@/Pages/Settings/EventType/Components/Modals/DeleteEventTypeConfirmationModal.vue";
import DeleteStandardEventTypeModal from "@/Pages/Settings/EventType/Components/Modals/DeleteStandardEventTypeModal.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
export default {
    mixins: [Permissions],
    computed: {
        iconMenuItems() {
            return [
                {
                    iconName: 'eventType1',
                },
                {
                    iconName: 'eventType2',
                },
                {
                    iconName: 'eventType3',
                },
                {
                    iconName: 'eventType4',
                },
                {
                    iconName: 'eventType5',
                },
                {
                    iconName: 'eventType6',
                },
                {
                    iconName: 'eventType7',
                },
                {
                    iconName: 'eventType8',
                },
                {
                    iconName: 'eventType9',
                },
                {
                    iconName: 'eventType10',
                },
            ]
        }
    },
    components: {
        BaseMenuItem,
        GlassyIconButton,
        ColorPicker,
        EventSettingHeader,
        TextInputComponent,
        ModalHeader,
        BaseModal,
        BaseMenu,
        ColorPickerComponent,
        FormButton,
        AddButtonBig,
        AppLayout,
        XCircleIcon,
        PlusSmIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetDialogModal,
        ChevronDownIcon,
        DotsVerticalIcon,
        TrashIcon,
        PencilAltIcon,
        XIcon,
        AddEditEventTypeModal,
        DeleteEventTypeConfirmationModal,
        DeleteStandardEventTypeModal
    },
    props: ['event_types'],
    data() {
        return {
            addingEventType: false,
            editingEventType: false,
            deletingEventType: false,
            deletingUndefined: false,
            eventTypeToDelete: null,
            takenEventTypeColors: [],
            eventTypeForm: this.$inertia.form({
                _method: 'POST',
                name: '',
                project_mandatory: false,
                individual_name: false,
                abbreviation: '',
                hex_code: '#EC7A3D',
                relevant_for_project_period: false
            }),
            editEventTypeForm: this.$inertia.form({
                _method: 'PATCH',
                hex_code: '',
                name: '',
                project_mandatory: false,
                individual_name: false,
                abbreviation: '',
                relevant_for_project_period: false,
                id: null
            }),
            eventTypeToEdit: null,
        }
    },
    methods: {
        addColor(color) {
            this.eventTypeForm.hex_code = color;
        },
        updateColor(color) {
            this.editEventTypeForm.hex_code = color;
        },
        openAddEventTypeModal() {
            this.addingEventType = true;
        },
        openEditEventTypeModal(eventType) {
            this.eventTypeToEdit = eventType;
            this.addingEventType = true
        },
        closeEditEventTypeModal() {
            this.editEventTypeForm.hex_code = "";
            this.editEventTypeForm.name = "";
            this.editEventTypeForm.id = null;
            this.editEventTypeForm.project_mandatory = false;
            this.editEventTypeForm.individual_name = false;
            this.editEventTypeForm.relevant_for_project_period = false;
            this.editingEventType = false;
        },
        closeAddEventTypeModal() {
            this.addingEventType = false;
            this.eventTypeToEdit = null;
        },
        addEventType() {
            this.eventTypeForm.post(route('event_types.store'));
            this.closeAddEventTypeModal();
        },
        editEventType() {
            this.editEventTypeForm.patch(route('event_types.update', {event_type: this.editEventTypeForm.id}));
            this.closeEditEventTypeModal();
        },
        openDeleteEventTypeModal(eventType) {
            this.eventTypeToDelete = eventType;
            if (this.eventTypeToDelete.id === 1) {
                this.deletingUndefined = true;
            }
            this.deletingEventType = true;
        },
        closeDeletingUndefined() {
            this.deletingUndefined = false;
                this.eventTypeToDelete = null
        },
        closeDeleteEventTypeModal() {
            this.deletingEventType = false;
            this.eventTypeToDelete = null;
        },
        deleteEventType() {
            this.$inertia.delete(`../event_types/${this.eventTypeToDelete.id}`, {
                onSuccess: () => {
                    this.closeDeleteEventTypeModal();
                }
            });
        }
    },
    setup() {
        return {}
    }
}
</script>

<style>
input[type=color] {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch {
    border-radius: 100%;
    border: 1px solid transparent;
}

input[type=color]::-webkit-color-swatch-wrapper {
    border-radius: 100%;
    border: 1px solid transparent;
}

</style>
