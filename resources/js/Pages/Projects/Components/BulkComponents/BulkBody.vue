<template>
    <div :class="!isInModal ? 'my-10' : ''" class="relative">
        <div class="absolute w-full h-full bg-artwork-buttons-context/50 top-0 z-50" v-if="isLoading">
            <div class="h-full flex items-center justify-center text-white">
                {{ $t('Data is currently loaded. Please wait') }}
            </div>
        </div>
        <div class="flex items-center justify-end gap-x-4 print:hidden w-68" v-if="!isInModal">
            <MultiEditSwitch :multi-edit="multiEdit"
                             :room-mode="false"
                             @update:multi-edit="UpdateMultiEditEmits"/>
            <div class="flex items-center gap-x-2">
                <PlanningSwitch :planning="isPlanningEvent"
                               @update:planning="isPlanningEvent = $event"/>
            </div>
            <ToolTipComponent
                icon="IconCircuitCapacitorPolarized"
                icon-size="h-7 w-7"
                :tooltip-text="$t('Customize column size')"
                direction="bottom"
                @click="showIndividualColumnSizeConfigModal = true"
            />
            <ToolTipComponent icon="IconFileExport"
                              icon-size="h-7 w-7"
                              :tooltip-text="$t('Export project list')"
                              direction="bottom"
                              @click="showExportModal = true"/>
            <ToolTipComponent icon="IconCalendarMonth"
                              icon-size="h-7 w-7"
                              :tooltip-text="$t('Show project period in calendar')"
                              direction="bottom"
                              @click="useProjectTimePeriodAndRedirect()"/>
            <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                <MenuItem v-slot="{ active }">
                    <div @click="updateUserSortId(1)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by room') }}
                        <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 1"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateUserSortId(2)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by appointment type') }}
                        <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 2"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateUserSortId(3)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Sort by day') }}
                        <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 3"/>
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div @click="updateUserSortId(0)"
                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                        {{ $t('Reset sorting') }}
                    </div>
                </MenuItem>
            </BaseMenu>
        </div>


        <div class="max-w-7xl overflow-x-scroll relative" >
            <BulkHeader v-model="timeArray" :is-in-modal="isInModal" :multi-edit="multiEdit"/>
            <div :class="isInModal ? 'min-h-96 max-h-96 overflow-y-scroll w-max' : ''">
                <div v-if="events.length > 0" v-for="(event, index) in events" class="mb-4">
                    <div :id="index" :class="(events[index]?.day !== events[index + 1]?.day) && usePage().props.auth.user.bulk_sort_id === 3 ? 'border-b-2 border-dashed pb-3' : ''">
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
                            :event-statuses="eventStatuses"
                            :multi-edit="multiEdit"
                        />
                    </div>
                </div>
                <div v-else class="flex items-center h-24 print:hidden">
                    <AlertComponent :text="$t('No events found. Click on the plus (+) icon to create an event')" type="info"
                                    show-icon icon-size="h-5 w-5"
                                    classes="!items-center"/>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between pointer-events-none print:hidden" v-if="!multiEdit">
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
        <div v-else class="fixed bottom-0 h-28 w-full bg-gray-900/10 -mx-5 print:hidden">
            <div class="flex items-center justify-center gap-x-4 w-full h-full">
                <div>
                    <FormButton
                        @click="showConfirmDeleteModal = true"
                        :disabled="getEventIdsWhereSelectedForMultiEdit().length === 0"
                        class="bg-red-500 hover:bg-red-600 text-white h-12"
                        :text="$t('Delete')" />
                </div>
                <div>
                    <FormButton
                        @click="openMultiEditModal"
                        :disabled="getEventIdsWhereSelectedForMultiEdit().length === 0"
                        class="bg-artwork-buttons-create text-white h-12"
                        :text="$t('Edit')" />
                </div>
            </div>
        </div>
    </div>
    <event-component
        v-if="eventComponentIsVisible"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="eventToEdit"
        :wantedRoomId="null"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :used-in-bulk-component="true"
        @closed="onEventComponentClosed"
        :event-statuses="eventStatuses"
    />
    <export-modal v-if="showExportModal"
                  @close="showExportModal = false"
                  :enums="[
                      exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
                      exportTabEnums.EXCEL_CALENDAR_EXPORT,
                      exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT
                  ]"
                  :configuration="getExportModalConfiguration()"/>

    <BulkMultiEditModal
        v-if="showMultiEditModal"
        :event-statuses="eventStatuses"
        :event-types="eventTypes"
        :rooms="rooms"
        :event-ids="eventIdsForMultiEdit"
        @close="showMultiEditModal = false"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        @close="showConfirmDeleteModal = false"
        @delete="deleteSelectedEvents"
        :title="$t('Do you really want to delete the selected events?')"
        :description="$t('This action cannot be undone.')"
        @closed="showConfirmDeleteModal = false"
        />
    <IndividualColumnSizeConfigModal
        v-if="showIndividualColumnSizeConfigModal"
        @close="showIndividualColumnSizeConfigModal = false"
    />
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
import PlanningSwitch from "@/Components/Calendar/Elements/PlanningSwitch.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {usePermission} from "@/Composeables/Permission.js";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import {provide, inject} from "vue";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BulkMultiEditModal from "@/Pages/Projects/Components/BulkComponents/BulkMultiEditModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import IndividualColumnSizeConfigModal
    from "@/Pages/Projects/Components/BulkComponents/IndividualColumnSizeConfigModal.vue";
