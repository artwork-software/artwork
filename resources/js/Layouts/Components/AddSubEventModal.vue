<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img alt="Neue Spalte" src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Neuer Untertermin
                </div>
                <p class="mb-3 text-gray-400 text-sm">
                    Gehört zu Blocker in {{ event.roomName }}
                </p>
                <p>
                    Bitte beachte, dass der Termin innerhalb des Termingruppenzeitraums stattfinden muss.
                </p>

                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>

                <div class="mt-6">
                    <div class="flex w-full py-2 gap-1">
                        <div class="w-1/2">
                            <Listbox as="div" class="flex h-12" v-model="subEvent.selectedEventType"
                                     id="eventType">
                                <ListboxButton
                                    class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <EventTypeIconCollection :height="20" :width="20" :iconName="subEvent.selectedEventType?.svg_name"/>
                                        <span class="block truncate items-center ml-3 flex">
                                            <span>{{ subEvent.selectedEventType?.name }}</span>
                                        </span>
                                        <span class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-72 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in filteredEventTypes"
                                                       :key="eventType.name"
                                                       :value="eventType"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <EventTypeIconCollection :height="12" :width="12" :iconName="eventType?.svg_name"/>
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">{{ eventType.name }}
                                                    </span>
                                                </div>
                                                <span
                                                    :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>

                        <div class="w-1/2">
                            <input type="text"
                                   v-model="subEvent.eventName"
                                   @keyup="subEvent.eventName.length > 0 ? submit = true : submit = false"
                                   id="eventTitle"
                                   :placeholder="subEvent.selectedEventType?.individual_name ? 'Terminname*' : 'Terminname'"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                        </div>
                    </div>
                    <!-- Attribute Menu -->
                    <Menu as="div" class="inline-block text-left w-full">
                        <div>
                            <MenuButton
                                class="h-12 inputMain w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
                            >

                            <span class="float-left flex xsLight subpixel-antialiased"><img src="/Svgs/IconSvgs/icon_adjustments.svg"
                                                                                            class="mr-2"
                                                                                            alt="attributeIcon"/>Termineigenschaften wählen</span>
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
                                class="absolute overflow-y-auto h-24 mt-2 w-[88%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                    <div class="flex w-full mb-4">
                                        <input v-model="subEvent.audience"
                                               type="checkbox"
                                               class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>

                                        <div :class="[subEvent.audience ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                            Mit Publikum
                                        </div>
                                    </div>
                                    <div class="flex w-full mb-2">
                                        <input v-model="subEvent.is_loud"
                                               type="checkbox"
                                               class="cursor-pointer h-6 w-6 text-success border-2 border-gray-300 focus:ring-0"/>
                                        <div :class="[subEvent.is_loud ? 'xsWhiteBold' : 'xsLight', 'my-auto mx-2']">Es
                                            wird laut
                                        </div>
                                    </div>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>

                    <!--    Properties    -->
                    <div class="flex py-2">
                        <div v-if="subEvent.audience">
                            <TagComponent icon="audience" displayed-text="Mit Publikum" hideX="true" />
                        </div>
                        <div v-if="subEvent.is_loud">
                            <TagComponent displayed-text="es wird laut" hideX="true" />
                        </div>
                    </div>
                    <div class="flex pb-1 flex-col sm:flex-row align-baseline gap-1">
                        <div class="sm:w-1/2">
                            <label for="startDate" class="xxsLight">Start</label>
                            <div class="w-full flex">
                                <input v-model="subEvent.start_time"
                                       id="startDate"
                                       @change="checkTimes()"
                                       type="datetime-local"
                                       required
                                       class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                            </div>
                        </div>
                        <div class="sm:w-1/2">
                            <label for="endDate" class="xxsLight">Ende</label>
                            <div class="w-full flex">
                                <input v-model="subEvent.end_time"
                                       id="endDate"
                                       @change="checkTimes()"
                                       type="datetime-local"
                                       required
                                       class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                            </div>
                        </div>
                    </div>
                   <div>
                       <div class="text-red-500 text-xs" v-show="helpText.length > 0">{{ helpText }}</div>
                       <div class="text-red-500 text-xs" v-show="helpTextStart.length > 0">{{ helpTextStart }}</div>
                       <div class="text-red-500 text-xs" v-show="helpTextEnd.length > 0">{{ helpTextEnd }}</div>
                       <div class="text-red-500 text-xs" v-show="helpTextLength.length > 0">{{ helpTextLength }}</div>
                   </div>

                    <div class="py-2">
                    <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                              id="description"
                              v-model="subEvent.description"
                              rows="4"
                              class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    </div>
                </div>
            </div>
            <div class="flex justify-center w-full py-4">
                <button :disabled="!submit" :class="!submit ? 'bg-secondary hover:bg-secondary' : ''" class="bg-buttonBlue hover:bg-indigo-600 py-2 px-8 rounded-full text-white"
                        @click="updateOrCreateEvent()">
                    Belegen
                </button>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection.vue";
