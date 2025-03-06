<template>
    <app-layout :title="$t('Event Settings')">

           <EventSettingHeader>
               <div class="mt-4 max-w-3xl">
                   <div class="flex items-center justify-between mb-5">
                       <h2 class="headline2 my-2">{{$t('Event Types')}}</h2>
                       <AddButtonBig @click="openAddEventTypeModal" :text="$t('New Event Type')"/>
                       <div v-if="this.$page.props.show_hints" class="flex mt-1">
                           <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                           <span class="hind ml-1 my-auto">{{$t('Create new Event Types')}}</span>
                       </div>
                   </div>
                   <div class="xsLight mt-2">
                       {{ $t('Define event types to which events can be assigned later. You can also define whether they must be assigned to projects or whether they can have their own individual appointment name.')}}
                   </div>
               </div>
               <ul role="list" class="mt-4 mb-20 w-full max-w-3xl">
                   <li v-for="(eventType,index) in event_types" :key="eventType.id"
                       class="flex justify-between">
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
                           </div>
                       </div>
                       <div class="flex items-center">
                           <BaseMenu has-no-offset>
                               <MenuItem v-slot="{ active }">
                                   <a href="#" @click="openEditEventTypeModal(eventType)"
                                      :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                       <PencilAltIcon
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                       {{$t('Edit event type')}}
                                   </a>
                               </MenuItem>
                               <MenuItem v-if="index !== 0" v-slot="{ active }">
                                   <a href="#" @click="openDeleteEventTypeModal(eventType)"
                                      :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                       <TrashIcon
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                       {{$t('Delete event type')}}
                                   </a>
                               </MenuItem>
                           </BaseMenu>
                       </div>
                   </li>
               </ul>
           </EventSettingHeader>


        <!-- Termintyp erstellen Modal-->
        <BaseModal @closed="closeAddEventTypeModal" v-if="addingEventType" modal-image="/Svgs/Overlays/illu_appointment_new.svg">
                <div class="mx-4">
                    <ModalHeader
                        :title="$t('New event type')"
                    />
                    <form @submit.prevent="addEventType" class="grid grid-cols-1 gap-4">
                        <div class="flex items-center">
                            <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none h-14 w-14">
                                <ColorPickerComponent @updateColor="addColor" color="#ccc" />
                            </div>

                            <div class="relative my-auto w-full ml-8">
                                <TextInputComponent id="name" v-model="eventTypeForm.name" type="text" :label="$t('Event type name*')" required/>
                            </div>
                        </div>
                        <div class="">
                            <TextInputComponent
                                :label="$t('Abbreviation of the event type')"
                                v-model="eventTypeForm.abbreviation"
                                required
                                id="abbreviation"
                            />
                        </div>
                        <div class="flex items-center">
                            <input v-model="eventTypeForm.project_mandatory"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[eventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto ">{{$t('project assignment mandatory')}}</p>
                        </div>
                        <div class="flex items-center">
                            <input v-model="eventTypeForm.individual_name"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[eventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto ">{{$t('individual event name mandatory')}}</p>
                        </div>
                        <div class="flex items-center">
                            <input v-model="eventTypeForm.relevant_for_project_period"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[eventTypeForm.relevant_for_project_period ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto ">{{$t('Relevant for project period')}}</p>
                        </div>
                        <div class="mt-5 w-full flex justify-center items-center text-center">
                            <FormButton
                                type="submit"
                                :disabled="this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === ''"
                                :text="$t('Create event type')" />
                        </div>
                    </form>
                </div>
        </BaseModal>
        <!-- Termintyp bearbeiten Modal-->
        <BaseModal @closed="closeEditEventTypeModal" v-if="editingEventType" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
                <div class="mx-4">
                    <ModalHeader
                        :title="$t('Edit event type')"
                    />
                    <form @submit.prevent="editEventType" class="grid grid-cols-1 gap-4">
                        <div class="flex items-center">
                            <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none h-14 w-14">
                                <ColorPickerComponent @updateColor="updateColor" :color="editEventTypeForm.hex_code" />
                            </div>

                            <div class="relative my-auto w-full ml-8">
                                <TextInputComponent id="name" v-model="editEventTypeForm.name" type="text" :label="$t('Event type name*')" required/>
                            </div>
                        </div>
                        <div class="">
                            <TextInputComponent
                                :label="$t('Abbreviation of the event type')"
                                v-model="editEventTypeForm.abbreviation"
                                required
                                id="abbreviation"
                            />
                        </div>
                        <div class="flex items-center ">
                            <input v-model="editEventTypeForm.project_mandatory"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[editEventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto">{{$t('project assignment mandatory')}}</p>
                        </div>
                        <div class="flex items-center">
                            <input v-model="editEventTypeForm.individual_name"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[editEventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto">{{$t('individual event name mandatory')}}</p>
                        </div>
                        <div class="flex items-center">
                            <input v-model="editEventTypeForm.relevant_for_project_period"
                                   type="checkbox"
                                   class="input-checklist"/>
                            <p :class="[editEventTypeForm.relevant_for_project_period ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto">{{$t('Relevant for project period')}}</p>
                        </div>

                        <div class="mt-8 w-full justify-center flex">
                            <FormButton
                                type="submit"
                                :disabled="this.editEventTypeForm.name === ''"
                                :text="$t('Save')"
                            />
                        </div>
                    </form>
                </div>
        </BaseModal>
        <!-- Termintyp löschen Modal -->
        <BaseModal @closed="closeDeleteEventTypeModal" v-if="deletingEventType" modal-image="/Svgs/Overlays/illu_appointment_edit.svg" :show-image="false">
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Delete event type')}}
                    </div>
                    <div class="errorText">
                        {{$t('Are you sure you want to delete the event type {eventType} from the system? All events that are assigned to this type will be set to "undefined".', {eventType: eventTypeToDelete.name})}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-white"
                                @click="deleteEventType">
                            {{$t('Delete')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteEventTypeModal"
                                  class="xsLight cursor-pointer">{{$t('No, actually not')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <BaseModal @closed="closeDeletingUndefined" v-if="deletingUndefined" modal-image="/Svgs/Overlays/illu_appointment_edit.svg" :show-image="false">
                <div class="mx-4">
                    <div class="headline1">
                        {{$t('Delete event type')}}
                    </div>
                    <div class="errorText">
                        {{$t('The event type {eventType} cannot be deleted because it is the standard event type.', {eventType: eventTypeToDelete.name})}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <div class="flex my-auto">
                            <span @click="closeDeletingUndefined"
                                  class="text-secondary subpixel-antialiased cursor-pointer">{{$t('Ok')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
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
            })
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
            this.editEventTypeForm.hex_code = eventType.hex_code;
            this.editEventTypeForm.name = eventType.name;
            this.editEventTypeForm.id = eventType.id;
            this.editEventTypeForm.project_mandatory = eventType.project_mandatory;
            this.editEventTypeForm.individual_name = eventType.individual_name;
            this.editEventTypeForm.abbreviation = eventType.abbreviation;
            this.editEventTypeForm.relevant_for_project_period = eventType.relevant_for_project_period;
            this.editingEventType = true;
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
            this.eventTypeForm.name = "";
            this.eventTypeForm.hex_code = "";
            this.eventTypeForm.project_mandatory = false;
            this.eventTypeForm.individual_name = false;
            this.eventTypeForm.relevant_for_project_period = false;
            this.eventTypeForm.abbreviation = '';
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
            this.$inertia.delete(`../event_types/${this.eventTypeToDelete.id}`);
            this.closeDeleteEventTypeModal();
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
