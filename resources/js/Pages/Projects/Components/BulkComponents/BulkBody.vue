<template>
    <div :class="!isInModal ? 'max-w-7xl my-10' : ''">

        <div class="flex items-center justify-end" v-if="!isInModal">
            <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(1)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        Nach Räumen sortieren
                        <IconCheck class="w-5 h-5" v-if="currentSort === 1" />
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(2)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        Nach Terminart sortieren
                        <IconCheck class="w-5 h-5" v-if="currentSort === 2" />
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(3)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        Nach Tag sortieren
                        <IconCheck class="w-5 h-5" v-if="currentSort === 3" />
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(0)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        Sortierung zurücksetzen
                    </div>
                </MenuItem>
            </BaseMenu>
        </div>

        <BulkHeader v-model="timeArray" :is-in-modal="isInModal"/>
        <div class="min-h-96 max-h-96 overflow-y-scroll">
            <transition
                enter-active-class="duration-300 ease-out"
                enter-from-class="transform opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="transform opacity-0">
            <div>
                <div v-for="(event, index) in events" class="mb-4">
                    <BulkSingleEvent
                        :rooms="rooms"
                        :event_types="eventTypes"
                        :time-array="timeArray"
                        :event="event"
                        :copy-types="copyTypes"
                        :index="index"
                        @delete-current-event="deleteCurrentEvent"
                        @create-copy-by-event-with-data="createCopyByEventWithData"
                    />
                </div>
            </div>
            </transition>
        </div>
        <div class="flex items-center justify-between pointer-events-none">
            <IconCirclePlus @click="addEmptyEvent" class="w-8 h-8 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out pointer-events-auto" stroke-width="2"/>

            <div class="flex items-center gap-x-4">
                <div v-if="invalidEvents.length > 0" class="text-artwork-messages-error text-xs">
                    {{ $t('The name is not given for {0} event(s)', [invalidEvents.length]) }}
                </div>
                <BaseButton
                    @click="submit"
                    class="bg-artwork-buttons-create text-white h-12 pointer-events-auto"
                    :text="$t('Create')">
                    <IconCirclePlus class="w-5 h-5 text-white mr-2"/>
                </BaseButton>
            </div>
        </div>
    </div>
</template>

<script setup>

import BulkSingleEvent from "@/Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {IconCheck, IconCirclePlus} from "@tabler/icons-vue";
import BulkHeader from "@/Pages/Projects/Components/BulkComponents/BulkHeader.vue";
import {reactive, ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    eventTypes: {
        type: Array,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    isInModal: {
        type: Boolean,
        required: false,
        default: false
    }
})

const timeArray = ref(!props.isInModal);
const invalidEvents = ref([]);
const emits = defineEmits(['closed']);
const currentSort = ref(0);

const copyTypes = ref([
    {
        id: 1,
        name: 'Täglich',
        type: 'daily'
    },
    {
        id: 2,
        name: 'Wöchentlich',
        type: 'weekly'
    },
    {
        id: 3,
        name: 'Monatlich',
        type: 'monthly',
    }
])

const events = reactive([
    {
        index: 0,
        type: props.eventTypes ? props.eventTypes[0] : null,
        name: '',
        room: props.rooms ? props.rooms[0] : null,
        day: new Date().toISOString().split('T')[0],
        start_time: '',
        end_time: '',
        copy: false,
        copyCount: 1,
        // default copy type is daily
        copyType: copyTypes.value[0]
    }
])

const addEmptyEvent = () => {
    // create empty event but with +1 day of the last event
    let newDate = new Date();
    if (events.length > 0){
        let lastEvent = events[events.length - 1];
        newDate = new Date(lastEvent.day);
        newDate.setDate(newDate.getDate() + 1);
    }

    events.push({
        index: events.length + 1,
        type: props.eventTypes ? props.eventTypes[0] : null,
        name: '',
        room: props.rooms ? props.rooms[0] : null,
        day: newDate.toISOString().split('T')[0],
        start_time: '',
        end_time: '',
        copy: false,
        copyCount: 1,
        copyType: copyTypes.value[0]
    })
}

const deleteCurrentEvent = (event) => {
    events.splice(events.indexOf(event), 1)
}

const updateTimeArray = (value) => {
    timeArray.value = value;
}

const createCopyByEventWithData = (event) => {
    let newDate = new Date(event.day);

    for (let i = 0; i < event.copyCount; i++) {
        // Je nach copyType den Tag anpassen
        if (event.copyType.type === 'daily') {
            newDate.setDate(newDate.getDate() + 1);
        } else if (event.copyType.type === 'weekly') {
            newDate.setDate(newDate.getDate() + 7);
        } else if (event.copyType.type === 'monthly') {
            newDate.setMonth(newDate.getMonth() + 1);
        }

        // Kopie des Events erstellen
        events.push({
            index: events.length + 1,
            type: event.type,
            name: event.name,
            room: event.room,
            day: newDate.toISOString().split('T')[0],
            start_time: event.start_time,
            end_time: event.end_time,
            copy: false,
            copyCount: 1,
            copyType: copyTypes.value[0]
        });
    }

    // copy flag zurücksetzen
    event.copy = false;
    event.copyCount = 1;
    event.copyType = copyTypes.value[0];
}

const submit = () => {

    // remove error flag from all events
    events.forEach(event => {
        event.nameError = false;
    });

    // check in every event if the type has a individual name flag if yes check if the name it not empty
    invalidEvents.value = events.filter(event => event.type.individual_name && !event.name);

    // if there are invalid events add a border to the input field, add error text and return
    if (invalidEvents.value.length > 0) {
        invalidEvents.value.forEach(event => {
            event.nameError = true;
        });
        return;
    }

    router.post(route('events.bulk.store', {project: props.project}), {
        events: events,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emits('closed');
            // clear events
            events.splice(0, events.length);
            // add empty event
            addEmptyEvent();
        }
    });

}

// add watch to event to check if the name was changed and remove the error flag and remove the event from the invalidEvents array
watch(events, (newEvents) => {
    newEvents.forEach(event => {
        if (event.name) {
            invalidEvents.value = invalidEvents.value.filter(invalidEvent => invalidEvent !== event);
        }
    });
}, {deep: true});

const updateSort = (type) => {
    currentSort.value = type;
    if (currentSort.value === 1) {
        events.sort((a, b) => {
            return a.room.name.localeCompare(b.room.name);
        });
    } else if (currentSort.value === 2) {
        events.sort((a, b) => {
            return a.type.name.localeCompare(b.type.name);
        });
    } else if (currentSort.value === 3) {
        events.sort((a, b) => {
            return a.day.localeCompare(b.day);
        });
    } else {
        events.sort((a, b) => {
            return a.index - b.index;
        });
    }
}
</script>

<style scoped>

</style>