<template>
    <div
        :style="{ width: width + 'px', minHeight: totalHeight - heightSubtraction(event) * zoom_factor + 'px', backgroundColor: backgroundColorWithOpacity, fontsize: fontSize, lineHeight: lineHeight }"
        class="rounded-lg relative group" :class="event.occupancy_option ? 'event-disabled' : ''">
        <div v-if="zoom_factor > 0.4"
             class="absolute w-full h-full z-10 rounded-lg group-hover:block flex justify-center align-middle items-center"
             :class="event.clicked ? 'block bg-green-200/50' : 'hidden bg-artwork-buttons-create/50'">
            <div class="flex justify-center items-center h-full gap-2" v-if="!multiEdit && !event.clicked">
                <a v-if="event.projectId && !project" type="button" :href="getEditHref(event.projectId)"
                   class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconLink stroke-width="1.5" class="h-4 w-4"/>
                </a>
                <button type="button" @click="$emit('editEvent', event)"
                        class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                </button>
                <button v-if="(isRoomAdmin || isCreator || hasAdminRole) && event.eventTypeId === 1"
                        @click="$emit('openAddSubEventModal', event, 'create', null)"
                        type="button"
                        class="rounded-full bg-artwork-buttons-create text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconCirclePlus stroke-width="1.5" stroke="currentColor" class="w-6 h-6"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || hasAdminRole" type="button"
                        @click="$emit('showDeclineEventModal', event)"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconX stroke-width="1.5"
                           stroke="currentColor" class="w-4 h-4"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || hasAdminRole"
                        @click="$emit('openConfirmModal', event, 'main')" type="button"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconTrash stroke-width="1.5"
                               stroke="currentColor" class="w-4 h-4"/>
                </button>
            </div>
            <div v-else class="flex justify-center items-center h-full gap-2">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input v-model="event.clicked"
                               aria-describedby="candidates-description"
                               name="candidates" type="checkbox"
                               :id="event.id"
                               class="h-5 w-5 border-gray-300 text-green-400 focus:ring-green-600"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-1 py-1 ">
            <div :style="{lineHeight: lineHeight,fontSize: fontSize, color: textColorWithDarken}"
                 :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']"
                 class="flex justify-between ">
                <div v-if="!project" class="flex items-center relative w-full">
                    <div v-if="event.eventTypeAbbreviation" class="mr-1">
                        {{ event.eventTypeAbbreviation }}:
                    </div>
                    <div :style="{ width: width - (64 * zoom_factor) + 'px'}" class=" truncate">
                        {{ event.eventName ?? event.projectName }}
                    </div>
                    <div v-if="usePage().props.user.calendar_settings.project_status" class="absolute right-1">
                        <div v-if="event.projectStateColor"
                             :class="[event.projectStateColor,zoom_factor <= 0.8 ? 'border-2' : 'border-4']"
                             class="rounded-full">
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="flex items-center" v-if="event.title !== event.eventTypeName">
                        <div v-if="event.eventTypeAbbreviation" class="mr-1">
                            {{ event.eventTypeAbbreviation }}:
                        </div>
                        <div :style="{ width: width - (64 * zoom_factor) + 'px'}" class=" truncate">
                            {{ event.alwaysEventName }}
                        </div>
                    </div>
                    <div v-else :style="{ width: width - (55 * zoom_factor) + 'px'}" class=" truncate">
                        {{ event.eventTypeName }}
                    </div>
                </div>
                <!-- Icon -->
                <div v-if="event.audience"
                     class="flex">
                    <IconUsersGroup stroke-width="1.5" :width="22 * zoom_factor" :height="11 * zoom_factor"/>
                </div>
            </div>
            <div class="flex">
                <!-- Time -->
                <div class="flex" :style="{lineHeight: lineHeight,fontSize: fontSize, color: textColorWithDarken}"
                     :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
                    <div
                        v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance"
                        class="items-center">
                        <div v-if="event.allDay">
                            {{ $t('Full day') }}
                        </div>
                        <div v-else>
                            {{
                                new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                            }}
                        </div>
                    </div>
                    <div class="flex w-full" v-else>
                        <div v-if="event.allDay">
                            <div
                                v-if="atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()">
                                {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }}
                            </div>
                            <div v-else>
                                {{ $t('Full day') }}, {{ new Date(event.start).format("DD.MM.") }} - {{
                                    new Date(event.end).format("DD.MM.")
                                }}
                            </div>
                        </div>
                        <div v-else class="items-center">
                            <div v-if="new Date(event.start).toDateString() !== new Date(event.end).toDateString()">
                            <span class="text-error">
                                {{
                                    new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : ''
                                }}
                            </span>
                                {{
                                    new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("DD.MM. HH:mm")
                                }}
                            </div>
                            <div v-else>
                                <div v-if="atAGlance">
                                    {{
                                        new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                                    }}
                                </div>
                                <div v-else>
                                    {{
                                        new Date(event.start).format("HH:mm") + ' - ' + new Date(event.end).format("HH:mm")
                                    }}
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="event.option_string && usePage().props.user.calendar_settings.options"
                     class="flex items-center">
                    <div
                        v-if="!atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                        class="flex eventTime font-medium subpixel-antialiased"
                        :style="{lineHeight: lineHeight,fontSize: fontSize}">
                        , {{ event.option_string }}
                    </div>
                    <div class="flex eventTime font-medium subpixel-antialiased ml-0.5" v-else>
                        ({{ event.option_string.charAt(7) }})
                    </div>
                </div>
            </div>
            <!-- repeating Event -->
            <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                 :class="[zoom_factor === 1 ? 'eventText' : '', 'font-semibold']"
                 v-if="usePage().props.user.calendar_settings.repeating_events && event.is_series"
                 class="uppercase flex items-center">
                <IconRepeat class="mx-1 h-3 w-3" stroke-width="1.5"/>
                {{ $t('Repeat event') }}
            </div>
            <!-- User-Icons -->
            <div class="-ml-3 mb-0.5 w-full"
                 v-if="usePage().props.user.calendar_settings.project_management && event.projectLeaders?.length > 0">
                <div v-if="event.projectLeaders && !project && zoom_factor >= 0.8"
                     class="mt-1 ml-5 flex flex-wrap">
                    <div class="flex flex-wrap flex-row -ml-1.5"
                         v-for="user in event.projectLeaders?.slice(0,3)">
                        <img :src="user.profile_photo_url" alt=""
                             class="mx-auto shrink-0 flex object-cover rounded-full"
                             :class="['h-' + 5 * zoom_factor, 'w-' + 5 * zoom_factor]">
                    </div>
                    <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                        <Menu as="div" class="relative">
                            <MenuButton class="flex rounded-full focus:outline-none">
                                <div
                                    :class="'h-5 w-5'"
                                    class="-ml-1.5 flex-shrink-0 flex items-center my-auto font-semibold rounded-full shadow-sm text-white bg-black">
                                    <p class="">
                                        +{{ event.projectLeaders.length - 3 }}
                                    </p>
                                </div>
                            </MenuButton>
                            <transition enter-active-class="transition-enter-active"
                                        enter-from-class="transition-enter-from"
                                        enter-to-class="transition-enter-to"
                                        leave-active-class="transition-leave-active"
                                        leave-from-class="transition-leave-from"
                                        leave-to-class="transition-leave-to">
                                <MenuItems
                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <MenuItem v-for="user in event.projectLeaders" v-slot="{ active }">
                                        <Link href="#"
                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <img :class="'h-5 w-5'"
                                                 class="rounded-full"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="ml-4">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                        </Link>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="usePage().props.user.calendar_settings.work_shifts" class="ml-1 pb-1 text-xs">
            <div v-for="shift in event.shifts">
                <span>{{ shift.craft.abbreviation }}</span>
                <span>
                    &nbsp;({{ shift.worker_count }}/{{ shift.max_worker_count }})
                </span>
            </div>
        </div>
    </div>
    <div v-if="event.subEvents?.length > 0">
        <div v-for="subEvent in event.subEvents" class="mb-1">
            <div class="w-full relative group rounded-lg border-l-[6px] border-[#A7A6B115]">
                <div
                    class="bg-indigo-500/50 hidden absolute w-full h-full rounded-lg group-hover:block flex justify-center align-middle items-center">
                    <div class="flex justify-center items-center h-full gap-2">
                        <button @click="$emit('editSubEvent', subEvent, 'edit', event)" type="button"
                                class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                        </button>
                        <button v-if="isRoomAdmin || isCreator || hasAdminRole"
                                @click="$emit('openConfirmModal', subEvent, 'sub')" type="button"
                                class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <IconTrash stroke-width="1.5"
                                       stroke="currentColor" class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                <div :class="[subEvent.class]"
                     :style="{ width: width + 'px', height: (totalHeight - heightSubtraction(subEvent)) * zoom_factor + 'px' }"
                     class="px-1 py-0.5 rounded-lg overflow-y-auto">
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                         :class="[zoom_factor === 1 ? 'eventHeader' : '', 'font-bold']"
                         class="flex justify-between">
                        <div class="flex" v-if="subEvent.title?.length > 0">
                            <div v-if="subEvent.eventTypeAbbreviation" class="mr-1">
                                {{ subEvent.eventTypeAbbreviation }}:
                            </div>
                            <div class="flex items-center">
                                {{ subEvent.title }}
                            </div>
                        </div>
                        <div v-else class="flex items-center">
                            {{ subEvent.eventTypeName }}
                        </div>
                        <!-- Icons -->
                        <div v-if="subEvent.audience"
                             class="flex">
                            <IconUsersGroup stroke-width="1.5" :width="22 * zoom_factor" :height="11 * zoom_factor"/>
                        </div>
                    </div>
                    <!-- Time -->
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                         :class="[zoom_factor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']"
                         class="flex">
                        <div
                            v-if="new Date(subEvent.start).toDateString() === new Date(subEvent.end).toDateString() && !project && !atAGlance"
                            class="items-center">
                            <div v-if="subEvent.allDay">
                                {{ $t('Full day') }}
                            </div>
                            <div v-else>
                                {{
                                    new Date(subEvent.start).formatTime("HH:mm")
                                }} - {{ new Date(subEvent.end).formatTime("HH:mm") }}
                            </div>
                        </div>
                        <div class="flex w-full" v-else>
                            <div v-if="subEvent.allDay">
                                <div
                                    v-if="atAGlance && new Date(subEvent.start).toDateString() === new Date(subEvent.end).toDateString()">
                                    {{ $t('Full day') }}, {{ new Date(subEvent.start).format("DD.MM.") }}
                                </div>
                                <div v-else>
                                    <span class="text-error">
                        {{
                                            new Date(subEvent.start).toDateString() !== new Date(subEvent.end).toDateString() ? '!' : ''
                                        }}
                            </span>
                                    {{ $t('Full day') }}, {{ new Date(subEvent.start).format("DD.MM.") }} - {{
                                        new Date(subEvent.end).format("DD.MM.")
                                    }}
                                </div>

                            </div>
                            <div v-else class="items-center">
                            <span class="text-error">
                        {{
                                    new Date(subEvent.start).toDateString() !== new Date(subEvent.end).toDateString() ? '!' : ''
                                }}
                            </span>
                                {{
                                    new Date(subEvent.start).format("DD.MM. HH:mm")
                                }} - {{ new Date(subEvent.end).format("DD.MM. HH:mm") }}
                            </div>
                        </div>
                    </div>
                    <div v-if="usePage().props.user.calendar_settings.work_shifts" class="ml-0.5 text-xs">
                        <div v-for="shift in subEvent.shifts">
                            <span>{{ shift.craft.abbreviation }}</span>
                            (
                            <VueMathjax
                                :formula="convertToMathJax(decimalToFraction(shift.user_count ? shift.user_count : 0))"/>
                            /{{ shift.number_employees }}
                            <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{
                                    shift.number_masters
                                }}</span>)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import {Link, usePage} from "@inertiajs/vue3";
