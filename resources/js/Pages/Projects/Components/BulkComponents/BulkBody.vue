<template>
    <div :class="!isInModal ? 'my-10' : ''" class="relative">
        <div class="absolute w-full h-full bg-artwork-buttons-context/50 top-0 z-50" v-if="isLoading">
            <div class="h-full flex items-center justify-center text-white">
                {{ $t('Data is currently loaded. Please wait') }}
            </div>
        </div>
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
        <div :class="isInModal ? 'min-h-96 max-h-96 overflow-y-scroll' : ''">
            <div  v-if="events.length > 0" v-for="(event, index) in events" class="mb-4">
                <BulkSingleEvent
                    :rooms="rooms"
                    :event_types="eventTypes"
                    :time-array="timeArray"
                    :event="event"
                    :copy-types="copyTypes"
                    :index="index"
                    @delete-current-event="deleteCurrentEvent"
                    @create-copy-by-event-with-data="createCopyByEventWithData"
                    :is-in-modal="isInModal"
                />
            </div>
            <div v-else class="flex items-center h-24">
                <AlertComponent :text="$t('No events found. Click on the plus (+) icon to create an event')" type="info" show-icon icon-size="h-5 w-5" classes="!items-center" />
            </div>
        </div>
        <div class="flex items-center justify-between pointer-events-none">
            <IconCirclePlus @click="addEmptyEvent" class="w-8 h-8 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out pointer-events-auto" stroke-width="2"/>

            <div class="flex items-center gap-x-4">
                <div v-if="invalidEvents.length > 0" class="text-artwork-messages-error text-xs">
                    {{ $t('The name is not given for {0} event(s)', [invalidEvents.length]) }}
                </div>
                <BaseButton
                    v-if="isInModal"
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
import {onMounted, reactive, ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";

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
    },
    eventsInProject: {
        type: Object,
        required: false,
        default: () => []
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

const events = reactive([])
const isLoading = ref(true);
// if props.eventsInProject is not empty add the events to the events array
onMounted(() => {
    if (props.eventsInProject.length > 0) {
        events.splice(0, 1);
        props.eventsInProject.forEach(event => {
            events.push({
                id: event.id,
                type: props.eventTypes.find(type => type.id === event.event_type_id),
                name: event.name ?? event.eventName,
                room: props.rooms.find(room => room.id === event.room_id),
                day: event.event_date_without_time.start_clear,
                start_time: !event.allDay ? event.start_time_without_day : '',
                end_time: !event.allDay ? event.end_time_without_day : '',
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                index: events.length + 1
            })
        });
        isLoading.value = false;
    } else {
        isLoading.value = false;
    }

    if(props.isInModal) {
        addEmptyEvent();
    }
});


const addEmptyEvent = () => {
    isLoading.value = true;
    // create empty event but with +1 day of the last event
    let newDate = new Date();
    if (events.length > 0){
        let lastEvent = events[events.length - 1];
        newDate = new Date(lastEvent.day);
        newDate.setDate(newDate.getDate() + 1);
    }
    if (props.isInModal) {
        console.log('is in modal');
        events.push({
            index: events.length + 1,
            type: props.eventTypes ? props.eventTypes[0] : null,
            name: props.isInModal ? '' : 'Blocker',
            room: props.rooms ? props.rooms[0] : null,
            day: newDate.toISOString().split('T')[0],
            start_time: '',
            end_time: '',
            copy: false,
            copyCount: 1,
            copyType: copyTypes.value[0]
        });
        isLoading.value = false;
    } else {
        if (events.length > 0){
            // create and new event with the same data as the last event but day + 1
            let lastEvent = events[events.length - 1];
            newDate = new Date(lastEvent.day);
            newDate.setDate(newDate.getDate() + 1);

            // save it in database
            router.post(route('event.store.bulk.single', {project: props.project}), {
                event: {
                    type: lastEvent.type,
                    name: lastEvent.name,
                    room: lastEvent.room,
                    day: newDate.toISOString().split('T')[0],
                    start_time: lastEvent.start_time,
                    end_time: lastEvent.end_time,
                    copy: false,
                    copyCount: 1,
                    copyType: copyTypes.value[0]
                }
            }, {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    isLoading.value = false;
                }
            });

        } else {


            // save it in database
            router.post(route('event.store.bulk.single', {project: props.project}), {
                event: {
                    type: props.eventTypes ? props.eventTypes[0] : null,
                    name: props.isInModal ? '' : 'Blocker',
                    room: props.rooms ? props.rooms[0] : null,
                    day: newDate.toISOString().split('T')[0],
                    start_time: '',
                    end_time: '',
                    copy: false,
                    copyCount: 1,
                    copyType: copyTypes.value[0]
                }
            }, {
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    isLoading.value = false;
                }
            });
        }
    }
}

const deleteCurrentEvent = (event) => {
    isLoading.value = true;
    if (event.id){
        router.delete(route('event.bulk.delete', {event: event.id}), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isLoading.value = false;
            }
        });
    } else {
        isLoading.value = false;
    }

    events.splice(events.indexOf(event), 1)

}

const updateTimeArray = (value) => {
    timeArray.value = value;
}

const createCopyByEventWithData = (event) => {
    isLoading.value = true;
    let newDate = new Date(event.day);
    let createdEvents = [];
    for (let i = 0; i < event.copyCount; i++) {
        // Je nach copyType den Tag anpassen
        if (event.copyType.type === 'daily') {
            newDate.setDate(newDate.getDate() + 1);
        } else if (event.copyType.type === 'weekly') {
            newDate.setDate(newDate.getDate() + 7);
        } else if (event.copyType.type === 'monthly') {
            newDate.setMonth(newDate.getMonth() + 1);
        }

        let eventName = '';

        if (event.type.individual_name) {
            eventName = event.name;
        } else {
            eventName = '';
        }

        // Kopie des Events erstellen
        events.push({
            index: events.length + 1,
            type: event.type,
            name: eventName,
            room: event.room,
            day: newDate.toISOString().split('T')[0],
            start_time: event.start_time,
            end_time: event.end_time,
            copy: false,
            copyCount: 1,
            copyType: copyTypes.value[0]
        });

        // save this event in createdEvents
        createdEvents.push({
            type: event.type,
            name: eventName,
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

    // save the created events in database
    if (!props.isInModal){
        router.post(route('events.bulk.store', {project: props.project}), {
            events: createdEvents
        }, {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
                isLoading.value = false;
            }
        });
    } else {
        isLoading.value = false;
    }
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
    isLoading.value = true;
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

    isLoading.value = false;
}
</script>

<style scoped>

</style>