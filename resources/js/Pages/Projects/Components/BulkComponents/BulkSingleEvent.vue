<template>
   <div class="print:w-full">
       <div class="grid gird-cols-1 md:grid-cols-8 gap-4">
           <div class="" v-if="usePage().props.event_status_module">
               <Listbox v-model="event.status"
                        @update:model-value="updateEventInDatabase"
                        id="type"
                        as="div"
                        class="relative"
                        :disabled="canEditComponent === false">
                   <ListboxButton :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']" class="print:border-0">
                       <div class="flex items-center gap-x-2">
                           <div>
                               <div class="block w-5 h-5 rounded-full"
                                    :style="{'backgroundColor' : event.status?.color }"/>
                           </div>
                           <div class="truncate w-16 print:w-full">
                               {{ event.status?.name }}
                           </div>
                       </div>
                       <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden" aria-hidden="true"/>
                   </ListboxButton>
                   <ListboxOptions class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                       <ListboxOption class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                      v-for="status in eventStatuses"
                                      :key="status.name"
                                      :value="status"
                                      v-slot="{ active, selected }">
                           <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']" class="flex items-center gap-x-2">
                               <div>
                                   <div class="block w-3 h-3 rounded-full"
                                        :style="{'backgroundColor' : status?.color }"/>
                               </div>
                               {{ status.name }}
                           </div>
                           <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                      aria-hidden="true"/>
                       </ListboxOption>
                   </ListboxOptions>
               </Listbox>
           </div>
           <div class="">
               <Listbox v-model="event.type"
                        @update:model-value="updateEventInDatabase"
                        id="type"
                        as="div"
                        class="relative"
                        :disabled="canEditComponent === false">
                   <ListboxButton :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']" class="print:border-0 ">
                       <div class="flex items-center gap-x-2">
                           <div class="">
                               <div class="block w-5 h-5 rounded-full"
                                    :style="{'backgroundColor' : event.type?.hex_code }"/>
                           </div>
                           <div class="truncate w-16 print:w-full">
                               {{ event.type?.name }}
                           </div>
                       </div>
                       <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden" aria-hidden="true"/>
                   </ListboxButton>
                   <ListboxOptions class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                       <ListboxOption class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                      v-for="eventType in sortedEventTypes"
                                      :key="eventType.name"
                                      :value="eventType"
                                      v-slot="{ active, selected }">
                           <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']" class="flex items-center gap-x-2">
                               <div>
                                   <div class="block w-3 h-3 rounded-full"
                                        :style="{'backgroundColor' : eventType?.hex_code }"/>
                               </div>
                               {{ eventType.name }}
                           </div>
                           <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                      aria-hidden="true"/>
                       </ListboxOption>
                   </ListboxOptions>
               </Listbox>
           </div>
           <div>
               <input v-model="event.name"
                   type="text"
                   :id="'name-' + index"
                   class="input h-12 print:border-0 print:bg-white"
                   :class="event.type?.individual_name && !event.name ? 'border-red-500' : ''"
                   placeholder="Name"
                   @focusout="updateEventInDatabase"
                   :disabled="canEditComponent === false"
               />
           </div>
           <div>
               <Listbox id="room"
                        as="div"
                        class="relative"
                        v-model="event.room"
                        @update:model-value="updateEventInDatabase"
                        :disabled="canEditComponent === false">
                   <ListboxButton :class="[canEditComponent ? '' : 'bg-gray-100', 'menu-button']" class=" print:border-0">
                       <div class="flex-grow flex text-left xsDark">
                           {{ event.room?.name }}
                       </div>
                       <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary print:hidden" aria-hidden="true"/>
                   </ListboxButton>
                   <ListboxOptions class="w-full rounded-lg bg-primary max-h-56 overflow-y-auto text-sm absolute z-30">
                       <ListboxOption v-for="room in sortedRooms"
                                      class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                      :key="room.name"
                                      :value="room"
                                      v-slot="{ active, selected }">
                           <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                               {{ room.name }}
                           </div>
                           <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                      aria-hidden="true"/>
                       </ListboxOption>
                   </ListboxOptions>
               </Listbox>
           </div>
           <div class="print:col-span-2">
               <div class="relative">
                   <div class="absolute inset-y-0 left-1 text-xs pointer-events-none text-primary flex items-center pl-3 z-40 h-12">
                       {{ dayString }},
                   </div>
                   <input
                       v-model="event.day"
                       type="date"
                       :id="'day-' + index"
                       placeholder="Tag"
                       class="input h-12 pl-9 text-xs print:border-0 print:pr-5"
                       :disabled="canEditComponent === false"
                       @focusout="updateEventInDatabase"
                       @change="dayString = getDayOfWeek(new Date(event.day)).replace('.', '')"
                   />
               </div>
           </div>
           <div class="col-span-2">
               <div class="flex items-center" v-if="timeArray">
                   <input
                       v-model="event.start_time"
                       type="time"
                       :id="'start-time-' + index"
                       placeholder="Tag"
                       class="input h-12 !rounded-r-none print:border-0"
                       :disabled="canEditComponent === false"
                       @focusout="updateEventInDatabase"
                   />
                   <input
                       v-model="event.end_time"
                       type="time"
                       :id="'end_time-' + index"
                       placeholder="Tag"
                       class="input h-12 !rounded-l-none border-l-0 print:border-0"
                       :disabled="canEditComponent === false"
                       @focusout="updateEventInDatabase"
                   />
               </div>
           </div>
           <div v-if="canEditComponent" class="flex items-center col-span-1 print:hidden">
               <div class="flex items-center gap-x-3">
                   <ToolTipComponent icon="IconNote" v-if="!isInModal" :tooltip-text="$t('Edit the description')" stroke="1.5" @click="openNoteModal = true" />
                   <ToolTipDefault :tooltip-text="$t('Set the event to all-day')" left show24-h-icon icon-classes="w-6 h-6" v-if="event.start_time && event.end_time && !event.copy && !isInModal" @click="removeTime"/>
                   <IconCopy @click="event.copy = true" v-if="!event.copy"
                             class="w-6 h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                             stroke-width="2"/>
                   <Menu v-if="!isInModal"
                         as="div"
                         class="text-sm cursor-pointer flex flex-row items-center bg-transparent">
                       <MenuButton as="div" class="bg-transparent">
                           <IconDotsVertical class="w-5 h-5 flex-shrink-0 z-50"
                                             stroke-width="1.5"
                                             aria-hidden="true"/>
                       </MenuButton>
                       <div class="w-full h-full relative">
                           <transition enter-active-class="transition-enter-active"
                                       enter-from-class="transition-enter-from"
                                       enter-to-class="transition-enter-to"
                                       leave-active-class="transition-leave-active"
                                       leave-from-class="transition-leave-from"
                                       leave-to-class="transition-leave-to">
                               <MenuItems class="w-56 absolute top-2 shadow-lg rounded-xl bg-artwork-navigation-background focus:outline-none">
                                   <MenuItem v-slot="{ active }"
                                             as="div">
                                       <a @click="openEventComponent(event.id)"
                                          :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer flex items-center px-4 py-2 subpixel-antialiased group']">
                                           <IconEdit class="mr-3 h-5 w-5 group-hover:text-white"/>
                                           {{ $t('Edit') }}
                                       </a>
                                   </MenuItem>
                                   <MenuItem v-if="index > 0 && !event.copy || !isInModal"
                                             v-slot="{ active }"
                                             as="div"
                                             @click="openDeleteEventConfirmModal()">
                                       <a :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer flex items-center px-4 py-2 subpixel-antialiased group']">
                                           <IconTrash class="mr-3 h-5 w-5 group-hover:text-white"/>
                                           {{ $t('Put in the trash') }}
                                       </a>
                                   </MenuItem>
                               </MenuItems>
                           </transition>
                       </div>
                   </Menu>
                   <div v-if="event.copy" class="flex items-center gap-x-2">
                       <IconPlus class="w-6 h-6 text-artwork-buttons-context" stroke-width="2"/>
                       <input
                           type="number"
                           class="input h-12 w-14"
                           placeholder="Anzahl"
                           v-model="event.copyCount"
                           min="1"
                           minlength="1"
                           max="1000"
                       />
                       <Listbox as="div" class="relative" v-model="event.copyType" id="room">
                           <ListboxButton class="menu-button">
                               <div class="flex-grow flex text-left xsDark !w-12 truncate">
                                   {{ event.copyType?.name }}
                               </div>
                               <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                           </ListboxButton>
                           <ListboxOptions
                               class="w-full rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                               <ListboxOption v-for="copyType in copyTypes"
                                              class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                              :key="copyType.name"
                                              :value="copyType"
                                              v-slot="{ active, selected }">
                                   <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                       {{ copyType.name }}
                                   </div>
                                   <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                              aria-hidden="true"/>
                               </ListboxOption>
                           </ListboxOptions>
                       </Listbox>
                       <IconCircleCheckFilled @click="createCopyByEventWithData(event)"
                                              class="w-8 h-8 text-artwork-buttons-create cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                                              stroke-width="2"/>
                       <IconX @click="event.copy = false"
                              class="w-6 h-6 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                              stroke-width="2"/>
                   </div>
               </div>
           </div>
       </div>
       <div v-if="event.nameError && !event.name" class="text-xs mt-1 text-artwork-messages-error">
           {{ $t('Event name is mandatory') }}
       </div>
       <confirmation-component
           v-if="showDeleteEventConfirmModal"
           :confirm="$t('Delete')"
           :titel="$t('Delete event')"
           :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
           @closed="onCloseDeleteEventConfirmModal"/>

       <AddEditEventNoteModal :event="event" v-if="openNoteModal" @close="openNoteModal = false"/>
   </div>