import {IconCirclePlus, IconEdit, IconLink, IconRepeat, IconTrash, IconUsersGroup, IconX} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import VueMathjax from "vue-mathjax-next";
import {useI18n} from "vue-i18n";

const {t} = useI18n(), $t = t;
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const atAGlance = ref(usePage().props.user.at_a_glance ?? false);
const deleteComponentVisible = ref(false);
const deleteTitle = ref('');
const deleteDescription = ref('');
const deleteType = ref('');

const emits = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal'
]);

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    fontSize: {
        type: String,
        required: false,
        default: '0.875rem'
    },
    lineHeight: {
        type: String,
        required: false,
        default: '1.25rem'
    },
    first_project_tab_id: {
        type: Number,
        required: false,
        default: 1
    },
    project: {
        type: Object,
        required: false,
        default: null
    },
    hasAdminRole: {
        type: Boolean,
        required: false,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    },
    width: {
        type: Number,
        required: true
    },
});

const isRoomAdmin = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId)?.admins.some(admin => admin.id === usePage().props.user.id) || false;
});

const isCreator = computed(() => {
    return props.event.created_by.id === usePage().props.user.id
});

const roomCanBeBookedByEveryone = computed(() => {
    return props.rooms?.find(room => room.id === props.event.roomId).everyone_can_book
});

