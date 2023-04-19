<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Termin bearbeiten" src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Termine verschieben
                        </div>
                    </h1>
                    <h2  class="xsLight mb-2">
                        Möchtest du alle selektierten Termine in einen anderen Raum oder um einen bestimmten Zeitraum verschieben?
                    </h2>
                </div>

                <div class="w-full">
                    <div class="mb-2">
                        <Listbox as="div" class="sm:col-span-3" v-model="selectedRoom">
                            <div class="relative">
                                <ListboxButton class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex justify-between items-center">
                                    <div v-if="selectedRoom === null">Keine Raumverschiebung</div>
                                    <div v-else> {{ selectedRoom?.name }}</div>
                                    <div class="mr-3">
                                        <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>

                                <ListboxOptions class="absolute w-full bg-primary shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   :value="null"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                            <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                Keine Raumverschiebung
                                            </div>
                                            <div v-if="selected">
                                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                            </div>
                                        </li>
                                    </ListboxOption>
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   v-for="room in rooms"
                                                   :key="room.id"
                                                   :value="room"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                            <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                {{ room.name }}
                                            </div>
                                            <div v-if="selected">
                                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                            </div>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </div>
                        </Listbox>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-8 mb-2">
                        <div class="mb-2 col-span-1">
                            <Listbox as="div" class="" v-model="selectedCalculationType">
                                <div class="relative">
                                    <ListboxButton class="w-full pl-3 h-12 inputMain bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex justify-between items-center">
                                        <div> {{ selectedCalculationType.type }}</div>
                                        <div class="mr-3">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                        </div>
                                    </ListboxButton>
                                    <ListboxOptions class="absolute w-full bg-primary shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none  z-10">
                                        <ListboxOption as="template" class="p-2 text-sm"
                                                       v-for="calculation in calculationTypes"
                                                       :key="calculation.id"
                                                       :value="calculation"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                                <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                    {{ calculation.type }}
                                                </div>
                                                <div v-if="selected">
                                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                                </div>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </div>
                            </Listbox>
                        </div>
                        <div class="mb-2 col-span-3">
                            <input type="number"
                                   v-model="editEvents.value"
                                   id="eventTitle"
                                   min="0" max="999"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="mb-2 col-span-4">
                            <Listbox as="div" v-model="selectedTimeType">
                                <div class="relative">
                                    <ListboxButton class="w-full pl-3 h-12 inputMain bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm flex justify-between items-center">
                                        <div> {{ selectedTimeType.value }}</div>
                                        <div class="mr-3">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                        </div>
                                    </ListboxButton>

                                    <ListboxOptions class="absolute bg-primary shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                        <ListboxOption as="template" class="p-2 text-sm"
                                                       v-for="time in timeTypes"
                                                       :key="time.id"
                                                       :value="time"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                                <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                    {{ time.value }}
                                                </div>
                                                <div v-if="selected">
                                                    <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                                </div>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </div>
                            </Listbox>
                        </div>
                    </div>

                    <div class="mb-2">
                        <input type="text"
                               v-model="editEvents.date"
                               :placeholder="editEvents.date === null ? 'Keine Verschiebung auf Datum' : '' "
                               onfocus="(this.type='date')"
                               id="eventTitle"
                               class="h-12 sDark inputMain placeholder:text-black placeholder:font-semibold text-sm placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                    </div>

                    <div class="w-full flex justify-center">
                        <AddButton mode="modal" class="!bg-buttonBlue hover:!bg-buttonHover text-white resize-none py-4 px-6" text="Speichern"  @click="saveMultiEdit"/>
                    </div>
                </div>
            </div>

        </template>
    </jet-dialog-modal>


</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import InputComponent from "@/Layouts/Components/InputComponent";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";

export default {
    name: 'MultiEditModal',

    components: {
        AddButton,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        EventTypeIconCollection,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        ConfirmationComponent,
        TagComponent,
        InputComponent
    },

    data() {
        return {
            calculationTypes: [
                {id: 1, type: '+'},
                {id: 2, type: '-'},
            ],
            timeTypes: [
                {id: 1, value: 'Stunde(n)'},
                {id: 2, value: 'Tag(e)'},
                {id: 3, value: 'Woche(n)'},
                {id: 4, value: 'Monat(e)'},
                {id: 5, value: 'Jahr(e)'},
            ],
            editEvents: useForm({
                events: this.checkedEvents,
                newRoomId: null,
                calculationType: null,
                value: 0,
                type:  null,
                date: null
            }),
            selectedCalculationType: {id: 1, type: '+'},
            selectedTimeType: {id: 1, value: 'Stunde(n)'},
            selectedRoom: null,
        }
    },
    props: ['checkedEvents', 'rooms'],
    emits: ['closed'],
    methods: {
        openModal() {

        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },

        saveMultiEdit(){
            this.editEvents.type = this.selectedTimeType.id;
            this.editEvents.calculationType = this.selectedCalculationType.id;
            this.editEvents.newRoomId = this.selectedRoom?.id;

            this.editEvents.patch(route('multi-edit.save'), {
                onSuccess: () => {
                    this.editEvents.newRoomId = null;
                    this.editEvents.calculationType = null;
                    this.editEvents.value = 0;
                    this.editEvents.type = null;
                    this.editEvents.date = null;
                    this.closeModal(true)
                },
                preserveScroll: true
            });
        }
    },
}
</script>

<style scoped></style>