</template>

<script setup>
import {
    IconCheck,
    IconChevronDown,
    IconCircleCheckFilled,
    IconCopy,
    IconDotsVertical,
    IconEdit,
    IconPlus,
    IconTrash,
    IconX
} from "@tabler/icons-vue";
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
import Input from "@/Layouts/Components/InputComponent.vue";
import {router, usePage} from "@inertiajs/vue3";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {computed, onMounted, ref} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import AddEditEventNoteModal from "@/Pages/Projects/Components/BulkComponents/AddEditEventNoteModal.vue";
import {inject} from "vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    timeArray: {
        type: Boolean,
        required: true
    },
    event_types: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    copyTypes: {
        type: Array,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    isInModal: {
        type: Boolean,
        required: false,
        default: false
    },
    canEditComponent: {
        type: Boolean,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    }
});

const showMenu = ref(false);
const dayString = ref(null);
const openNoteModal = ref(false);
const event_properties = inject('event_properties');

const emit = defineEmits(['deleteCurrentEvent', 'createCopyByEventWithData', 'openEventComponent']);
const openEventComponent = (eventId) => {
    emit.call(this, 'openEventComponent', eventId)
};

const createCopyByEventWithData = (event) => {
    emit('createCopyByEventWithData', event);
}

const showDeleteEventConfirmModal = ref(false);

