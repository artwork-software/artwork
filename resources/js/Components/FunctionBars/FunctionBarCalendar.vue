<template>
    <div class="pl-5 py-4 sticky z-50 top-0" :class="project ? 'bg-white -mx-16 pr-10' : 'bg-gray-50 pr-16'">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div v-if="!project" class="flex flex-row">
                    <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="false"/>
                    <div v-if="dateValue && dateValue[0] === dateValue[1]" class="flex items-center">
                        <button class="ml-2 text-black previousDay" @click="previousDay">
                            <IconChevronLeft class="h-5 w-5 text-primary"/>
                        </button>
                        <button class="ml-2 text-black nextDay" @click="nextDay">
                            <IconChevronRight class="h-5 w-5 text-primary"/>
                        </button>
                    </div>
                    <div v-else class="flex items-center">
                        <button class="ml-2 text-black previousTimeRange" @click="previousTimeRange">
                            <IconChevronLeft class="h-5 w-5 text-primary"/>
                        </button>
                        <button class="ml-2 text-black nextTimeRange" @click="nextTimeRange">
                            <IconChevronRight class="h-5 w-5 text-primary"/>
                        </button>
                    </div>
                </div>
                <div :class="[project ? 'ml-10' : '','flex items-center']"  v-if="!project">
                    <div @click="showCalendarAboSettingModal = true"
                         class="flex items-center gap-x-1 text-sm group cursor-pointer">
                        <IconCalendarStar
                            class="h-5 w-5 group-hover:text-yellow-500 duration-150 transition-all ease-in-out"/>
                        {{ $t('Subscribe to calendar') }}
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-x-2">
                <div v-if="dateValue[0] !== dateValue[1]" class="flex items-center">
                    <div class="flex items-center gap-x-2">
                        <MultiEditSwitch :multi-edit="multiEdit" :room-mode="roomMode"
                                         @update:multi-edit="UpdateMultiEditEmits"/>
                        <Switch @click="changeAtAGlance()" v-if="!roomMode" v-model="atAGlance"
                                :class="[atAGlance ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                            <span class="sr-only">Use setting</span>
                            <span
                                :class="[atAGlance ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                          <span
                              :class="[atAGlance ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                              aria-hidden="true">
                             <IconList stroke-width="1.5" class="w-5 h-5"/>
                          </span>
                          <span
                              :class="[atAGlance ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                              aria-hidden="true">
                              <IconList stroke-width="1.5" class="w-5 h-5"/>
                          </span>
                    </span>
                        </Switch>
                    </div>
                </div>
                <div class="flex items-center gap-x-4">
                    <IconZoomIn @click="incrementZoomFactor" :disabled="zoom_factor <= 0.2" v-if="!atAGlance"
                                class="h-7 w-7 text-artwork-buttons-context cursor-pointer"></IconZoomIn>
                    <IconZoomOut @click="decrementZoomFactor" :disabled="zoom_factor >= 1.4"
                                 v-if="!atAGlance"
                                 class="h-7 w-7 text-artwork-buttons-context cursor-pointer"></IconZoomOut>
                    <IconArrowsDiagonal class="h-7 w-7 text-artwork-buttons-context cursor-pointer"
                                        @click="$emit('openFullscreenMode')" v-if="!atAGlance && !isFullscreen"/>
                    <IndividualCalendarFilterComponent
                        class=""
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :at-a-glance="atAGlance"
                        :type="project ? 'project' : 'individual'"
                        :user_filters="user_filters"
                        :extern-updated="externUpdate"
                    />


                    <Menu as="div" class="relative inline-block items-center text-left">
                        <div class="flex items-center">
                            <MenuButton>
                            <span class="items-center flex">
                                <button type="button"
                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <IconSettings class="h-7 w-7 text-artwork-buttons-context"/>
                                </button>
                                <span
                                    v-if="usePage().props.user.calendar_settings.project_status || usePage().props.user.calendar_settings.options || usePage().props.user.calendar_settings.project_management || usePage().props.user.calendar_settings.repeating_events || usePage().props.user.calendar_settings.work_shifts"
                                    class="rounded-full border-2 border-error w-2 h-2 bg-error absolute ml-6 ring-white ring-1">
                                </span>
                            </span>
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
                                class="w-80 absolute right-0 top-12 origin-top-right shadow-lg bg-artwork-navigation-background rounded-lg ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="w-76 p-6">
                                    <div class="flex items-center py-1" v-if="!project">
                                        <input v-model="userCalendarSettings.project_status"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <div
                                            :class="userCalendarSettings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                            class=" ml-4 my-auto text-secondary">{{ $t('Project Status') }}
                                        </div>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input v-model="userCalendarSettings.options"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <p :class="userCalendarSettings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class=" ml-4 my-auto text-secondary">{{ $t('Option prioritization') }}</p>
                                    </div>
                                    <div class="flex items-center py-1" v-if="!project">
                                        <input v-model="userCalendarSettings.project_management"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <p :class="userCalendarSettings.project_management ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class=" ml-4 my-auto text-secondary">{{ $t('Project managers') }}</p>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input v-model="userCalendarSettings.repeating_events"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <p :class="userCalendarSettings.repeating_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class=" ml-4 my-auto text-secondary">{{ $t('Repeat event') }}</p>
                                    </div>
                                    <div class="flex items-center py-1" v-if="canAny(['can manage workers', 'can plan shifts'])">
                                        <input v-model="userCalendarSettings.work_shifts"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <p :class="userCalendarSettings.work_shifts ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                           class=" ml-4 my-auto text-secondary">{{ $t('Shifts') }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button class="text-sm mx-3 mb-4" @click="saveUserCalendarSettings">{{
                                            $t('Save')
                                        }}
                                    </button>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>

                <div @click="showPDFConfigModal = true">
                    <IconFileExport class="h-7 w-7 text-artwork-buttons-context cursor-pointer"/>
                </div>

                <PlusButton @click="$emit('wantsToAddNewEvent');"/>
                <AddButtonSmall
                    @click="createEventComponentIsVisible = true"
                    :text="$t('New occupancy')"
                    class="hidden"
                />
            </div>
        </div>
        <div class="w-full overflow-y-scroll" :class="activeFilters.length > 0 ? 'mt-10' : ''">
            <div class="mb-1 ml-4 max-w-7xl">
                <div class="flex">
                    <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter"
                                   @removeFilter="removeFilter"/>
                </div>

            </div>
        </div>
    </div>

    <PdfConfigModal v-if="showPDFConfigModal" @closed="showPDFConfigModal = false" :project="project"
                    :pdf-title="project ? project.name : 'Raumbelegung'"/>

    <GeneralCalendarAboSettingModal
        :event-types="eventTypes"
        :rooms="rooms"
        :areas="areas"
        v-if="showCalendarAboSettingModal"
        @close="closeCalendarAboSettingModal"
    />

    <CalendarAboInfoModal v-if="showCalendarAboInfoModal" @close="showCalendarAboInfoModal = false" />

