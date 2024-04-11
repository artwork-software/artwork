<template>
    <div class="w-full flex flex-col">
        <ShiftHeader>
            <div class="ml-5 bg-white flex-grow">
                <ShiftPlanFunctionBar @previousTimeRange="previousTimeRange"
                                      @next-time-range="nextTimeRange"
                                      :date-value="dateValue"
                                      :all-shifts-committed="true"
                                      :filter-options="filterOptions"
                                      :personal-filters="personalFilters"
                                      :rooms="shiftPlan"
                                      @enterFullscreenMode="openFullscreen"
                                      @openHistoryModal="openHistoryModal"
                                      :user_filters="user_filters"
                />
            </div>

            <pre>

            </pre>

            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div ref="shiftPlan" id="shiftPlan" class="bg-white flex-grow" :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                    <Table>
                        <template #head>
                            <div class="stickyHeader">
                            <TableHead>
                                <th class="z-0" style="width:164px;"></th>
                                <th v-for="day in days" style="width:200px;" class="z-20 h-16 py-3 border-r-4 border-secondaryHover truncate">
                                    <div class="flex calendarRoomHeader font-semibold ml-4 mt-2">
                                        {{ day.day_string }} {{ day.full_day }} <span v-if="day.is_monday" class="text-[10px] font-normal ml-2">(KW{{ day.week_number }})</span>
                                    </div>
                                </th>
                            </TableHead>
                            </div>
                        </template>
                        <template #body>
                            <TableBody>
                                <tr v-for="(room,index) in shiftPlan" class="w-full flex">
                                    <th class="xsDark flex items-center -mt-2 h-28 w-44"
                                        :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <Link class="flex font-semibold items-center ml-4">
                                            {{ room[days[0].full_day].roomName }}
                                        </Link>
                                    </th>
                                    <td v-for="day in days" style="width:200px;" class="max-h-28 overflow-y-auto cell">
                                        <div v-for="event in room[day.full_day].events.data" class="mb-1">
                                            <SingleShiftPlanEvent
                                                v-if="event.shifts.length > 0"
                                                :multiEditMode="multiEditMode"
                                                :user-for-multi-edit="userForMultiEdit"
                                                :highlightMode="highlightMode"
                                                :highlighted-id="idToHighlight"
                                                :highlighted-type="typeToHighlight"
                                                :eventType="this.findEventTypeById(event.eventTypeId)"
                                                :project="this.findProjectById(event.projectId)"
                                                :event="event"
                                                :shift-qualifications="shiftQualifications"
                                                @dropFeedback="showDropFeedback"
                                                :day-string="day"
                                            >
                                            </SingleShiftPlanEvent>
                                        </div>
                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div id="userOverview" class="w-full fixed bottom-0 z-30"  :style="showUserOverview ?{ height: userOverviewHeight - 30 + 'px'} : {height: 20 + 'px'}">
                    <div class="flex justify-center overflow-y-scroll">
                        <div v-if="this.$can('can plan shifts') || this.hasAdminRole()" @click="showCloseUserOverview" :class="showUserOverview ? '' : 'fixed bottom-0 '"
                             class="flex h-5 w-8 justify-center items-center cursor-pointer bg-primary">
                            <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14.123" height="6.519"
                                     viewBox="0 0 14.123 6.519">
                                    <g id="Gruppe_1608" data-name="Gruppe 1608"
                                       transform="translate(-275.125 870.166) rotate(-90)">
                                        <path id="Pfad_1313" data-name="Pfad 1313" d="M0,0,6.814,3.882,13.628,0"
                                              transform="translate(865.708 289) rotate(-90)" fill="none" stroke="#a7a6b1"
                                              stroke-width="1"/>
                                        <path id="Pfad_1314" data-name="Pfad 1314" d="M0,0,4.4,2.509,8.809,0"
                                              transform="translate(864.081 286.591) rotate(-90)" fill="none"
                                              stroke="#a7a6b1" stroke-width="1"/>
                                    </g>
                                </svg>
                            </div>
                        </div>
                        <div v-if="showUserOverview" @mousedown="startResize" :class="showUserOverview ? '' : 'fixed bottom-0 '"
                             class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-primary"
                            :title="$t('Hold and drag to change the size')">
                            <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                                <SelectorIcon class="h-3 w-6 text-gray-400" />
                            </div>
                        </div>
                    </div>
                <div v-show="showUserOverview"
                     ref="userOverview"
                     class="w-full bg-primary overflow-x-scroll z-30 overflow-y-scroll"
                     :style="showUserOverview ? { height: userOverviewHeight + 'px'} : {height: 20 + 'px'}">
                    <table class="w-full text-white overflow-y-scroll">
                        <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                        <div>
                            <tr class="flex w-full py-1">
                                <th class="w-44"></th>
                                <th class="flex items-center pl-2 py-1">
                                    <Switch @click="toggleHighlightMode"
                                            :class="[highlightMode ?
                                        'bg-indigo-500' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                    <span aria-hidden="true"
                                          :class="[highlightMode ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                                    </Switch>
                                    <div :class="[highlightMode ? 'xsLight text-secondaryHover' : 'xsLight','ml-1']">
                                        {{ $t('Highlight layers')}}
                                    </div>
                                </th>
                                <th class="flex items-center pl-2 py-1">
                                    <Switch @click="toggleMultiEditMode"
                                            :class="[multiEditMode ?
                                        'bg-indigo-500' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                    <span aria-hidden="true"
                                          :class="[multiEditMode ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                                    </Switch>
                                    <div :class="[multiEditMode ? 'xsLight text-secondaryHover' : 'xsLight','ml-1']">
                                        {{$t('Multi-Edit')}}
                                    </div>
                                </th>
                            </tr>
                            <tbody class="w-full pt-3" v-for="craft in craftsToDisplay">
                                <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 xsLight flex justify-between pb-1" @click="changeCraftVisibility(craft.id)">
                                    {{craft.name}}
                                    <ChevronDownIcon
                                        :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4 mt-0.5"
                                    />
                                </tr>
                                <tr v-if="!closedCrafts.includes(craft.id)" v-for="(user,index) in craft.users" class="w-full flex">
                                    <th class="stickyYAxisNoMarginLeft flex items-center text-right -mt-2 pr-1"
                                        :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                        <DragElement v-if="!highlightMode && !multiEditMode"
                                                     :item="user.element"
                                                     :expected-hours="user.expectedWorkingHours"
                                                     :planned-hours="user.plannedWorkingHours"
                                                     :type="user.type"
                                        />
                                        <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           :userForMultiEdit="userForMultiEdit"
                                                           :multiEditMode="multiEditMode"
                                                           @addUserToMultiEdit="addUserToMultiEdit"
                                        />
                                        <HighlightUserCell v-else
                                                           :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           @highlightShiftsOfUser="highlightShiftsOfUser"
                                        />
                                    </th>
                                    <td v-for="day in days">
                                        <div :class="highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : ''"
                                            class="w-[12.375rem] h-12 p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer"
                                            @click="openShowUserShiftModal(user, day)">
                                            <span v-for="shift in user.element?.shifts" v-if="!user.vacations?.includes(day.without_format)">
                                                <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                                </span>
                                            </span>
                                            <span v-else class="h-full flex justify-center items-center">
                                                {{ $t('not available')}}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody>
                                <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 xsLight flex justify-between pb-1" @click="changeCraftVisibility('noCraft')">
                                    {{ $t('Without craft assignment')}}
                                    <ChevronDownIcon
                                        :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4 mt-0.5"
                                    />
                                </tr>
                                <tr v-if="!closedCrafts.includes('noCraft')" v-for="(user,index) in usersWithNoCrafts" class="w-full flex">
                                    <th class="stickyYAxisNoMarginLeft flex items-center text-right -mt-2 pr-1" :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                        <DragElement v-if="!highlightMode && !multiEditMode"
                                                     :item="user.element"
                                                     :expected-hours="user.expectedWorkingHours"
                                                     :planned-hours="user.plannedWorkingHours"
                                                     :type="user.type"
                                        />
                                        <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           :userForMultiEdit="userForMultiEdit"
                                                           :multiEditMode="multiEditMode"
                                                           @addUserToMultiEdit="addUserToMultiEdit"
                                        />
                                        <HighlightUserCell v-else
                                                           :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           @highlightShiftsOfUser="highlightShiftsOfUser"/>
                                    </th>
                                    <td v-for="day in days">
                                        <div class="w-[12.375rem] h-12 p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer"  @click="openShowUserShiftModal(user, day)">
                                            <span v-for="shift in user.element?.shifts" v-if="!user.vacations?.includes(day.without_format)">
                                                <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                                </span>
                                            </span>
                                            <span v-else class="h-full flex justify-center items-center">
                                                {{ $t('not available')}}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </div>
                    </table>
                </div>
            </div>
            <show-user-shifts-modal v-if="showUserShifts"
                                    @closed="showUserShifts = false"
                                    :user="userToShow"
                                    :day="dayToShow"
                                    :projects="projects"
            />
            <ShiftHistoryModal v-if="showHistoryModal"
                               :history="history"
                               @closed="showHistoryModal = false"
            />
        </ShiftHeader>
        <div class="fixed bottom-1 w-full z-40" v-if="multiEditMode">
            <div v-show="multiEditFeedback" class="flex items-center justify-center text-red-500 my-2">
                {{ multiEditFeedback }}
            </div>
            <div class="flex items-center justify-center gap-4">
                <div>
                    <button type="button"
                            @click="multiEditMode = false"
                            class="rounded-full bg-gray-100 px-14 py-3 text-sm font-semibold text-gray-500 shadow-sm hover:bg-gray-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ $t('Cancel')}}
                    </button>
                </div>
                <div>
                    <button type="button"
                            @click="initializeMultiEditSave"
                            :disabled="this.userForMultiEdit === null && this.checkedShiftsForMultiEdit.length === 0"
                            :class="[
                                this.userForMultiEdit === null && this.checkedShiftsForMultiEdit.length === 0 ?
                                'bg-gray-600' :
                                'cursor-pointer bg-buttonBlue hover:bg-buttonHover',
                                'rounded-full px-14 py-3 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                            ]">
                        {{ $t('Save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <SideNotification v-if="dropFeedback" type="error" :text="dropFeedback" @close="dropFeedback = null"/>
    <ShiftsQualificationsAssignmentModal v-if="this.showShiftsQualificationsAssignmentModal"
                                         :show="this.showShiftsQualificationsAssignmentModal"
                                         :user="this.userForMultiEdit"
                                         :shifts="this.showShiftsQualificationsAssignmentModalShifts"
                                         @close="this.closeShiftsQualificationsAssignmentModal"
    />
</template>
<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import Permissions from "@/mixins/Permissions.vue";
import {ChevronDownIcon, LightBulbIcon} from "@heroicons/vue/outline";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import {Link} from "@inertiajs/inertia-vue3";
import ShiftPlanUserOverview from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanUserOverview.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {Inertia} from "@inertiajs/inertia";
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import ShiftHistoryModal from "@/Pages/Shifts/Components/ShiftHistoryModal.vue";
import ShowUserShiftsModal from "@/Pages/Shifts/Components/ShowUserShiftsModal.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import HighlightUserCell from "@/Pages/Shifts/Components/HighlightUserCell.vue";
import {Switch} from "@headlessui/vue";
import MultiEditUserCell from "@/Pages/Shifts/Components/MultiEditUserCell.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import Table from "@/Components/Table/Table.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import { SelectorIcon } from "@heroicons/vue/solid";
import ShiftsQualificationsAssignmentModal from "@/Layouts/Components/ShiftPlanComponents/ShiftsQualificationsAssignmentModal.vue";

export default {
    name: "ShiftPlan",
    mixins: [Permissions],
    components: {
        ShiftsQualificationsAssignmentModal,
        TableBody,
        TableHead,
        Table,
        ChevronDownIcon,
        SideNotification,
        MultiEditUserCell,
        Switch,
        DragElement,
        ShowUserShiftsModal,
        ShiftHistoryModal,
        ShiftHeader,
        ShiftTabs,
        ShiftPlanUserOverview,
        Link,
        CalendarFunctionBar,
        SingleCalendarEvent,
        EventComponent,
        SingleShiftPlanEvent,
        LightBulbIcon,
        AppLayout,
        ShiftPlanFunctionBar,
        HighlightUserCell,
        SelectorIcon
    },
    props: [
        'events',
        'projects',
        'rooms',
        'shiftPlan',
        'days',
        'filterOptions',
        'dateValue',
        'personalFilters',
        'selectedDate',
        'eventTypes',
        'history',
        'usersForShifts',
        'freelancersForShifts',
        'serviceProvidersForShifts',
        'user_filters',
        'crafts',
        'shiftQualifications'
    ],
    data() {
        return {
            showUserOverview: this.$can('can plan shifts') || this.hasAdminRole(),
            isFullscreen: false,
            showHistoryModal: false,
            showUserShifts: false,
            userToShow: null,
            dayToShow: null,
            highlightMode: false,
            idToHighlight: null,
            typeToHighlight: null,
            multiEditMode: false,
            checkedShiftsForMultiEdit: [],
            userForMultiEdit: null,
            multiEditFeedback: '',
            dropFeedback: null,
            closedCrafts:[],
            userOverviewHeight: 400,
            startY: 0,
            startHeight: 0,
            windowHeight: window.innerHeight,
            shiftsToHandleOnMultiEdit: {
                assignToShift: [],
                removeFromShift: []
            },
            showShiftsQualificationsAssignmentModal: false,
            showShiftsQualificationsAssignmentModalShifts: [],
            shiftsAreChecked: [],
            shiftsToRemoveCheckState: [],
        }
    },
    mounted() {
        // Listen for scroll events on both sections
        this.$refs.shiftPlan.addEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.addEventListener('scroll', this.syncScrollUserOverview);
        window.addEventListener('resize', this.updateHeight);
        this.updateHeight();
    },
    computed: {
        dropUsers() {
            const users = [];
            this.usersForShifts.forEach((user) => {
                users.push({
                    element: user.user,
                    type: 0,
                    plannedWorkingHours: user.plannedWorkingHours,
                    expectedWorkingHours: user.expectedWorkingHours,
                    vacations: user.vacations,
                    assigned_craft_ids: user.user.assigned_craft_ids,
                    availabilities: user.availabilities
                })
            })
            this.freelancersForShifts.forEach((freelancer) => {
                users.push({
                    element: freelancer.freelancer,
                    type: 1,
                    plannedWorkingHours: freelancer.plannedWorkingHours,
                    vacations: freelancer.vacations,
                    assigned_craft_ids: freelancer.freelancer.assigned_craft_ids,
                    availabilities: freelancer.availabilities
                })
            })
            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    plannedWorkingHours: service_provider.plannedWorkingHours,
                    assigned_craft_ids: service_provider.service_provider.assigned_craft_ids
                })
            })
            return users;
        },
        craftsToDisplay() {
            const users = this.dropUsers;
            return this.crafts.map(craft => ({
                name: craft.name,
                id: craft.id,
                users: users.filter(user => user.assigned_craft_ids?.includes(craft.id))
            }));
        },
        usersWithNoCrafts() {
            return this.dropUsers.filter(user =>
                !user.assigned_craft_ids || user.assigned_craft_ids?.length === 0
            );
        },
    },
    methods: {
        showDropFeedback(feedback) {
            this.dropFeedback = feedback;
            setTimeout(() => {
                this.dropFeedback = null
            }, 2000)
        },
        findProjectById(projectId) {
            return this.projects.find(project => project.id === projectId);
        },
        findEventTypeById(eventTypeId) {
            return this.eventTypes.find(eventType => eventType.id === eventTypeId);
        },
        openFullscreen() {
            let elem = document.getElementById('shiftPlan');
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
                this.isFullscreen = true;
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
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
        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
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
            Inertia.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.user.id), {
                start_date: this.dateValue[0],
                end_date: this.dateValue[1],
            }, {
                preserveScroll: true,
                preserveState: false
            });
        },
        openHistoryModal() {
            this.showHistoryModal = true;
        },
        showCloseUserOverview() {
            this.showUserOverview = !this.showUserOverview
            //this.$emit('isOpen', this.showUserOverview)
        },
        syncScrollShiftPlan(event) {
            if (this.$refs.userOverview) {
                // Synchronize horizontal scrolling from shiftPlan to userOverview
                this.$refs.userOverview.scrollLeft = event.target.scrollLeft;
            }
        },
        syncScrollUserOverview(event) {
            if (this.$refs.shiftPlan) {
                // Synchronize horizontal scrolling from userOverview to shiftPlan
                this.$refs.shiftPlan.scrollLeft = event.target.scrollLeft;
            }
        },
        openShowUserShiftModal(user, day) {
            this.userToShow = user
            this.dayToShow = day
            this.showUserShifts = true
        },
        toggleHighlightMode() {
            this.multiEditMode = false;
            this.highlightMode = !this.highlightMode;
        },
        toggleMultiEditMode() {
            this.highlightMode = false;
            this.multiEditMode = !this.multiEditMode;
        },
        highlightShiftsOfUser(id, type) {
            this.idToHighlight = id;
            this.typeToHighlight = type;
        },
        addUserToMultiEdit(item) {
            if (item === null) {
               this.userForMultiEdit = [];
            }
            this.userForMultiEdit = item;
        },
        initializeMultiEditSave() {
            if (this.userForMultiEdit === null) {
                this.multiEditFeedback = 'Bitte wähle einen Nutzer*in aus!';
                return;
            }

            //check if existing shifts are not contained anymore
            this.userForMultiEdit.shift_ids.forEach((shiftId) => {
                let checkedShift = this.checkedShiftsForMultiEdit.find((checkedShift) => checkedShift.id === shiftId);
                if (typeof checkedShift === 'undefined') {
                    this.shiftsToHandleOnMultiEdit.removeFromShift.push(shiftId);
                }
            });

            // Check if the same layer is in the checkedShiftsForMultiEdit twice, if so delete it until it is only in the checkedShiftsForMultiEdit once
            let shiftIds = this.checkedShiftsForMultiEdit.map(shift => shift.id);
            let shiftIdsUnique = [...new Set(shiftIds)];
            shiftIdsUnique.forEach((shiftId) => {
                let shiftCount = shiftIds.filter(id => id === shiftId).length;
                if (shiftCount > 1) {
                    for (let i = 0; i < shiftCount - 1; i++) {
                        let index = this.checkedShiftsForMultiEdit.findIndex(shift => shift.id === shiftId);
                        this.checkedShiftsForMultiEdit.splice(index, 1);
                    }
                }
            });

            //check if user has any shift qualifications
            if (this.userForMultiEdit.shift_qualifications.length > 0) {
                //iterate checked shifts
                this.checkedShiftsForMultiEdit.forEach((checkedShift) => {
                    //if user is not already assigned to shift
                    if (!this.userForMultiEdit.shift_ids.includes(checkedShift.id)) {
                        //if user only has one shift qualification
                        if (this.userForMultiEdit.shift_qualifications.length === 1) {
                            if (
                                !this.hasShiftsQualificationFreeSlots(
                                    checkedShift,
                                    this.userForMultiEdit.shift_qualifications[0].id
                                )
                            ) {
                                //no free slots available for given shift qualification
                                return;
                            }
                            this.shiftsToHandleOnMultiEdit.assignToShift.push({
                                shiftId: checkedShift.id,
                                shiftQualificationId: this.userForMultiEdit.shift_qualifications[0].id
                            });
                            return;
                        }
                        //if user has multiple shift qualifications
                        let availableShiftQualificationSlots = [];
                        this.userForMultiEdit.shift_qualifications.forEach((userShiftQualification) => {
                            checkedShift.shifts_qualifications.forEach((shiftsQualification) => {
                                if (
                                    userShiftQualification.id === shiftsQualification.shift_qualification_id &&
                                    this.hasShiftsQualificationFreeSlots(checkedShift, userShiftQualification.id)
                                ) {
                                    availableShiftQualificationSlots.push(userShiftQualification);
                                }
                            });
                        });

                        //when no slots available return
                        if (availableShiftQualificationSlots.length === 0) {
                            return;
                        }

                        //if only one slot is available it will be assigned
                        if (availableShiftQualificationSlots.length === 1) {
                            this.shiftsToHandleOnMultiEdit.assignToShift.push({
                                shiftId: checkedShift.id,
                                shiftQualificationId: availableShiftQualificationSlots[0].id
                            });
                            return;
                        }

                        //multiple slots are available, prepare shift qualification assignment modal
                        this.showShiftsQualificationsAssignmentModalShifts.push({
                            shift: checkedShift,
                            availableSlots: availableShiftQualificationSlots
                        });
                    }
                });
            }

            //open shifts qualifications assignment modal
            if (this.showShiftsQualificationsAssignmentModalShifts.length > 0) {
                this.showShiftsQualificationsAssignmentModal = true;
                return;
            }

            this.saveMultiEdit();
        },
        closeShiftsQualificationsAssignmentModal(closedForAssignment, assignedShifts) {
            this.showShiftsQualificationsAssignmentModal = false;
            this.showShiftsQualificationsAssignmentModalShifts = [];

            if (!closedForAssignment) {
                return;
            }

            assignedShifts.forEach((shiftToBeAssigned) => {
                this.shiftsToHandleOnMultiEdit.assignToShift.push({
                    shiftId: shiftToBeAssigned.shiftId,
                    shiftQualificationId: shiftToBeAssigned.shiftQualificationId
                });
            });

            this.saveMultiEdit();
        },
        saveMultiEdit() {
            if (
                this.shiftsToHandleOnMultiEdit.assignToShift.length === 0 &&
                this.shiftsToHandleOnMultiEdit.removeFromShift.length === 0
            ) {
                this.resetMultiEditMode();
                return;
            }


            Inertia.post(route('shift.multi.edit.save'), {
                userType: this.userForMultiEdit.type,
                userTypeId: this.userForMultiEdit.id,
                shiftsToHandle: this.shiftsToHandleOnMultiEdit
            }, {
                preserveScroll: true,
                preserveState: true,
                onFinish: this.resetMultiEditMode
            });
        },
        resetMultiEditMode() {
            this.multiEditMode = false;
            this.shiftsToHandleOnMultiEdit = {
                assignToShift: [],
                removeFromShift: []
            }
        },
        hasShiftsQualificationFreeSlots(shift, shiftQualificationId) {
            let shiftsQualificationById = shift.shifts_qualifications.find((shiftsQualification) => {
                return shiftsQualification.shift_qualification_id === shiftQualificationId;
            });

            if (shiftsQualificationById.value === null || shiftsQualificationById.value === 0) {
                return false;
            }

            return this.determineUsedShiftsQualificationSlots(
                shift,
                shiftQualificationId
            ) < shiftsQualificationById.value;
        },
        determineUsedShiftsQualificationSlots(shift, shiftQualificationId) {
            let assignedUserCount = 0;

            shift.users.forEach((user) => {
                if (user.pivot.shift_qualification_id === shiftQualificationId) {
                    assignedUserCount++;
                }
            });

            shift.freelancer.forEach((freelancer) => {
                if (freelancer.pivot.shift_qualification_id === shiftQualificationId) {
                    assignedUserCount++;
                }
            });

            shift.service_provider.forEach((serviceProvider) => {
                if (serviceProvider.pivot.shift_qualification_id === shiftQualificationId) {
                    assignedUserCount++;
                }
            });

            return assignedUserCount;
        },
        changeCraftVisibility(id) {
            if (this.closedCrafts.includes(id)) {
                this.closedCrafts.splice(this.closedCrafts.indexOf(id), 1);
            } else {
                this.closedCrafts.push(id);
            }
        },
        startResize(event) {
            event.preventDefault();
            this.startY = event.clientY;
            this.startHeight = this.userOverviewHeight;

            document.addEventListener('mousemove', this.resizing);
            document.addEventListener('mouseup', this.stopResize);
        },
        resizing(event) {
            const currentY = event.clientY;
            const diff = this.startY - currentY;
            if (this.startHeight + diff < 100) {
                this.userOverviewHeight = 100;
                this.updateHeight()
                return;
            }

            if ((window.innerHeight - 200) - (this.startHeight + diff) < 200) {
                this.userOverviewHeight = (window.innerHeight - 200) - 200;
                this.updateHeight()
                return;
            }

            this.userOverviewHeight = this.startHeight + diff;
            this.updateHeight()
        },
        stopResize(event) {
            event.preventDefault();
            document.removeEventListener('mousemove', this.resizing);
            document.removeEventListener('mouseup', this.stopResize);
        },
        updateHeight() {
            if(!this.showUserOverview){
                this.windowHeight = (window.innerHeight - 250);
            } else {
                this.windowHeight = (window.innerHeight - 200) - this.userOverviewHeight;
            }

            if (window.innerHeight - 200 < 400) {
                this.userOverviewHeight = window.innerHeight - 300;
            }

            // check if userOverviewHeight is not smaller than 100
            if (this.userOverviewHeight < 100) {
                this.userOverviewHeight = 100;
            }
        },
        setShiftsCheckState(shiftId, state) {
            // Durchläuft den shiftPlan und aktualisiert den isCheckedForMultiEdit Status
            this.shiftPlan.forEach(room => {
                this.days.forEach(day => {
                    room[day.full_day].events.data.forEach(event => {
                        event.shifts.forEach(shift => {
                            if (shift.id === shiftId) {
                                shift.isCheckedForMultiEdit = state;
                                if(!state){
                                   // remove shift form checkedShiftsForMultiEdit
                                    const index = this.checkedShiftsForMultiEdit.findIndex(shift => shift.id === shiftId);
                                    if (index !== -1) {
                                        this.checkedShiftsForMultiEdit.splice(index, 1);
                                    }
                                } else {
                                    this.checkedShiftsForMultiEdit.push(shift);
                                }
                            }
                        });
                    });
                });
            });
        }
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.updateHeight);
        document.removeEventListener('mousemove', this.resizing);
        document.removeEventListener('mouseup', this.stopResize);
    },
    beforeDestroy() {
        this.$refs.shiftPlan.removeEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.removeEventListener('scroll', this.syncScrollUserOverview);
    },
    watch: {
        showUserOverview: {
            handler() {
                this.updateHeight();
            },
            deep: true
        },
        shiftPlan: {
            handler(newShiftPlan) {
                // Erstelle eine Kopie von shiftsAreChecked, um zu bestimmen, welche entfernt wurden
                let currentCheckedIds = [...this.shiftsAreChecked];

                // Durchlaufe den neuen shiftPlan, um Änderungen zu identifizieren
                newShiftPlan.forEach(room => {
                    this.days.forEach(day => {
                        room[day.full_day].events.data.forEach(event => {
                            event.shifts.forEach(shift => {
                                const index = currentCheckedIds.indexOf(shift.id);
                                if (shift.isCheckedForMultiEdit) {
                                    // Füge hinzu, wenn nicht vorhanden
                                    if (index === -1) {
                                        this.shiftsAreChecked.push(shift.id);
                                        this.setShiftsCheckState(shift.id, true);
                                    }
                                } else if (index !== -1) {
                                    // Entferne die ID und setze alle Schichten mit dieser ID auf false
                                    this.shiftsAreChecked.splice(index, 1);
                                    this.setShiftsCheckState(shift.id, false);
                                }
                            });
                        });
                    });
                });
            },
            deep: true
        }
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
    top: 0px;
}

.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 60px;
    z-index: 22;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}
</style>