const openDeleteEventConfirmModal = () => {
    showDeleteEventConfirmModal.value = true;
};

const onCloseDeleteEventConfirmModal = (closedOnPurpose) => {
    if (closedOnPurpose) {
        emit('deleteCurrentEvent', props.event);
    }

    showDeleteEventConfirmModal.value = false;
};

const updateEventInDatabase = () => {
    if (props.event.id) {
        if (props.event.start_time && !props.event.end_time) {
            const startTime = new Date(`01/01/2000 ${props.event.start_time}`);
            startTime.setMinutes(startTime.getMinutes() + 30);
            props.event.end_time = startTime.toTimeString().slice(0, 5);
        }

        if (!props.event.start_time && props.event.end_time) {
            const endTime = new Date(`01/01/2000 ${props.event.end_time}`);
            endTime.setMinutes(endTime.getMinutes() - 30);
            props.event.start_time = endTime.toTimeString().slice(0, 5);
        }

        if (props.event.type?.individual_name && !props.event.name) {
            props.event.nameError = true;
            return;
        }

        router.patch(route('event.update.single.bulk', { event: props.event.id }), {
            data: props.event
        }, {
            preserveState: false,
            preserveScroll: true
        })
    }
}

const sortedRooms = computed(() => {
    return props.rooms.sort((a, b) => a.name.localeCompare(b.name));
})

const sortedEventTypes = computed(() => {
    return props.event_types.sort((a, b) => a.name.localeCompare(b.name));
})

const removeTime = () => {
    props.event.start_time = null;
    props.event.end_time = null;
    updateEventInDatabase();
}

const getDayOfWeek = (date) => {
    const days = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
    return days[date.getDay()];
}

onMounted(() => { dayString.value = getDayOfWeek(new Date(props.event.day)).replace('.', '') });
</script>

