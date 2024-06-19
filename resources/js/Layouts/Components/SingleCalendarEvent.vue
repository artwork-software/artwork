<template>
    <div
        :style="{ width: width + 'px', minHeight: totalHeight - heightSubtraction(event) * zoomFactor + 'px', backgroundColor: backgroundColorWithOpacity, fontsize: fontSize, lineHeight: lineHeight }"
        class="rounded-lg relative group">
        <div v-if="zoomFactor > 0.4"
             class="absolute w-full h-full rounded-lg group-hover:block flex justify-center align-middle items-center"
             :class="event.clicked ? 'block bg-green-200/50' : 'hidden bg-artwork-buttons-create/50'">
            <div class="flex justify-center items-center h-full gap-2" v-if="!multiEdit">
                <a v-if="event.projectId && !project" type="button" :href="getEditHref(event.projectId)"
                   class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconLink stroke-width="1.5" class="h-4 w-4"/>
                </a>
                <button type="button" @click="openEditEventModal(event)"
                        class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" @click="openAddSubEventModal"
                        v-show="event.eventTypeId === 1" type="button"
                        class="rounded-full bg-artwork-buttons-create text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <IconCirclePlus stroke-width="1.5" stroke="currentColor" class="w-6 h-6"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" type="button"
                        @click="showDeclineEventModal = true"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconX stroke-width="1.5"
                           stroke="currentColor" class="w-4 h-4"/>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()"
                        @click="openConfirmModal(event.id, 'main')" type="button"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <IconTrash stroke-width="1.5"
                               stroke="currentColor" class="w-4 h-4"/>
                </button>
            </div>
            <div v-else class="flex justify-center items-center h-full gap-2">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input v-model="event.clicked" @click="$emit('checkEvent', event)" id="candidates"
                               aria-describedby="candidates-description"
                               name="candidates" type="checkbox"
                               class="h-5 w-5 border-gray-300 text-green-400 focus:ring-green-600"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-1 py-1 ">
            <div :style="{lineHeight: lineHeight,fontSize: fontSize, color: TextColorWithDarken}"
                 :class="[zoomFactor === 1 ? 'eventHeader' : '', 'font-bold']"
                 class="flex justify-between ">
                <div v-if="!project" class="flex items-center relative w-full">
                    <div v-if="event.eventTypeAbbreviation" class="mr-1">
                        {{ event.eventTypeAbbreviation }}:
                    </div>
                    <div :style="{ width: width - (64 * zoomFactor) + 'px'}" class=" truncate">
                        {{ event.eventName ?? event.project.name }}
                    </div>
                    <div v-if="$page.props.user.calendar_settings.project_status" class="absolute right-1">
                        <div v-if="event.project?.state?.color"
                             :class="[event.project.state.color,zoomFactor <= 0.8 ? 'border-2' : 'border-4']"
                             class="rounded-full">
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="flex items-center" v-if="event.title !== event.eventTypeName">
                        <div v-if="event.eventTypeAbbreviation" class="mr-1">
                            {{ event.eventTypeAbbreviation }}:
                        </div>
                        <div :style="{ width: width - (64 * zoomFactor) + 'px'}" class=" truncate">
                            {{ event.alwaysEventName }}
                        </div>
                    </div>
                    <div v-else :style="{ width: width - (55 * zoomFactor) + 'px'}" class=" truncate">
                        {{ event.eventTypeName }}
                    </div>
                </div>
                <!-- Icon -->
                <div v-if="event.audience"
                     class="flex">
                    <IconUsersGroup stroke-width="1.5" :width="22 * zoomFactor" :height="11 * zoomFactor"/>
                </div>
            </div>
            <div class="flex">
                <!-- Time -->
                <div class="flex" :style="{lineHeight: lineHeight,fontSize: fontSize, color: TextColorWithDarken}"
                     :class="[zoomFactor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
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
                                    {{ new Date(event.start).format("DD.MM. HH:mm") + ' - ' + new Date(event.end).format("HH:mm") }}
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
                <div v-if="event.option_string && $page.props.user.calendar_settings.options" class="flex items-center">
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
                 :class="[zoomFactor === 1 ? 'eventText' : '', 'font-semibold']"
                 v-if="$page.props.user.calendar_settings.repeating_events && event.is_series"
                 class="uppercase flex items-center">
                <IconRepeat class="mx-1 h-3 w-3" stroke-width="1.5"/>
                {{ $t('Repeat event') }}
            </div>
            <!-- User-Icons -->
            <div class="-ml-3 mb-0.5 w-full" v-if="$page.props.user.calendar_settings.project_management && event.projectLeaders?.length > 0">
                <div v-if="event.projectLeaders && !project && zoomFactor >= 0.8"
                     class="mt-1 ml-5 flex flex-wrap">
                    <div class="flex flex-wrap flex-row -ml-1.5"
                         v-for="user in event.projectLeaders?.slice(0,3)">
                        <img :src="user.profile_photo_url" alt=""
                             class="mx-auto shrink-0 flex object-cover rounded-full"
                             :class="['h-' + 5 * zoomFactor, 'w-' + 5 * zoomFactor]">
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
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
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
        <div v-if="$page.props.user.calendar_settings.work_shifts" class="ml-1 pb-1 text-xs">
            <div v-for="shift in event.shifts">
                <span>{{ shift.craft.abbreviation }}</span>
                <span>
                    &nbsp;({{ this.getCurrentShiftWorkerCount(shift) }}/{{ this.getMaxShiftWorkerCount(shift) }})
                </span>
            </div>
        </div>
    </div>
    <div v-show="event.subEvents?.length > 0">
        <div v-for="subEvent in event.subEvents" class="mb-1">
            <div class="w-full relative group rounded-lg border-l-[6px] border-[#A7A6B115]">
                <div
                    class="bg-indigo-500/50 hidden absolute w-full h-full rounded-lg group-hover:block flex justify-center align-middle items-center">
                    <div class="flex justify-center items-center h-full gap-2">
                        <button @click="editSubEvent(subEvent)" type="button"
                                class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <IconEdit class="h-4 w-4" stroke-width="1.5"/>
                        </button>
                        <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()"
                                @click="openConfirmModal(subEvent.id, 'sub')" type="button"
                                class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <IconTrash stroke-width="1.5"
                                       stroke="currentColor" class="w-4 h-4"/>
                        </button>
                    </div>
                </div>
                <div :class="[subEvent.class]"
                     :style="{ width: width + 'px', height: (totalHeight - heightSubtraction(subEvent)) * zoomFactor + 'px' }"
                     class="px-1 py-0.5 rounded-lg overflow-y-auto">
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                         :class="[zoomFactor === 1 ? 'eventHeader' : '', 'font-bold']"
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
                            <IconUsersGroup stroke-width="1.5" :width="22 * zoomFactor" :height="11 * zoomFactor"/>
                        </div>
                    </div>
                    <!-- Time -->
                    <div :style="{lineHeight: lineHeight,fontSize: fontSize}"
                         :class="[zoomFactor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']"
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
                    <div v-if="$page.props.user.calendar_settings.work_shifts" class="ml-0.5 text-xs">
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
    <AddSubEventModal
        v-if="showAddSubEventModal"
        @close="closeAddSubModal"
        :event="event"
        :event-types="eventTypes"
        :sub-event-to-edit="subEventToEdit"
    />
    <ConfirmDeleteModal
        v-if="deleteComponentVisible"
        :title="deleteTitle"
        :description="deleteDescription"
        @closed="closeConfirmModal"
        @delete="deleteEvent"
    />
    <DeclineEventModal
        :request-to-decline="event"
        :event-types="eventTypes"
        @closed="showDeclineEventModal = false"
        v-if="showDeclineEventModal"
    />

