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
                                      :crafts="crafts"
                                      @select-go-to-next-mode="selectGoToNextMode"
                                        @select-go-to-previous-mode="selectGoToPreviousMode"
                />
            </div>
            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div ref="shiftPlan" id="shiftPlan" class="bg-white flex-grow" :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                    <Table>
                        <template #head>
                            <div class="stickyHeader">
                            <TableHead id="stickyTableHead" ref="stickyTableHead">
                                <th class="z-0" style="width:192px;"></th>
                                <th  v-for="day in days" :style="{width: day.week_separator ? '40px' : '200px'}" :id="day.full_day" class="z-20 h-16 py-3 border-r-4 border-secondaryHover truncate">
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
                                    <th class="xsDark flex items-center -mt-2 h-28 w-48"
                                        :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <Link class="flex font-semibold items-center ml-4">
                                            {{ room[days[0].full_day].roomName }}
                                        </Link>
                                    </th>
                                    <td v-for="day in days" :style="{width: day.week_separator ? '40px' : '200px'}" class="max-h-28 overflow-y-auto cell border-r-2 border-dotted" :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white']">
                                        <div v-for="event in room[day.full_day]?.events.data" class="mb-1">
                                            <SingleShiftPlanEvent
                                                v-if="checkIfEventHasShiftsToDisplay(event)"
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
                                            />
                                            <SingleEventInShiftPlan v-else :event="event" :day="day" />
                                        </div>
                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div id="userOverview" class="w-full fixed bottom-0 z-30">
                    <div class="flex justify-center overflow-y-scroll">
                        <div v-if="this.$can('can plan shifts') || this.hasAdminRole()" @click="showCloseUserOverview" :class="showUserOverview ? '' : 'fixed bottom-0 '"
                             class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background">
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
                             class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-artwork-navigation-background"
                            :title="$t('Hold and drag to change the size')">
                            <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                                <SelectorIcon class="h-3 w-6 text-gray-400" />
                            </div>
                        </div>
                    </div>
                <div v-show="showUserOverview" ref="userOverview" class="relative w-full bg-artwork-navigation-background overflow-x-scroll z-30 overflow-y-scroll" :style="showUserOverview ? { height: userOverviewHeight + 'px'} : {height: 20 + 'px'}">
                    <div class="flex items-center justify-between w-full fixed py-5 z-50 bg-artwork-navigation-background px-3" :style="{top: calculateTopPositionOfUserOverView}">
                        <div class="flex items-center justify-end gap-x-3">
                            <Switch @click="toggleMultiEditMode" v-model="multiEditMode" :class="[multiEditMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                <span class="sr-only">Use setting</span>
                                <span :class="[multiEditMode ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span :class="[multiEditMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                         <IconPencil stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                      <span :class="[multiEditMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                          <IconPencil stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                </span>
                            </Switch>
                            <div class="flex items-center gap-x-2" v-if="dayServices && selectedDayService">
                                <Switch @click="toggleDayServiceMode" v-model="dayServiceMode" :class="[dayServiceMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span :class="[dayServiceMode ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                        <span :class="[dayServiceMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                            <component :is="selectedDayService?.icon" class="w-5 h-5" :style="{color: selectedDayService?.hex_color}" />
                                        </span>
                                        <span :class="[dayServiceMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                            <component :is="selectedDayService?.icon" class="w-5 h-5" :style="{color: selectedDayService?.hex_color}" />
                                        </span>
                                    </span>
                                </Switch>
                                <DayServiceFilter :current-selected-day-service="selectedDayService" :day-services="dayServices" @update:current-selected-day-service="updateSelectedDayService" />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-x-3 pr-20">
                            <Switch @click="toggleHighlightMode" v-model="highlightMode" :class="[highlightMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                <span class="sr-only">Use setting</span>
                                <span :class="[highlightMode ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span :class="[highlightMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                         <IconBulb stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                      <span :class="[highlightMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                          <IconBulb stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                </span>
                            </Switch>

                            <Switch @click="toggleCompactMode" v-model="$page.props.user.compact_mode" :class="[$page.props.user.compact_mode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                <span class="sr-only">Use setting</span>
                                <span :class="[$page.props.user.compact_mode ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span :class="[$page.props.user.compact_mode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                         <IconList stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                      <span :class="[$page.props.user.compact_mode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                          <IconList stroke-width="1.5" class="w-5 h-5" />
                                      </span>
                                </span>
                            </Switch>
                            <BaseFilter onlyIcon="true" class="text-white">
                                <div class="mx-auto w-full max-w-md rounded-2xl border-none mt-2">
                                    <CraftFilter :crafts="crafts" is_tiny/>
                                </div>
                            </BaseFilter>
                        </div>
                    </div>
                    <div class="pt-16">
                        <table class="w-full text-white overflow-y-scroll">
                            <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                            <div>
                                <tbody class="w-full pt-3" v-for="craft in craftsToDisplay">
                                <tr class="stickyYAxisNoMarginLeft pl-2 cursor-pointer w-48 xsLight flex justify-between pb-1" @click="changeCraftVisibility(craft.id)">
                                    {{craft.name}}
                                    <ChevronDownIcon
                                        :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4 mt-0.5"
                                    />
                                </tr>
                                <tr v-if="!closedCrafts.includes(craft.id)" v-for="(user,index) in craft.users" class="w-full flex">
                                    <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right"
                                        :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                        <DragElement v-if="!highlightMode && !multiEditMode"
                                                     :item="user.element"
                                                     :expected-hours="user.expectedWorkingHours"
                                                     :planned-hours="user.plannedWorkingHours"
                                                     :type="user.type"
                                                     :color="craft.color"
                                        />
                                        <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           :userForMultiEdit="userForMultiEdit"
                                                           :multiEditMode="multiEditMode"
                                                           @addUserToMultiEdit="addUserToMultiEdit"
                                                           :color="craft.color"
                                        />
                                        <HighlightUserCell v-else
                                                           :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           @highlightShiftsOfUser="highlightShiftsOfUser"
                                                           :color="craft.color"
                                        />
                                    </th>
                                    <td v-for="day in days" class="flex gap-x-0.5 relative">
                                        <div :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']" class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer truncate relative overflow-hidden" :style="{width: day.is_sunday ? '158px' : '198px'}"
                                             @click="handleCellClick(user, day)">
                                            <span v-for="shift in user.element?.shifts" v-if="!user.vacations?.includes(day.without_format)">
                                                <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                                </span>
                                            </span>
                                            <span v-else class="h-full flex justify-center items-center text-artwork-messages-error">
                                                {{ $t('not available')}}
                                            </span>
                                            <span v-if="user.availabilities">
                                                <span v-for="availability in user.availabilities[day.full_day]">
                                                    <span class="text-green-500">
                                                        <span v-if="availability.comment">&bdquo;{{ availability.comment }}&rdquo; </span>
                                                    </span>
                                                </span>
                                            </span>

                                        </div>
                                        <div :style="{marginRight: day.is_sunday ? '40px' : '0px'}" v-if="user.dayServices" v-for="(userDayServices, index) in user.dayServices" class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                            <div v-if="index === day.without_format" v-for="(userDayService, position) in userDayServices" class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center" :class="position > 0 ? '-ml-3' : ''">
                                                <component :is="userDayService.icon" class="h-4 w-4" :style="{color: userDayService.hex_color}"/>
                                            </div>
                                        </div>
                                        <div v-if="day.is_sunday" class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden" style="width: 37px" :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']">
                                            <span v-if="user.type === 0">
                                                {{ user?.weeklyWorkingHours[day.week_number] }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                                <tbody>
                                <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 pl-2 xsLight flex justify-between pb-1" @click="changeCraftVisibility('noCraft')">
                                    {{ $t('Without craft assignment')}}
                                    <ChevronDownIcon
                                        :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4 mt-0.5"
                                    />
                                </tr>
                                <tr v-if="!closedCrafts.includes('noCraft')" v-for="(user,index) in usersWithNoCrafts" class="w-full flex">
                                    <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right" :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                        <DragElement v-if="!highlightMode && !multiEditMode"
                                                     :item="user.element"
                                                     :expected-hours="user.expectedWorkingHours"
                                                     :planned-hours="user.plannedWorkingHours"
                                                     :type="user.type"
                                                     :color="null"
                                        />
                                        <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           :userForMultiEdit="userForMultiEdit"
                                                           :multiEditMode="multiEditMode"
                                                           @addUserToMultiEdit="addUserToMultiEdit"
                                                           :color="null"
                                        />
                                        <HighlightUserCell v-else
                                                           :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                           :item="user.element"
                                                           :expected-hours="user.expectedWorkingHours"
                                                           :planned-hours="user.plannedWorkingHours"
                                                           :type="user.type"
                                                           @highlightShiftsOfUser="highlightShiftsOfUser"
                                                           :color="null"/>
                                    </th>
                                    <td v-for="day in days" class="flex gap-x-0.5 relative">
                                        <div class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer"
                                             @click="handleCellClick(user, day)"
                                             :style="{width: day.is_sunday ? '158px' : '198px'}"
                                             :class="$page.props.user.compact_mode ? 'h-8' : 'h-12'">
                                            <span v-for="shift in user.element?.shifts" v-if="!user.vacations?.includes(day.without_format)">
                                                <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                                </span>
                                            </span>
                                            <span v-else class="h-full flex justify-center items-center text-artwork-messages-error">
                                                {{ $t('not available')}}
                                            </span>
                                            <span v-if="user.availabilities">
                                                <span v-for="availability in user.availabilities[day.full_day]">
                                                    <span class="text-green-500">
                                                        <span v-if="availability.comment">&bdquo;{{ availability.comment }}&rdquo; </span>
                                                    </span>
                                                </span>
                                            </span>
                                        </div>
                                        <div :style="{marginRight: day.is_sunday ? '40px' : '0px'}" v-if="user.dayServices" v-for="(userDayServices, index) in user.dayServices" class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                            <div v-if="index === day.without_format" v-for="(userDayService, position) in userDayServices" class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center" :class="position > 0 ? '-ml-3' : ''">
                                                <component :is="userDayService.icon" class="h-4 w-4" :style="{color: userDayService.hex_color}"/>
                                            </div>
                                        </div>
                                        <div v-if="day.is_sunday" class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden" style="width: 37px" :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']">
                                            <span v-if="user.type === 0">
                                                {{ user?.weeklyWorkingHours[day.week_number] }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </div>
                        </table>
                    </div>
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
                            class="rounded-full bg-gray-100 px-14 py-3 text-sm font-semibold text-gray-500 shadow-sm hover:bg-gray-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
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
                                'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create',
                                'rounded-full px-14 py-3 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
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
import Permissions from "@/Mixins/Permissions.vue";
import {ChevronDownIcon, LightBulbIcon} from "@heroicons/vue/outline";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import {Link} from "@inertiajs/vue3";
import ShiftPlanUserOverview from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanUserOverview.vue";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import {router} from "@inertiajs/vue3";
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
import {SelectorIcon} from "@heroicons/vue/solid";
import ShiftsQualificationsAssignmentModal
    from "@/Layouts/Components/ShiftPlanComponents/ShiftsQualificationsAssignmentModal.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {IconChevronDown, IconFileText, IconPencil, IconX} from "@tabler/icons-vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import SingleEventInShiftPlan from "@/Pages/Shifts/Components/SingleEventInShiftPlan.vue";
import IconLib from "@/Mixins/IconLib.vue";
import DayServiceFilter from "@/Components/Filter/DayServiceFilter.vue";

export default {
    name: "ShiftPlan",
    mixins: [Permissions, IconLib],
    components: {
        DayServiceFilter,
        IconPencil,
        SingleEventInShiftPlan,
        CraftFilter,
        IconChevronDown, IconX, IconFileText, BaseFilter,
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
        'shiftQualifications',
        'dayServices'
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
            dayServiceMode: false,
            selectedDayService: this.dayServices ? this.dayServices[0] : null,
            checkedShiftsForMultiEdit: [],
            userForMultiEdit: null,
            multiEditFeedback: '',
            dropFeedback: null,
            closedCrafts:[],
            userOverviewHeight: 515,
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
            firstDayPosition: this.days ? this.days[0].full_day : null,
            currentDayOnView: this.days ? this.days[0] : null
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
                    availabilities: user.availabilities,
                    weeklyWorkingHours: user.weeklyWorkingHours,
                    dayServices: user.dayServices
                })
            })
            this.freelancersForShifts.forEach((freelancer) => {
                users.push({
                    element: freelancer.freelancer,
                    type: 1,
                    plannedWorkingHours: freelancer.plannedWorkingHours,
                    vacations: freelancer.vacations,
                    assigned_craft_ids: freelancer.freelancer.assigned_craft_ids,
                    availabilities: freelancer.availabilities,
                    dayServices: freelancer.dayServices
                })
            })
            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    plannedWorkingHours: service_provider.plannedWorkingHours,
                    assigned_craft_ids: service_provider.service_provider.assigned_craft_ids,
                    dayServices: service_provider.dayServices
                })
            })
            return users;
        },
        craftsToDisplay() {
            const users = this.dropUsers;
            if (this.$page.props.user?.show_crafts?.length === 0 || this.$page.props.user?.show_crafts === null) {
                return this.crafts.map(craft => ({
                    name: craft.name,
                    id: craft.id,
                    users: users.filter(user => user.assigned_craft_ids?.includes(craft.id)),
                    color: craft?.color
                }));
            } else {
                return this.crafts.filter(craft => this.$page.props.user?.show_crafts?.includes(craft.id)).map(craft => ({
                    name: craft.name,
                    id: craft.id,
                    users: users.filter(user => user.assigned_craft_ids?.includes(craft.id)),
                    color: craft?.color
                }));
            }
        },
        usersWithNoCrafts() {
            return this.dropUsers.filter(user =>
                !user.assigned_craft_ids || user.assigned_craft_ids?.length === 0
            );
        },
    },
    methods: {
        handleCellClick(user, day){
            if(this.dayServiceMode ){
                let type = null;
                switch (user.type) {
                    case 0:
                        type = 'user';
                        break;
                    case 1:
                        type = 'freelancer';
                        break;
                    case 2:
                        type = 'service_provider';
                        break;
                }
                const hasDayService = user.dayServices?.[day.without_format]?.some(dayService => dayService.id === this.selectedDayService.id)
                // check if user has allready the selected day service, if yes remove it. the dayServices in User are group by day
                if (hasDayService){
                    router.patch(route('remove.day.service.from.user', {
                        dayServiceable: user.element.id,
                    }), {
                        dayService: this.selectedDayService.id,
                        date: day.without_format,
                        modelType: type
                    }, {
                        preserveScroll: true,
                        preserveState: true
                    });
                } else {
                    router.post(route('day-service.attach', {
                        dayServiceable: user.element.id,
                        dayService: this.selectedDayService.id,
                    }), {
                        date: day.without_format,
                        modelType: type
                    }, {
                        preserveScroll: true,
                        preserveState: true
                    });
                }

            } else {
                this.openShowUserShiftModal(user, day)
            }
        },
        updateSelectedDayService(dayService) {
            this.selectedDayService = dayService;
        },
        calculateTopPositionOfUserOverView() {
            return this.showUserOverview ? this.userOverviewHeight + 'px' : '0';
        },
        checkIfEventHasShiftsToDisplay(event) {
            if(this.$page.props.user?.show_crafts?.length === 0 || this.$page.props.user?.show_crafts === null){
                return event.shifts.length > 0;
            } else {
                return event.shifts.length > 0 && event.shifts.some(shift => this.$page.props.user.show_crafts?.includes(shift.craft.id));
            }
        },
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
            router.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.user.id), {
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

                // update the current day on view with the day that is currently visible check if day.week_separator is false
                // because we don't want to update the currentDayOnView with the week separator
                const firstDay = document.getElementById(this.days[0].full_day)
                const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                const firstDayPosition = scrollableContainer.scrollLeft;
                const scrollPosition = scrollableContainer.scrollLeft;
                const dayIndex = Math.floor(scrollPosition / firstDay.offsetWidth);
                if (!this.days[dayIndex].week_separator) {
                    this.currentDayOnView = this.days[dayIndex];
                } else {
                    this.currentDayOnView = this.days[dayIndex + 1];
                }
            }
        },
        selectGoToNextMode(){
            if (this.$page.props.user.goto_mode === 'day'){
                this.goToDay('next')
            } else if (this.$page.props.user.goto_mode === 'week'){
                this.goToWeek('next')
            } else if (this.$page.props.user.goto_mode === 'month'){
                this.goToMonth('next')
            }
        },
        selectGoToPreviousMode(){
            if (this.$page.props.user.goto_mode === 'day'){
                this.goToDay('previous')
            } else if (this.$page.props.user.goto_mode === 'week'){
                this.goToWeek('previous')
            } else if (this.$page.props.user.goto_mode === 'month'){
                this.goToMonth('previous')
            }
        },
        goToWeek(type = 'next'){
            if (type === 'next') {
                const nextKwDay = this.days.find(day => day.is_monday && day.week_number === this.currentDayOnView.week_number + 1);

                // bring the new kw in the scroll position of the currentDayOnView
                const firstDay = document.getElementById(this.currentDayOnView.full_day);
                const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                scrollableContainer.scrollLeft = firstDay.offsetWidth * this.days.indexOf(nextKwDay);
            } else {
                const previousKwDay = this.days.find(day => day.is_monday && day.week_number === this.currentDayOnView.week_number - 1);

                // bring the new kw in the scroll position of the currentDayOnView
                const firstDay = document.getElementById(this.currentDayOnView.full_day);
                const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                scrollableContainer.scrollLeft = firstDay.offsetWidth * this.days.indexOf(previousKwDay);
            }
        },
        goToDay(type = 'next') {
            if (type === 'next') {
                const nextDay = this.days.find(day => day.full_day === this.currentDayOnView.full_day);
                const nextDayIndex = this.days.indexOf(this.currentDayOnView) + 1;
                if (nextDayIndex < this.days.length) {
                    const nextDay = this.days[nextDayIndex];
                    const firstDay = document.getElementById(this.currentDayOnView.full_day);
                    const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                    scrollableContainer.scrollLeft = firstDay.offsetWidth * nextDayIndex;
                }
            } else {
                const previousDay = this.days.find(day => day.full_day === this.currentDayOnView.full_day);
                const previousDayIndex = this.days.indexOf(this.currentDayOnView) - 1;
                if (previousDayIndex >= 0) {
                    const previousDay = this.days[previousDayIndex];
                    const firstDay = document.getElementById(this.currentDayOnView.full_day);
                    const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                    scrollableContainer.scrollLeft = firstDay.offsetWidth * previousDayIndex;
                }
            }
        },
        goToMonth(type = 'next'){
            if (type === 'next') {
                const nextMonthDay = this.days.find(day => day.is_first_day_of_month && day.month_number === this.currentDayOnView.month_number + 1);

                // bring the new month in the scroll position of the currentDayOnView
                const firstDay = document.getElementById(this.currentDayOnView.full_day);
                const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                scrollableContainer.scrollLeft = firstDay.offsetWidth * this.days.indexOf(nextMonthDay);
            } else {
                const previousMonthDay = this.days.find(day => day.is_first_day_of_month && day.month_number === this.currentDayOnView.month_number - 1);

                // bring the new month in the scroll position of the currentDayOnView
                const firstDay = document.getElementById(this.currentDayOnView.full_day);
                const scrollableContainer = this.$refs.shiftPlan; // Use the shiftPlan reference as the scrollable container
                scrollableContainer.scrollLeft = firstDay.offsetWidth * this.days.indexOf(previousMonthDay);
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
            this.dayServiceMode = false;
            this.highlightMode = !this.highlightMode;
        },
        toggleMultiEditMode() {
            this.highlightMode = false;
            this.dayServiceMode = false;
            this.multiEditMode = !this.multiEditMode;
        },
        toggleDayServiceMode() {
            this.highlightMode = false;
            this.multiEditMode = false;
            this.dayServiceMode = !this.dayServiceMode;
        },
        toggleCompactMode() {
            router.post(route('user.compact.mode.toggle', {user: this.$page.props.user.id}), {
                compact_mode: !this.$page.props.user.compact_mode
            }, {
                preserveScroll: true,
                preserveState: true
            });
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


            router.post(route('shift.multi.edit.save'), {
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

            if ((window.innerHeight - 160) - (this.startHeight + diff) < 160) {
                this.userOverviewHeight = (window.innerHeight - 160) - 200;
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
                this.windowHeight = (window.innerHeight - 160) - this.userOverviewHeight;
            }

            if (window.innerHeight - 160 < 400) {
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
                    room[day.full_day]?.events.data.forEach(event => {
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
                        room[day.full_day]?.events.data.forEach(event => {
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