</template>

<script setup>

import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {computed, inject, ref} from "vue";
import {
    IconArrowsDiagonal,
    IconCalendarStar,
    IconChevronLeft,
    IconChevronRight,
    IconFileExport,
    IconList,
    IconSettings,
    IconZoomIn,
    IconZoomOut
} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import GeneralCalendarAboSettingModal from "@/Pages/Events/Components/GeneralCalendarAboSettingModal.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {Menu, MenuButton, MenuItems, Switch} from "@headlessui/vue";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
import PdfConfigModal from "@/Layouts/Components/PdfConfigModal.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";

const eventTypes = inject('eventTypes');
const rooms = inject('rooms');
const areas = inject('areas');
const dateValue = inject('dateValue');
const first_project_tab_id = inject('first_project_tab_id');
const filterOptions = inject('filterOptions');
const personalFilters = inject('personalFilters');
const user_filters = inject('user_filters');

const showCalendarAboSettingModal = ref(false);
const atAGlance = ref(usePage().props.user.at_a_glance ?? false);
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const dateValueCopy = ref(dateValue ?? []);
const showPDFConfigModal = ref(false);
const wantedRoom = ref(null)
const roomCollisions = ref([])
const externUpdate = ref(false)
const showCalendarAboInfoModal = ref(false)
const userCalendarSettings = useForm({
    project_status: usePage().props.user.calendar_settings ? usePage().props.user.calendar_settings.project_status : false,
    options: usePage().props.user.calendar_settings ? usePage().props.user.calendar_settings.options : false,
    project_management: usePage().props.user.calendar_settings ? usePage().props.user.calendar_settings.project_management : false,
    repeating_events: usePage().props.user.calendar_settings ? usePage().props.user.calendar_settings.repeating_events : false,
    work_shifts: usePage().props.user.calendar_settings ? usePage().props.user.calendar_settings.work_shifts : false
})

