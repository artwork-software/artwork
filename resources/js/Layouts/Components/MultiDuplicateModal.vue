<template>
    <BaseModal @closed="closeModal" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
        <div class="mx-4">
            <!--   Heading   -->
            <ModalHeader
                :title="$t('Duplicate events')"
                :description="$t('Would you like to duplicate all selected appointments to another room or by a certain period of time?')"
            />
            <div class="w-full">
                <div class="mb-2">
                    <Listbox as="div" class="sm:col-span-3" v-model="selectedRoom">
                        <div class="relative">
                            <ListboxButton class="menu-button">
                                <span v-if="selectedRoom === null">{{ $t('No room displacement')}}</span>
                                <div v-else> {{ selectedRoom?.name }}</div>
                                <div class="mr-3">
                                    <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </div>
                            </ListboxButton>
                            <ListboxOptions class="absolute w-full bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                <ListboxOption as="template" class="p-2 text-sm"
                                               :value="null"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                        <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                            {{ $t('No room displacement')}}
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
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
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
                <div class="grid grid-cols-1 sm:grid-cols-8 mb-2 gap-x-2">
                    <div class="mb-2 col-span-1">
                        <Listbox as="div" class="" v-model="selectedCalculationType">
                            <div class="relative">
                                <ListboxButton class="menu-button">
                                    <div> {{ selectedCalculationType.type }}</div>
                                    <div class="mr-3">
                                        <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>
                                <ListboxOptions class="absolute w-full bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none  z-10">
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   v-for="calculation in calculationTypes"
                                                   :key="calculation.id"
                                                   :value="calculation"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
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
                               class="input h-12"/>
                    </div>
                    <div class="mb-2 col-span-4">
                        <Listbox as="div" v-model="selectedTimeType">
                            <div class="relative">
                                <ListboxButton class="menu-button">
                                    <div> {{ selectedTimeType.value }}</div>
                                    <div class="mr-3">
                                        <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>
                                <ListboxOptions class="absolute bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   v-for="time in timeTypes"
                                                   :key="time.id"
                                                   :value="time"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
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
                <div class="pt-2 pb-4">
                    <DateInputComponent
                        v-model="editEvents.date"
                        :label="editEvents.date === null ? $t('No postponement to date') : '' "
                        id="eventTitle"
                    />
                </div>
                <div class="w-full flex justify-center">
                    <FormButton :text="$t('Save')"  @click="saveMultiDuplicate"/>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
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
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {router, useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";

export default {
    name: 'MultiDuplicateModal',
    mixins: [Permissions],
    components: {
        ModalHeader,
        DateInputComponent,
        BaseModal,
        FormButton,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
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
                {id: 1, value: this.$t('Hour(s)')},
                {id: 2, value: this.$t('Day(s)')},
                {id: 3, value: this.$t('Week(s)')},
                {id: 4, value: this.$t('Month(s)')},
                {id: 5, value: this.$t('Year(s)')},
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
            selectedTimeType: {id: 1, value: this.$t('Hour(s)')},
            selectedRoom: null,
        }
    },
    props: ['checkedEvents', 'rooms'],
    emits: ['closed'],
    methods: {
        closeModal(bool, desiredRoomIds = null, desiredDays = null) {
            this.$emit('closed', bool, desiredRoomIds, desiredDays);
        },
        saveMultiDuplicate() {
            this.editEvents.type = this.selectedTimeType.id;
            this.editEvents.calculationType = this.selectedCalculationType.id;
            this.editEvents.newRoomId = this.selectedRoom?.id ?? null;

            router.patch(route('multi-duplicate.save'), {
                events: this.editEvents.events,
                newRoomId: this.editEvents.newRoomId,
                calculationType: this.editEvents.calculationType,
                value: this.editEvents.value,
                type: this.editEvents.type,
                date: this.editEvents.date
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal()
                }
            })

        }
    },
}
</script>
