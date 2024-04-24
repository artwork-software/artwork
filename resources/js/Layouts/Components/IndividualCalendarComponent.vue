<template>
    <div id="myCalendar" class="bg-white min-w-[98%] w-[98%]" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="w-full flex flex-wrap bg-secondaryHover ml-14">
            <div class="flex justify-center w-full bg-white">
                <div class="mt-4 flex errorText items-center cursor-pointer mb-2"
                     @click="openEventsWithoutRoomComponent()"
                     v-if="filteredEvents?.length > 0">
                    <IconAlertTriangle class="h-6  mr-2"/>
                    {{ filteredEvents?.length === 1 ? $t('{0} Event without room!', [filteredEvents?.length]) : $t('{0} Events without room!', [filteredEvents?.length]) }}
                </div>
            </div>


            <CalendarFunctionBar :project="project" @open-event-component="openEditEventModal"
                                 @increment-zoom-factor="incrementZoomFactor"
                                 @decrement-zoom-factor="decrementZoomFactor" :zoom-factor="zoomFactor"
                                 :is-fullscreen="isFullscreen" @enterFullscreenMode="openFullscreen"
                                 :dateValue="dateValue"
                                 @change-at-a-glance="changeAtAGlance"
                                 @change-multi-edit="changeMultiEdit"
                                 @previousTimeRange="previousTimeRange"
                                 @next-time-range="nextTimeRange"
                                 :at-a-glance="atAGlance"
                                 :filter-options="filterOptions"
                                 :personal-filters="personalFilters"
                                 :user_filters="user_filters"
            />
            <div :class="this.project ? 'bg-lightBackgroundGray' : 'bg-white'">
                <!-- Calendar -->
                <table class="w-full bg-white relative">
                    <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                    <div class="bg-secondaryHover">
                        <tr class="flex w-full bg-userBg stickyHeader" :class="{'rounded-t-full': !isPageScrolled, 'mb-2' : !project}">
                            <th :style="{minWidth: zoomFactor === 0.2 ? 40 + 'px' : zoomFactor * 80 + 'px'}">
                            </th>
                            <th v-for="room in rooms" :style="{ minWidth: zoomFactor * 212 + 'px',maxWidth: zoomFactor * 212 + 'px'}" class="py-3  border-r-4 border-secondaryHover truncate mx-2">
                                <Link :style="textStyle" class="flex font-semibold items-center ml-4"
                                      :href="route('rooms.show',{room: room.id})">
                                    {{ room.name }}
                                </Link>
                            </th>
                        </tr>
                        <tbody class="w-full pt-3 ">
                        <tr :style="{height: zoomFactor * 115 + 'px'}" class="w-full flex "
                            :class="day.is_weekend ? 'bg-backgroundGray' : 'bg-white'" v-for="day in days">
                            <th :style="{height: zoomFactor * 115 + 'px',width: zoomFactor === 0.2 ? 40 + 'px' : zoomFactor * 80 + 'px'}"
                                :class="isDashboard || isFullscreen? 'stickyDaysNoMarginLeft bg-userBg' : 'stickyDays'"
                                class="text-secondary text-right pr-1">
                                <div :style="textStyle" class="mt-3">
                                    {{ zoomFactor >= 0.8 ? day.day_string : '' }} {{ zoomFactor >= 0.8 ? day.full_day : day.short_day }} <span v-if="day.is_monday" class="text-[10px] font-normal ml-2">(KW{{ day.week_number }})</span>
                                </div>

                            </th>

                            <td :style="{ width: zoomFactor * 212 + 'px', height: zoomFactor * 115 + 'px'}"
                                class="border-t-2 border-dashed"
                                :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white', zoomFactor > 0.4 ? 'cell' : 'overflow-hidden']"
                                v-for="room in calendarData">
                                <div class="py-0.5" v-for="event in room[day.full_day].events.data ?? room[day.full_day].events">

                                    <SingleCalendarEvent
                                        class="relative"
                                        :project="project ? project : false"
                                        :multiEdit="multiEdit"
                                        :zoom-factor="zoomFactor"
                                        :width="zoomFactor * 204"
                                        :event="event"
                                        :rooms="rooms"
                                        :event-types="eventTypes"
                                        :checked-events="checkedEvents"
                                        @open-edit-event-modal="openEditEventModal"
                                        @check-event="updateCheckedEvents"
                                        :first_project_tab_id="this.first_project_tab_id"
                                    />
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </div>
                </table>
            </div>
            <event-component
                v-if="createEventComponentIsVisible"
                @closed="onEventComponentClose()"
                :showHints="this.$page.props.show_hints"
                :eventTypes="eventTypes"
                :rooms="rooms"
                :project="project"
                :event="selectedEvent"
                :wantedRoomId="wantedRoom"
                :isAdmin="this.hasAdminRole()"
                :roomCollisions="roomCollisions"
                :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
            />

        </div>
        <!-- Termine ohne Raum Modal -->
        <events-without-room-component
            v-if="showEventsWithoutRoomComponent"
            @closed="onEventsWithoutRoomComponentClose()"
            :showHints="this.$page.props.show_hints"
            :eventTypes="eventTypes"
            :rooms="rooms"
            :eventsWithoutRoom="this.filteredEvents"
            :isAdmin="this.hasAdminRole()"
            :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
        />

        <div v-show="multiEdit"
             class="fixed z-30 w-full bg-white/70 bottom-0 h-20 shadow border-t border-gray-100 flex items-center justify-center gap-4">
            <FormButton :text="$t('Move events')"
                       @click="openMultiEditModal"/>
            <FormButton @click="openDeleteSelectedEventsModal = true"
                       class="!border-2 !border-buttonBlue bg-transparent !text-buttonBlue hover:!text-white hover:!bg-buttonHover !hover:border-transparent resize-none"
                       :text="$t('Delete events')"/>
        </div>

        <MultiEditModal :checked-events="editEvents" v-if="showMultiEditModal" :rooms="rooms"
                        @closed="closeMultiEditModal"/>

        <ConfirmDeleteModal
            v-if="openDeleteSelectedEventsModal"
            @closed="openDeleteSelectedEventsModal = false"
            @delete="deleteSelectedEvents"
            :title="$t('Delete assignments')"
            :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"/>

    </div>