const {hasAdminRole, canAny} = usePermission(usePage().props);

const emits = defineEmits(['updateMultiEdit', 'openFullscreenMode', 'wantsToAddNewEvent'])

const props = defineProps({
    project: {
        type: Object,
        default: null
    },
    multiEdit: {
        type: Boolean,
        default: false
    },
    roomMode: {
        type: Boolean,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    },
    isFullscreen: {
        type: Boolean,
        required: false,
        default: false
    },
})

const activeFilters = computed(() => {
    let activeFiltersArray = []
    filterOptions.rooms.forEach((room) => {
        if (user_filters.rooms?.includes(room.id)) {
            activeFiltersArray.push(room)
        }
    })

    filterOptions.areas.forEach((area) => {
        if (user_filters.areas?.includes(area.id)) {
            activeFiltersArray.push(area)
        }
    })

    filterOptions.eventTypes.forEach((eventType) => {
        if (user_filters.event_types?.includes(eventType.id)) {
            activeFiltersArray.push(eventType)
        }
    })

    filterOptions.roomCategories.forEach((category) => {
        if (user_filters.room_categories?.includes(category.id)) {
            activeFiltersArray.push(category)
        }
    })

    filterOptions.roomAttributes.forEach((attribute) => {
        if (user_filters.room_attributes?.includes(attribute.id)) {
            activeFiltersArray.push(attribute)
        }
    })

    if (user_filters.is_loud) {
        activeFiltersArray.push({name: "Laute Termine", value: 'isLoud', user_filter_key: 'is_loud'})
    }

    if (user_filters.is_not_loud) {
        activeFiltersArray.push({name: "Ohne laute Termine", value: 'isNotLoud', user_filter_key: 'is_not_loud'})
    }

    if (user_filters.adjoining_no_audience) {
        activeFiltersArray.push({
            name: "Ohne Nebenveranstaltung mit Publikum",
            value: 'adjoiningNoAudience',
            user_filter_key: 'adjoining_no_audience'
        })
    }

    if (user_filters.adjoining_not_loud) {
        activeFiltersArray.push({
            name: "Ohne laute Nebenveranstaltung",
            value: 'adjoiningNotLoud',
            user_filter_key: 'adjoining_not_loud'
        })
    }

    if (user_filters.has_audience) {
        activeFiltersArray.push({name: "Mit Publikum", value: 'hasAudience', user_filter_key: 'has_audience'})
    }

    if (user_filters.has_no_audience) {
        activeFiltersArray.push({name: "Ohne Publikum", value: 'hasNoAudience', user_filter_key: 'has_no_audience'})
    }

    if (user_filters.show_adjoining_rooms) {
        activeFiltersArray.push({
            name: "Nebenräume anzeigen",
            value: 'showAdjoiningRooms',
            user_filter_key: 'show_adjoining_rooms'
        })
    }

    return activeFiltersArray
})

const closeCalendarAboSettingModal = (bool) => {
    showCalendarAboSettingModal.value = false;
    if(bool){
        showCalendarAboInfoModal.value = true;
    }
}

const UpdateMultiEditEmits = (value) => {
    emits('updateMultiEdit', value)
}

const changeAtAGlance = () => {
    router.patch(route('user.update.at_a_glance', usePage().props.user.id), {
        at_a_glance: !atAGlance.value
    }, {
        preserveState: false,
        preserveScroll: true
    })
}

const incrementZoomFactor = () => {
    if (zoom_factor.value < 1.4) {
        zoom_factor.value = Math.round((zoom_factor.value + 0.2) * 10) / 10;
        updateZoomFactorInUser();
    }
}


const decrementZoomFactor = () => {
    if (zoom_factor.value > 0.4) {
        zoom_factor.value = Math.round((zoom_factor.value - 0.2) * 10) / 10;
        updateZoomFactorInUser();
    }
}