import {CheckIcon, ChevronDownIcon} from "@heroicons/vue/solid";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems
} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import dayjs from "dayjs";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "AddSubEventModal",
    mixins: [Permissions],
    computed: {
        dayjs() {
            var utc = require('dayjs/plugin/utc')
            dayjs.extend(utc)
            return dayjs
        },
        filteredEventTypes() {
            return this.eventTypes.filter(eventType => eventType.id !== 1)
        },
    },
    components: {
        TagComponent,
        JetDialogModal,
        XIcon,
        EventTypeIconCollection,
        ChevronDownIcon,
        Listbox, ListboxLabel, ListboxButton, ListboxOption, ListboxOptions,
        Menu, MenuItem, MenuItems, MenuButton, CheckIcon
    },
    data(){
        return {
            subEvent: useForm({
                event_id: this.event.id,
                eventName: this.subEventToEdit?.title ? this.subEventToEdit?.title : '',
                selectedEventType: this.subEventToEdit?.eventType ? this.subEventToEdit?.eventType : this.eventTypes[1],
                start_time: this.subEventToEdit?.start ? this.subEventToEdit?.start : dayjs(this.event.start).format('YYYY-MM-DD HH:mm'),
                end_time: this.subEventToEdit?.end ? this.subEventToEdit?.end : dayjs(this.event.end).format('YYYY-MM-DD HH:mm'),
                is_loud: this.subEventToEdit?.is_loud ? this.subEventToEdit?.is_loud : false,
                audience: this.subEventToEdit?.audience ? this.subEventToEdit?.audience : false,
                description: this.subEventToEdit?.description ? this.subEventToEdit?.description : '',
                user_id: this.$page.props.user.id,
                event_type_id: this.subEventToEdit?.eventTypeId ? this.subEventToEdit?.eventTypeId : ''
            }),
            edit: !!this.subEventToEdit?.id,
            helpText: '',
            helpTextStart: '',
            helpTextEnd: '',
            helpTextLength: '',
            show: true,
            submit: false
        }
    },
    props: ['event', 'eventTypes', 'subEventToEdit'],
    emits: ['close'],
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkTimes(){
            if(this.subEvent.start_time > this.subEvent.end_time && this.subEvent.end_time && this.subEvent.start_time){
                this.helpText = 'Endzeit kann nicht vor der Startzeit liegen!';
                this.submit = false;
            } else {
                this.helpText = '';
                this.submit = true;
            }

            const timezoneOffset = new Date(this.event.start).getTimezoneOffset()* 60000
            const start = dayjs(this.event.start);
            const end = dayjs(this.event.end);

            if(this.subEvent.start_time){
                const subEventStart = dayjs(this.subEvent.start_time);
                if(start > subEventStart || end < subEventStart){
                    this.helpTextStart = 'Startzeit muss innerhalb Termingruppenzeitraum liegen';
                    this.submit = false;
                } else {
                    this.helpTextStart = '';
                    this.submit = true;
                }
            }
            if(this.subEvent.end_time){
                const subEventEnd = Date.parse(this.subEvent.end_time);
                if(end < subEventEnd || start > subEventEnd){
                    this.helpTextEnd = 'Endzeit muss innerhalb Termingruppenzeitraum liegen';
                    this.submit = false;
                } else {
                    this.helpTextEnd = '';
                    this.submit = true;
                }
            }

            // check if event min 30min
            if(this.subEvent.start_time && this.subEvent.end_time){
                const date = new Date(this.subEvent.start_time);
                const minimumEnd = this.addMinutes(date, 30);
                if(minimumEnd <= new Date(this.subEvent.end_time)){
                    this.helpTextLength = '';
                    this.submit = true;
                } else {
                    this.helpTextLength = 'Der Termin darf nicht kürzer als 30 Minuten sein';
                    this.submit = false;
                }
            }

        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        updateOrCreateEvent(){
            this.subEvent.event_type_id = this.subEvent?.selectedEventType?.id;
            if(this.edit){
                this.subEvent.patch(route('subEvent.update', this.subEventToEdit.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(true);
                    }
                })
            } else {
                this.subEvent.post(route('subEvent.add'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(true);
                    }
                })
            }

        }
    },
    watch: {

    }
}
</script>

<style scoped>

</style>