</template>

<script>
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";
import {ExclamationIcon} from "@heroicons/vue/outline";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {Inertia} from "@inertiajs/inertia";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import CalendarEventTooltip from "@/Layouts/Components/CalendarEventTooltip.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {Link} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";


export default {
    name: "IndividualCalendarComponent",
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        Link,
        ConfirmDeleteModal,
        CalendarEventTooltip,
        MultiEditModal,
        CalendarFunctionBar,
        SingleCalendarEvent,
        IndividualCalendarFilterComponent,
        EventsWithoutRoomComponent,
        ExclamationIcon,
        EventComponent
    },
    data() {
        return {
            showEventsWithoutRoomComponent: false,
            selectedEvent: null,
            createEventComponentIsVisible: false,
            wantedRoom: null,
            roomCollisions: [],
            isFullscreen: false,
            zoomFactor: this.$page.props.user.zoom_factor ? this.$page.props.user.zoom_factor : 1,
            multiEdit: false,
            editEvents: [],
            showMultiEditModal: false,
            openDeleteSelectedEventsModal: false,
            checkedEvents: [],
            isPageScrolled: false,
        }
    },
    props: [
        'calendarData',
        'rooms',
        'days',
        'atAGlance',
        'eventTypes',
        'dateValue',
        'project',
        'eventsWithoutRoom',
        'isDashboard',
        'filterOptions',
        'personalFilters',
        'user_filters',
        'first_project_tab_id',
        'first_project_calendar_tab_id'
    ],
    emits: ['changeAtAGlance'],
    mounted() {
        window.addEventListener('resize', this.listenToFullscreen);
        this.handleScroll();
        window.addEventListener('scroll', this.handleScroll);
    },
    computed: {
        textStyle() {
            const fontSize = `max(calc(${this.zoomFactor} * 0.875rem), 12px)`;
            const lineHeight = `max(calc(${this.zoomFactor} * 1.25rem), 1.3)`;
            return {
                fontSize,
                lineHeight,
            };
        },
        filteredEvents() {
            return this.eventsWithoutRoom?.filter((event) => {
                let createdBy = event.created_by;
                let projectLeaders = event.projectLeaders;

                if (projectLeaders && projectLeaders.length > 0) {
                    if (createdBy.id === this.$page.props.user.id || projectLeaders.some((leader) => leader.id === this.$page.props.user.id)) {
                        return true;
                    }
                } else if (createdBy.id === this.$page.props.user.id) {
                    return true;
                }

                return false;
            });
        }
    },
    beforeDestroy() {
        window.removeEventListener('scroll', this.handleScroll);
    },
    methods: {
        updateCheckedEvents(event) {
            if(!this.checkedEvents.includes(event.id))
                this.checkedEvents.push(event.id);
            else
                this.checkedEvents = this.checkedEvents.filter((id) => id !== event.id);
        },
        changeMultiEdit(multiEdit) {
            this.multiEdit = multiEdit;
        },
        changeAtAGlance(atAGlance) {
            this.$emit('changeAtAGlance', atAGlance)
        },
        handleScroll() {
            this.isPageScrolled = window.scrollY > 70;
        },
        openEditEventModal(event = null) {

            this.wantedRoom = event?.roomId;

            if (event === null) {
                this.selectedEvent = null;
                if (this.isFullscreen) {
                    document.exitFullscreen();
                    this.isFullscreen = false;
                }
                this.createEventComponentIsVisible = true;
                return;
            }

            if (!event.id) {
                event = {
                    start: event?.start,
                    end: event?.end,
                    projectId: this.project?.id,
                    projectName: this.project?.name,
                    roomId: event.roomId,
                }
            }


            if (event?.start && event?.end) {
                axios.post('/collision/room', {
                    params: {
                        start: event?.start,
                        end: event?.end,
                    }
                }).then(response => this.roomCollisions = response.data);
            }
            this.selectedEvent = event;
            if (this.isFullscreen) {
                document.exitFullscreen();
                this.isFullscreen = false;
            }
            this.createEventComponentIsVisible = true;

        },
        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            Inertia.reload();
        },
        deleteSelectedEvents() {
            this.getCheckedEvents();
            Inertia.post(route('multi-edit.delete'), {
                events: this.editEvents
            }, {
                onSuccess: () => {
                    this.openDeleteSelectedEventsModal = false
                }
            })
        },
        openMultiEditModal() {
            this.getCheckedEvents();

            this.showMultiEditModal = true;
        },
        getCheckedEvents() {
            this.editEvents = [];
            const eventArray = [];
            this.days.forEach((day) => {
                this.calendarData.forEach((room) => {
                    room[day.full_day].events.data.forEach((event) => {
                        if (event.clicked) {
                            if (!eventArray.includes(event.id)) {
                                eventArray.push(event.id)
                            }
                        }
                    })
                })
            })
            this.editEvents = eventArray
        },
        closeMultiEditModal() {
            this.showMultiEditModal = false;
        },
        /* View in fullscreen */
        openFullscreen() {
            let elem = document.getElementById('myCalendar');
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
                this.isFullscreen = true;
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        },
        listenToFullscreen() {
            if (window.innerHeight === screen.height) {
                this.isFullscreen = true;
            } else {
                this.isFullscreen = false;
                this.zoomFactor = 1;
            }
        },
        incrementZoomFactor() {
            if (this.zoomFactor < 1.4) {
                this.zoomFactor = Math.round((this.zoomFactor + 0.2) * 10) / 10;
                this.updateZoomFactorInUser();
            }
        },
        decrementZoomFactor() {
            if (this.zoomFactor > 0.4) {
                this.zoomFactor = Math.round((this.zoomFactor - 0.2) * 10) / 10;
                this.updateZoomFactorInUser();
            }

        },
        updateZoomFactorInUser(){
            this.$inertia.patch(route('user.update.zoom_factor', {user : this.$page.props.user.id}), {
                zoom_factor: this.zoomFactor
            }, {
                preserveScroll: true
            })
        },
        openEventsWithoutRoomComponent() {
            this.showEventsWithoutRoomComponent = true;
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
            Inertia.reload();
        },
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        previousTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[1] = this.getPreviousDay(this.dateValue[0]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() - dayDifference);
            this.dateValue[0] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        nextTimeRange() {
            const dayDifference = this.calculateDateDifference();
            this.dateValue[0] = this.getNextDay(this.dateValue[1]);
            const newDate = new Date(this.dateValue[1]);
            newDate.setDate(newDate.getDate() + dayDifference + 1);
            this.dateValue[1] = newDate.toISOString().slice(0, 10);
            this.updateTimes();
        },
        getNextDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getPreviousDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() - 1);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        updateTimes() {
            Inertia.patch(route('update.user.calendar.filter.dates', this.$page.props.user.id), {
                start_date:  this.dateValue[0],
                end_date: this.dateValue[1],
            },{
                preserveScroll: true
            })
        },
    }
}
</script>

<style scoped>

/* this only works in some browsers but is wanted by the client */
.cell {
    overflow: overlay;
}

::-webkit-scrollbar {
    width: 16px;
}

::-webkit-scrollbar-track {
    background-color: transparent;
}

::-webkit-scrollbar-thumb {
    background-color: #A7A6B170;
    border-radius: 16px;
    border: 6px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #a8bbbf;
}
.stickyHeader {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    display: block;
    top: 56px;
    z-index: 30;
    background-color: #EDEDEC;
}

.stickyDays {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 60px;
    z-index: 22;
    background-color: #EDEDEC;
}

.stickyDaysNoMarginleft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
    background-color: #EDEDEC;
}

</style>
