<template>
    <div :class="!isInModal ? 'my-10' : ''" class="relative">
        <div class="absolute w-full h-full bg-artwork-buttons-context/50 top-0 z-50" v-if="isLoading">
            <div class="h-full flex items-center justify-center text-white">
                {{ $t('Data is currently loaded. Please wait') }}
            </div>
        </div>
        <div class="flex items-center justify-end gap-x-2" v-if="!isInModal">
            <IconCalendarMonth class="w-6 h-6 cursor-pointer"
                               @click="useProjectTimePeriodAndRedirect()"/>
            <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(1)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by room') }}
                        <IconCheck class="w-5 h-5" v-if="currentSort === 1"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(2)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by appointment type') }}
                        <IconCheck class="w-5 h-5" v-if="currentSort === 2"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(3)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by day') }}
                        <IconCheck class="w-5 h-5" v-if="currentSort === 3"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateSort(0)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Reset sorting') }}
                    </div>
                </MenuItem>
            </BaseMenu>
        </div>

        <BulkHeader v-model="timeArray" :is-in-modal="isInModal"/>
        <div :class="isInModal ? 'min-h-96 max-h-96 overflow-y-scroll' : ''">
            <div v-if="events.length > 0"
                 v-for="(event, index) in events"
                 class="mb-4">
                <BulkSingleEvent
                    :can-edit-component="canEditComponent"
                    :rooms="rooms"
                    :event_types="eventTypes"
                    :time-array="timeArray"
                    :event="event"
                    :copy-types="copyTypes"
                    :index="index"
                    :is-in-modal="isInModal"
                    @open-event-component="onOpenEventComponent"
                    @delete-current-event="deleteCurrentEvent"
                    @create-copy-by-event-with-data="createCopyByEventWithData"
                />
            </div>
            <div v-else class="flex items-center h-24">
                <AlertComponent :text="$t('No events found. Click on the plus (+) icon to create an event')" type="info"
                                show-icon icon-size="h-5 w-5"
                                classes="!items-center"/>
            </div>
        </div>
        <div class="flex items-center justify-between pointer-events-none">
            <IconCirclePlus v-if="canEditComponent"
                            @click="addEmptyEvent"
                            class="w-8 h-8 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out pointer-events-auto"
                            stroke-width="2"/>

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
    <event-component v-if="eventComponentIsVisible"
                     :showHints="$page.props?.can?.show_hints"
                     :eventTypes="eventTypes"
                     :rooms="rooms"
                     :project="project"
                     :event="eventToEdit"
                     :wantedRoomId="null"
                     :isAdmin="hasAdminRole()"
                     :roomCollisions="roomCollisions"
                     :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                     :used-in-bulk-component="true"
                     @closed="onEventComponentClosed"/>
</template>

<script setup>
import BulkSingleEvent from "@/Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {IconCalendarMonth, IconCheck, IconCirclePlus} from "@tabler/icons-vue";
import BulkHeader from "@/Pages/Projects/Components/BulkComponents/BulkHeader.vue";
import {onMounted, reactive, ref, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {usePermission} from "@/Composeables/Permission.js";

const {hasAdminRole} = usePermission(usePage().props),
    $t = useTranslation(),
    props = defineProps({
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
        },
        canEditComponent: {
            type: Boolean,
            required: true
        },
        first_project_calendar_tab_id: {
            type: Number,
            required: true
        }
    }),
    roomCollisions = ref([]),
    timeArray = ref(!props.isInModal),
    invalidEvents = ref([]),
    emits = defineEmits(['closed']),
    currentSort = ref(0),
    copyTypes = ref([
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
    ]),
    events = reactive([]),
    isLoading = ref(true),
    eventComponentIsVisible = ref(false),
    eventToEdit = ref(null),
    onOpenEventComponent = (eventId) => {
        eventComponentIsVisible.value = true;
        eventToEdit.value = props.eventsInProject?.find((event) => {
            return event.id === eventId
        });
    },
    onEventComponentClosed = () => {
        eventComponentIsVisible.value = false;
        eventToEdit.value = null;
    },
    addEmptyEvent = () => {
        isLoading.value = true;

        let newDate = new Date();
        if (events.length > 0) {
            let lastEvent = events[events.length - 1];
            newDate = new Date(lastEvent.day);
            newDate.setDate(newDate.getDate() + 1);
        }

        if (props.isInModal) {
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
            if (events.length > 0) {
                let lastEvent = events[events.length - 1];
                newDate = new Date(lastEvent.day);
                newDate.setDate(newDate.getDate() + 1);

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
    },
    deleteCurrentEvent = (event) => {
        isLoading.value = true;
        if (event.id) {
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

        events.splice(events.indexOf(event), 1);
    },
    createCopyByEventWithData = (event) => {
        isLoading.value = true;
        let newDate = new Date(event.day);
        let createdEvents = [];
        for (let i = 0; i < event.copyCount; i++) {
            if (event.copyType.type === 'daily') {
                newDate.setDate(newDate.getDate() + 1);
            } else if (event.copyType.type === 'weekly') {
                newDate.setDate(newDate.getDate() + 7);
            } else if (event.copyType.type === 'monthly') {
                newDate.setMonth(newDate.getMonth() + 1);
            }

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

            createdEvents.push({
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

        event.copy = false;
        event.copyCount = 1;
        event.copyType = copyTypes.value[0];

        if (!props.isInModal) {
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
    },
    submit = () => {
        events.forEach(event => {
            event.nameError = false;
        });

        invalidEvents.value = events.filter(event => event.type.individual_name && !event.name);

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
    },
    updateSort = (type) => {
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
    };

const useProjectTimePeriodAndRedirect = () => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
        {
            use_project_time_period: true,
            project_id: props.project.id
        }
    );
};

onMounted(() => {
    if (props.eventsInProject.length > 0) {
        events.splice(0, 1);
        props.eventsInProject.forEach(event => {
            events.push({
                id: event.id,
                project_id: event.projectId,
                type: props.eventTypes.find(type => type.id === event.event_type_id),
                name: event.eventName,
                room: props.rooms.find(room => room.id === event.room_id),
                day: event.event_date_without_time.start_clear,
                start_time: !event.allDay ? event.start_time_without_day : '',
                end_time: !event.allDay ? event.end_time_without_day : '',
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                index: events.length + 1
            });
        });
        isLoading.value = false;
    } else {
        isLoading.value = false;
    }

    if (props.isInModal) {
        addEmptyEvent();
    }
});

watch(events, (newEvents) => {
    newEvents.forEach(event => {
        if (event.name) {
            invalidEvents.value = invalidEvents.value.filter(invalidEvent => invalidEvent !== event);
        }
    });
}, {deep: true});
</script>