const backgroundColorWithOpacity = computed(() => {
    const percent = 15;
    const color = props.event.event_type_color;
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
});

const textColorWithDarken = computed(() => {
    const percent = 75;
    const color = props.event.event_type_color;
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
});

const totalHeight = computed(() => {
    let height = 42;
    // ProjectStatus is in same row as name -> no extra height needed
    if (usePage().props.user.calendar_settings.project_status) height += 0;
    //Options are in same row as time -> no extra height needed
    if (usePage().props.user.calendar_settings.options) height += 0;
    if (usePage().props.user.calendar_settings.project_management) height += 17;
    if (usePage().props.user.calendar_settings.repeating_events) height += 20;
    if (usePage().props.user.calendar_settings.work_shifts) height += 18;
    return height;
});

const heightSubtraction = (event) => {
    let heightSubtraction = 0;
    if (usePage().props.user.calendar_settings.project_management && (!event.projectLeaders || event.projectLeaders?.length < 1)) {
        heightSubtraction += 17;
    }
    if (usePage().props.user.calendar_settings.repeating_events && (!event.is_series || event.is_series === false)) {
        heightSubtraction += 20;
    }
    if (usePage().props.user.calendar_settings.work_shifts && (!event.shifts || event.shifts?.length < 1)) {
        heightSubtraction += 18;
    }
    return heightSubtraction;
};