const exportTabEnums = useExportTabEnums();
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
            required: false
        },
        eventStatuses: {
            type: Object,
            required: false
        },
        event_properties: {
            type: Array,
            required: false
        }
    }),
    roomCollisions = ref([]),
    timeArray = ref(!props.isInModal),
    isPlanningEvent = ref((() => {
        // Try to get the stored value from localStorage
        const storedValue = localStorage.getItem(`isPlanningEvent_${props.project.id}`);
        // If there's a stored value, use it; otherwise, use the project's state.is_planning value
        return storedValue !== null ? storedValue === 'true' : (props.project?.state?.is_planning || false);
    })()),
    invalidEvents = ref([]),
    emits = defineEmits(['closed']),
    multiEdit = ref(false),
    eventIdsForMultiEdit = ref([]),
    showMultiEditModal = ref(false),
    showConfirmDeleteModal = ref(false),
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
        },
        {
            id: 4,
            name: 'am gleichen Tag',
            type: 'same_day',
        }
    ]),
    events = reactive([]),
    isLoading = ref(true),
    eventComponentIsVisible = ref(false),
    eventToEdit = ref(null),
    showExportModal = ref(false),
    getEventIdsWhereSelectedForMultiEdit = () => {
        return events.filter(event => event.isSelectedForMultiEdit).map(event => event.id);
    },
    deleteSelectedEvents = () => {
        isLoading.value = true;
        router.delete(route('event.bulk.multi-edit.delete'), {
            data: {
                eventIds: getEventIdsWhereSelectedForMultiEdit()
            },
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                isLoading.value = false;
            }
        });
        events.filter(event => !event.isSelectedForMultiEdit);
    },
    openMultiEditModal = () => {
        eventIdsForMultiEdit.value = getEventIdsWhereSelectedForMultiEdit();
        showMultiEditModal.value = true;
    },
    showIndividualColumnSizeConfigModal = ref(false),
    lastUsedCopyCount = ref(1),
    getExportModalConfiguration = () => {
        const cfg = {};

        cfg[exportTabEnums.EXCEL_EVENT_LIST_EXPORT] = {
            show_artists: usePage().props.createSettings.show_artists,
            project: props.project,
        };

        cfg[exportTabEnums.EXCEL_CALENDAR_EXPORT] = {
            project: props.project,
        };

        return cfg;
    },
    UpdateMultiEditEmits = (value) => {
        multiEdit.value = value;
    },
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
        events.forEach(event => { event.isNew = false; });
        let newDate = new Date();
        if (events.length > 0) {
            let lastEvent = events[events.length - 1];
            newDate = new Date(lastEvent.day);
            newDate.setDate(newDate.getDate() + 1);
        }

        if (props.isInModal) {
            events.push({
                index: events.length + 1,
                status: props.eventStatuses ? props.eventStatuses?.find(status => status.default) : null,
                type: props.eventTypes ? props.eventTypes[0] : null,
                name: props.isInModal ? '' : 'Blocker',
                room: props.rooms ? props.rooms[0] : null,
                day: newDate.toISOString().split('T')[0],
                start_time: '',
                end_time: '',
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                description: '',
                isNew: true,
                is_planning: isPlanningEvent.value,
            });
            isLoading.value = false;
        } else {
            if (events.length > 0) {
                let lastEvent = events[events.length - 1];
                newDate = new Date(lastEvent.day);
                newDate.setDate(newDate.getDate() + 1);

                router.post(route('event.store.bulk.single', {project: props.project}), {
                    event: {
                        status: lastEvent.status,
                        type: lastEvent.type,
                        name: lastEvent.name,
                        room: lastEvent.room,
                        day: newDate.toISOString().split('T')[0],
                        start_time: lastEvent.start_time,
                        end_time: lastEvent.end_time,
                        copy: false,
                        copyCount: 1,
                        copyType: copyTypes.value[0],
                        description: '',
                        isNew: true,
                        is_planning: isPlanningEvent.value
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
                        // status get the default status form the eventStatuses
                        status: props.eventStatuses ? props.eventStatuses?.find(status => status.default) : null,
                        type: props.eventTypes ? props.eventTypes[0] : null,
                        name: props.isInModal ? '' : 'Blocker',
                        room: props.rooms ? props.rooms[0] : null,
                        day: newDate.toISOString().split('T')[0],
                        start_time: '',
                        end_time: '',
                        copy: false,
                        copyCount: 1,
                        copyType: copyTypes.value[0],
                        description: '',
                        isNew: true,
                        is_planning: isPlanningEvent.value
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
        // Store the selected copyCount for later use
        console.log(event.copyCount + 'event');
        lastUsedCopyCount.value = event.copyCount;
        let newDate = new Date(event.day);
        let createdEvents = [];
        for (let i = 0; i < event.copyCount; i++) {
            if (event.copyType.type === 'daily') {
                newDate.setDate(newDate.getDate() + 1);
            } else if (event.copyType.type === 'weekly') {
                newDate.setDate(newDate.getDate() + 7);
            } else if (event.copyType.type === 'monthly') {
                newDate.setMonth(newDate.getMonth() + 1);
            } else if (event.copyType.type === 'same_day') {
                newDate = new Date(event.day);
            }

            events.push({
                index: events.length + 1,
                status: event.status,
                type: event.type,
                name: event.name,
                room: event.room,
                day: newDate.toISOString().split('T')[0],
                start_time: event.start_time,
                end_time: event.end_time,
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                description: event.description,
                isNew: true,
                is_planning: isPlanningEvent.value,
            });

            createdEvents.push({
                status: event.status,
                type: event.type,
                name: event.name,
                room: event.room,
                day: newDate.toISOString().split('T')[0],
                start_time: event.start_time,
                end_time: event.end_time,
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                description: event.description,
                isNew: true,
                is_planning: isPlanningEvent.value
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
            // Ensure newly created events (those without an id) have the is_planning property set
            // This ensures the planning switch only affects newly created events
            if (!event.id && event.is_planning === undefined) {
                event.is_planning = isPlanningEvent;
            }
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
    updateUserSortId = (id) => {
        isLoading.value = true;
        router.patch(
            route('user.update_bulk_sort_id', {user: usePage().props.auth.user.id}),
            {
                bulk_sort_id: id
            },
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    isLoading.value = false;
                }
            }
        );
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
                status: props.eventStatuses.find(status => status.id === event.event_status_id),
                name: event.eventName,
                room: props.rooms.find(room => room.id === event.room_id),
                day: event.event_date_without_time.start_clear,
                start_time: !event.allDay ? event.start_time_without_day : '',
                end_time: !event.allDay ? event.end_time_without_day : '',
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                index: events.length + 1,
                description: event.description,
                isNew: false, // Standardmäßig false setzen
                // Set the is_planning property from the event data
                is_planning: event.is_planning
            });
        });

        // Die letzten {copyCount} Events als "neu" markieren
        if (events.length > 0) {
            events.forEach(event => event.isNew = false);

            console.log(lastUsedCopyCount);
            // Use the lastUsedCopyCount variable instead of reading from the event
            const copyCount = lastUsedCopyCount.value;

            // Mark the last {copyCount} events as new
            for (let i = 0; i < copyCount; i++) {
                const index = events.length - 1 - i;
                if (index >= 0) {
                    events[index].isNew = true;
                }
            }
        }

        isLoading.value = false;
    } else {
        isLoading.value = false;
    }

    if (usePage().props.auth.user.bulk_sort_id === 3) {
        events.sort((a, b) => {
            if (a.day === b.day) {
                if (a.start_time === b.start_time) {
                    return a.room.position - b.room.position;
                }
                return a.start_time.localeCompare(b.start_time);
            }
            return a.day.localeCompare(b.day);
        });
    }

    if (props.isInModal) {
        addEmptyEvent();
    }

    provide('event_properties', props.event_properties);
});

watch(events, (newEvents) => {
    newEvents.forEach(event => {
        if (event.name) {
            invalidEvents.value = invalidEvents.value.filter(invalidEvent => invalidEvent !== event);
        }
    });
}, {deep: true});

// Watch for changes to isPlanningEvent and store in localStorage
watch(isPlanningEvent, (newValue) => {
    localStorage.setItem(`isPlanningEvent_${props.project.id}`, newValue.toString());
});
</script>