const updateZoomFactorInUser = () => {
    router.patch(route('user.update.zoom_factor', {user: usePage().props.user.id}), {
        zoom_factor: zoom_factor.value
    }, {
        preserveScroll: true,
        preserveState: false
    })
}


const calculateDateDifference = () => {
    const date1 = new Date(dateValueCopy.value[0]);
    const date2 = new Date(dateValueCopy.value[1]);
    const timeDifference = date2.getTime() - date1.getTime();
    return timeDifference / (1000 * 3600 * 24);
}
const previousTimeRange = () => {
    const dayDifference = calculateDateDifference();
    dateValueCopy.value[1] = getPreviousDay(dateValueCopy.value[0]);
    const newDate = new Date(dateValueCopy.value[1]);
    newDate.setDate(newDate.getDate() - dayDifference);
    dateValueCopy.value[0] = newDate.toISOString().slice(0, 10);
    updateTimes();
}
const nextTimeRange = () => {
    const dayDifference = calculateDateDifference();
    dateValueCopy.value[0] = getNextDay(dateValueCopy.value[1]);
    const newDate = new Date(dateValueCopy.value[1]);
    newDate.setDate(newDate.getDate() + dayDifference + 1);
    dateValueCopy.value[1] = newDate.toISOString().slice(0, 10);
    updateTimes();
}
const getNextDay = (dateString) => {
    const date = new Date(dateString);
    date.setDate(date.getDate() + 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const getPreviousDay = (dateString) => {
    const date = new Date(dateString);
    date.setDate(date.getDate() - 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
const updateTimes = () => {
    router.patch(route('update.user.calendar.filter.dates', usePage().props.user.id), {
        start_date: dateValueCopy.value[0],
        end_date: dateValueCopy.value[1],
    }, {
        preserveScroll: false,
        preserveState: false
    });
}

const saveUserCalendarSettings = () => {
    userCalendarSettings.patch(route('user.calendar_settings.update', {user: usePage().props.user.id}))
}


const removeFilter = (filter) => {
    if (filter.value === 'isLoud') {
        updateFilterValue('is_loud', false);
    }

    if (filter.value === 'isNotLoud') {
        updateFilterValue('is_not_loud', false)
    }

    if (filter.value === 'adjoiningNoAudience') {
        updateFilterValue('adjoining_no_audience', false)
    }

    if (filter.value === 'adjoiningNotLoud') {
        updateFilterValue('adjoining_not_loud', false)
    }

    if (filter.value === 'hasAudience') {
        updateFilterValue('has_audience', false)
    }

    if (filter.value === 'hasNoAudience') {
        updateFilterValue('has_no_audience', false)
    }

    if (filter.value === 'showAdjoiningRooms') {
        updateFilterValue('show_adjoining_rooms', false)
    }

    if (filter.value === 'rooms') {
        user_filters.rooms.splice(user_filters.rooms.indexOf(filter.id), 1);
        updateFilterValue('rooms', user_filters.rooms.length > 0 ? user_filters.rooms : null)
    }

    if (filter.value === 'room_categories') {
        user_filters.room_categories.splice(user_filters.room_categories.indexOf(filter.id), 1);
        updateFilterValue('room_categories', user_filters.room_categories.length > 0 ? user_filters.room_categories : null)
    }

    if (filter.value === 'areas') {
        user_filters.areas.splice(user_filters.areas.indexOf(filter.id), 1);
        updateFilterValue('areas', user_filters.areas.length > 0 ? user_filters.areas : null)
    }

    if (filter.value === 'event_types') {
        user_filters.event_types.splice(user_filters.event_types.indexOf(filter.id), 1);
        updateFilterValue('event_types', user_filters.event_types.length > 0 ? user_filters.event_types : null)
    }

    if (filter.value === 'room_attributes') {
        user_filters.room_attributes.splice(user_filters.room_attributes.indexOf(filter.id), 1);
        updateFilterValue('room_attributes', user_filters.room_attributes.length > 0 ? user_filters.room_attributes : null)
    }
}

const updateFilterValue = (key, value) => {
    router.patch(route('user.calendar.filter.single.value.update', {user: usePage().props.user.id}), {
        key: key,
        value: value
    }, {
        preserveScroll: true,
        preserveState: false
    });
}

</script>