const convertToMathJax = (fraction) => {
    const parts = fraction.split(' ');

    if (parts.length === 1) {
        return `${parts[0]}`;
    } else {
        const wholePart = parts[0] > 0
            ? parts[0]
            : "";
        const fractionParts = parts[1].split('/');
        const numerator = fractionParts[0];
        const denominator = fractionParts[1];
        return `${wholePart}$\\frac{${numerator}}{${denominator}}$`;
    }
};

const decimalToFraction = (decimal) => {
    let wholePart = Math.floor(decimal);
    decimal = decimal - wholePart;

    if (decimal === parseInt(decimal)) {
        if (decimal < 1) {
            return `${wholePart}`;
        }
        return `${parseInt(decimal)}/1`;
    } else {
        let precision = getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000; // The desired precision for the fraction
        let top = Math.round(decimal * precision);
        let bottom = precision;

        let x = gcd(top, bottom);
        return `${wholePart} ${top / x}/${bottom / x}`;
    }
};

const getFirstDigitAfterDecimal = (number) => {
    const decimalPart = number.toString().split('.')[1];
    if (decimalPart && decimalPart.length > 0) {
        return parseInt(decimalPart[0]);
    }
    return null; // Return null if there is no decimal part
};

const gcd = (a, b) => {
    return (b) ? gcd(b, a % b) : a;
};

const getEditHref = (projectId) => {
    return route('projects.tab', {project: projectId, projectTab: props.first_project_tab_id});
};
</script>