</template>

<script>
import Button from "@/Jetstream/Button.vue";
import {PlusCircleIcon} from '@heroicons/vue/outline'
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import AddSubEventModal from "@/Layouts/Components/AddSubEventModal.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmEventRequestComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import {Link} from "@inertiajs/vue3";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import VueMathjax from "vue-mathjax-next";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "SingleCalendarEvent",
    components: {
        VueMathjax,
        DeclineEventModal,
        EventComponent,
        ConfirmDeleteModal,
        ConfirmationComponent,
        Menu, MenuItem, MenuItems, MenuButton, UserTooltip, Button, PlusCircleIcon, AddSubEventModal, NewUserToolTip,
        Link
    },
    props: [
        'event',
        'eventTypes',
        'height',
        'width',
        'zoomFactor',
        'fullHeight',
        'project',
        'multiEdit',
        'atAGlance',
        'rooms',
        "checkedEvents",
        'first_project_tab_id'
    ],
    emits: ['openEditEventModal', 'checkEvent'],
    computed: {
        backgroundColorWithOpacity() {
            const color = this.event.event_type_color;
            return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, 15%)`;
        },
        TextColorWithDarken() {
            const color = this.event.event_type_color;
            return `rgb(${parseInt(color.slice(-6, -4), 16) - 75}, ${parseInt(color.slice(-4, -2), 16) - 75}, ${parseInt(color.slice(-2), 16) - 75})`;
        },
        fontSize() {
            return `${this.zoomFactor * 0.75}rem`;

        },
        lineHeight() {
            return `${this.zoomFactor * 1}rem`;

        },
        totalHeight() {
            let height = 42;
            // ProjectStatus is in same row as name -> no extra height needed
            if (this.$page.props.user.calendar_settings.project_status) height += 0;
            //Options are in same row as time -> no extra height needed
            if (this.$page.props.user.calendar_settings.options) height += 0;
            if (this.$page.props.user.calendar_settings.project_management) height += 17;
            if (this.$page.props.user.calendar_settings.repeating_events) height += 20;
            if (this.$page.props.user.calendar_settings.work_shifts) height += 18;
            return height;
        },
        isRoomAdmin() {
            return this.rooms?.find(room => room.id === this.event.roomId)?.admins.some(admin => admin.id === this.$page.props.user.id) || false;
        },
        isCreator() {
            return this.event?.created_by?.id === this.$page.props.user.id
        },
        roomCanBeBookedByEveryone() {
            return this.rooms?.find(room => room.id === this.event.roomId).everyone_can_book
        }

    },
    data() {
        return {
            showDeclineEventModal: false,
            showAddSubEventModal: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            type: '',
            deleteTitle: '',
            deleteDescription: '',
            createEventComponentIsVisible: false,
            selectedEvent: null,
            wantedSplit: null,
            subEventToEdit: null
        }
    },
    methods: {
        getCurrentShiftWorkerCount(shift) {
            return shift.users.length + shift.freelancer.length + shift.service_provider.length;
        },
        getMaxShiftWorkerCount(shift) {
            let shiftWorkerCountByShiftsQualifications = 0;

            shift.shifts_qualifications.forEach((shiftsQualification) => {
                shiftWorkerCountByShiftsQualifications += shiftsQualification.value;
            });

            return shiftWorkerCountByShiftsQualifications;
        },
        gcd(a, b) {
            return (b) ? this.gcd(b, a % b) : a;
        },
        // calculates if there is unneeded height for each event
        heightSubtraction(event) {
            let heightSubtraction = 0;
            if (this.$page.props.user.calendar_settings.project_management && (!event.projectLeaders || event.projectLeaders?.length < 1)) {
                heightSubtraction += 17;
            }
            if (this.$page.props.user.calendar_settings.repeating_events && (!event.is_series || event.is_series === false)) {
                heightSubtraction += 20;
            }
            if (this.$page.props.user.calendar_settings.work_shifts && (!event.shifts || event.shifts?.length < 1)) {
                heightSubtraction += 18;
            }
            return heightSubtraction;
        },
        decimalToFraction(decimal) {
            let wholePart = Math.floor(decimal);
            decimal = decimal - wholePart;

            if (decimal === parseInt(decimal)) {
                if (decimal < 1) {
                    return `${wholePart}`;
                }
                return `${parseInt(decimal)}/1`;
            } else {
                let precision = this.getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000; // The desired precision for the fraction
                let top = Math.round(decimal * precision);
                let bottom = precision;

                let x = this.gcd(top, bottom);
                return `${wholePart} ${top / x}/${bottom / x}`;
            }
        },
        getFirstDigitAfterDecimal(number) {
            const decimalPart = number.toString().split('.')[1];
            if (decimalPart && decimalPart.length > 0) {
                return parseInt(decimalPart[0]);
            }
            return null; // Return null if there is no decimal part
        },
        convertToMathJax(fraction) {
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
        },
        openEditEventModal(event) {
            this.$emit('openEditEventModal', event);
        },
        closeAddSubModal() {
            this.showAddSubEventModal = false;
        },
        openAddSubEventModal() {
            this.subEventToEdit = null;
            this.showAddSubEventModal = true;
        },
        closeConfirmModal() {
            this.deleteComponentVisible = false;
        },
        openConfirmModal(eventId, type) {
            if (type === 'main') {
                this.type = type;
                this.deleteTitle = this.$t('Delete event?');
                this.deleteDescription = this.$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.');
            } else {
                this.type = type;
                this.deleteTitle = this.$t('Delete sub-event?');
                this.deleteDescription = this.$t('Are you sure you want to delete the selected assignments?');
            }
            this.eventToDelete = eventId
            this.deleteComponentVisible = true;
        },
        deleteEvent() {
            if (this.type === 'main') {
                this.$inertia.delete(route('events.delete', this.eventToDelete), {
                    preserveScroll: true,
                    preserveState: true
                })
            }
            if (this.type === 'sub') {
                this.$inertia.delete(route('subEvent.delete', this.eventToDelete), {
                    preserveScroll: true,
                    preserveState: true
                })
            }
            this.deleteComponentVisible = false;
        },
        editSubEvent(subEvent) {
            this.subEventToEdit = subEvent;
            this.showAddSubEventModal = true;
        },
        getEditHref(projectId) {
            return route('projects.tab', {project: projectId, projectTab: this.first_project_tab_id});
        },
    },
    watch: {
        multiEdit: {
            handler() {
                if (!this.multiEdit) {
                    this.event.clicked = false;
                }
            }
        },
        checkedEvents: {
            handler() {
                if (this.checkedEvents.includes(this.event.id)) {
                    this.event.clicked = true;
                }
                if (!this.checkedEvents.includes(this.event.id)) {
                    this.event.clicked = false;
                }
            },
            deep: true
        }
    }
}
</script>

<style scoped>

.occupancy_option_eventType0 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #d2d2d2 8px);
}

.occupancy_option_eventType1 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e0abd1 8px);
}

.occupancy_option_eventType2 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #f6b9d4 8px);
}

.occupancy_option_eventType3 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, rgb(227, 185, 162) 8px);
}

.occupancy_option_eventType4 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e5c386 8px);
}

.occupancy_option_eventType5 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #cce5ac 8px);
}

.occupancy_option_eventType6 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #b0e8c5 8px);
}

.occupancy_option_eventType7 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a9dde1 8px);
}

.occupancy_option_eventType8 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a0cfe5 8px);
}

.occupancy_option_eventType9 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a6dcda 8px);
}

.occupancy_option_eventType10 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a2cbe0 8px);
}

.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
