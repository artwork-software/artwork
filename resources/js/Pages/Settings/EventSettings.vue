<template>
    <app-layout>
        <div class="max-w-screen-lg ml-14 mr-40">
            <div class="">
                <h2 class="headline1">{{$t('Event Settings')}}</h2>
                <div class="xsLight mt-2">
                    {{ $t('Set global event settings.')}}
                </div>
            </div>
            <div class="mt-4 max-w-2xl">
                <div class="flex">
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
            <ul role="list" class="mt-4 mb-20 w-full">
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
                            <div class="flex mt-2">
                                <div class="xsLight mr-2">
                                    {{
                                        eventType.project_mandatory ? $t('Project assignment mandatory') : $t('Project assignment optional')
                                    }}
                                </div>
                                <div class="xsLight">
                                    |
                                </div>
                                <div class="xsLight ml-2">
                                    {{
                                        eventType.individual_name ? $t('individual event name mandatory') : $t('individual event name optional')
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <Menu as="div" class="my-auto relative">
                            <div class="flex" v-if="index !== 0">
                                <MenuButton
                                    class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon
                                        class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                        aria-hidden="true"/>
                                </MenuButton>
                                <div v-if="this.$page.props.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                    <div>
                                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    </div>
                                    <div class="flex" v-if="index === 1">
                                        <span
                                            class="hind ml-2 text-secondary tracking-tight text-lg">{{$t('Edit an event type')}}</span>
                                    </div>
                                </div>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="z-10 origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <a href="#" @click="openEditEventTypeModal(eventType)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                {{$t('Edit event type')}}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a href="#" @click="openDeleteEventTypeModal(eventType)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                {{$t('Delete event type')}}
                                            </a>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>

                    </div>
                </li>
            </ul>
        </div>
        <!-- Termintyp erstellen Modal-->
        <jet-dialog-modal :show="addingEventType" @close="closeAddEventTypeModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('New event type')}}
                    </div>
                    <XIcon @click="closeAddEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-4">
                        <div class="flex items-center">
                            <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none h-14 w-14">
                                <input type="color" placeholder="Farbe" v-model="eventTypeForm.hex_code" class="rounded-full focus:outline-none h-14 w-14 object-cover" >
                            </div>

                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="name" v-model="eventTypeForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="name"
                                       class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{$t('Event type name*')}}</label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <input :placeholder="$t('Abbreviation of the event type')"
                                   v-model="eventTypeForm.abbreviation"
                                   class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div>
                            <div class="flex items-center mt-8">
                                <input v-model="eventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto ">{{$t('project assignment mandatory')}}</p>
                            </div>
                            <div class="flex items-center mt-4">
                                <input v-model="eventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[eventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto ">{{$t('individual event name mandatory')}}</p>
                            </div>
                        </div>
                        <div class="mt-2 w-full items-center text-center">
                            <FormButton
                                @click="addEventType"
                                :disabled="this.eventTypeForm.name === '' || this.eventTypeForm.svg_name === ''"
                                :text="$t('Create event type')" />
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp bearbeiten Modal-->
        <jet-dialog-modal :show="editingEventType" @close="closeEditEventTypeModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Edit event type')}}
                    </div>
                    <XIcon @click="closeEditEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <div class="justify-content-center relative items-center flex cursor-pointer rounded-full focus:outline-none">
                                <input type="color" v-model="editEventTypeForm.hex_code" class="rounded-full focus:outline-none h-14 w-14" >
                            </div>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="editCategoryName" v-model="editEventTypeForm.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="editCategoryName"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">{{$t('Name')}}</label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <input :placeholder="editEventTypeForm.abbreviation !== '' ? editEventTypeForm.abbreviation : $t('Abbreviation of the event type')"
                                   v-model="editEventTypeForm.abbreviation"
                                   class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div>
                            <div class="flex items-center mt-8">
                                <input v-model="editEventTypeForm.project_mandatory"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.project_mandatory ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto">{{$t('project assignment mandatory')}}</p>
                            </div>
                            <div class="flex items-center mt-4">
                                <input v-model="editEventTypeForm.individual_name"
                                       type="checkbox"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <p :class="[editEventTypeForm.individual_name ? 'xsDark' : 'xsLight']"
                                   class="ml-4 my-auto">{{$t('individual event name mandatory')}}</p>
                            </div>
                        </div>
                        <div class="mt-8 w-full justify-center flex">
                            <FormButton
                                @click="editEventType"
                                :disabled="this.editEventTypeForm.name === ''"
                                :text="$t('Save')"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Termintyp löschen Modal -->
        <jet-dialog-modal :show="deletingEventType" @close="closeDeleteEventTypeModal">
            <template #content>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Delete event type')}}
                    </div>
                    <XIcon @click="closeDeleteEventTypeModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        {{$t('Are you sure you want to delete the event type {eventType} from the system? All events that are assigned to this type will be set to "undefined".', {eventType: eventTypeToDelete.name})}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-buttonBlue hover:bg-buttonHover focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteEventType">
                            {{$t('Delete')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteEventTypeModal"
                                  class="xsLight cursor-pointer">{{$t('No, actually not')}}</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <jet-dialog-modal :show="deletingUndefined" @close="closeDeletingUndefined">
            <template #content>
                <div class="mx-4">
                    <div class="headline1">
                        {{$t('Delete event type')}}
                    </div>
                    <XIcon @click="closeDeletingUndefined"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
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
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, TrashIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import Permissions from "@/mixins/Permissions.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ColorPicker from 'primevue/colorpicker';

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
        ColorPicker
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
            }),
            editEventTypeForm: this.$inertia.form({
                _method: 'PATCH',
                hex_code: '',
                name: '',
                project_mandatory: false,
                individual_name: false,
                abbreviation: '',
                id: null
            })
        }
    },
    methods: {
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
            this.editingEventType = true;
        },
        closeEditEventTypeModal() {
            this.editEventTypeForm.hex_code = "";
            this.editEventTypeForm.name = "";
            this.editEventTypeForm.id = null;
            this.editEventTypeForm.project_mandatory = false;
            this.editEventTypeForm.individual_name = false;
            this.editingEventType = false;
        },
        closeAddEventTypeModal() {
            this.addingEventType = false;
            this.eventTypeForm.name = "";
            this.eventTypeForm.hex_code = "";
            this.eventTypeForm.project_mandatory = false;
            this.eventTypeForm.individual_name = false;
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
            this.deletingUndefined = false,
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
