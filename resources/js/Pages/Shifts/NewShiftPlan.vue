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
                >
                    <template #multiEditCalendar>
                        <div v-if="multiEditModeCalendar">
                            <div class="flex items-center justify-center gap-x-4">
                                <button type="button"
                                        @click="initializeCalendarMultiEditSave"
                                        :disabled="multiEditCalendarDays.length === 0"
                                        class="pointer-events-auto"
                                        :class="[
                                                multiEditCalendarDays.length === 0 ?
                                                'bg-gray-600' :
                                                'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                    {{ $t('Create') }}
                                </button>
                                <button type="button"
                                        @click="openCellMultiEditCalendarDelete = true"
                                        :disabled="multiEditCalendarDays.length === 0"
                                        class="pointer-events-auto"
                                        :class="[
                                                multiEditCalendarDays.length === 0 ?
                                                'bg-gray-600' :
                                                'cursor-pointer bg-artwork-messages-error hover:bg-artwork-messages-error/90',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                    {{ $t('Delete') }}
                                </button>
                            </div>
                        </div>
                    </template>
                    <template #moreButtons>
                        <Switch @click="toggleMultiEditModeCalendar" v-model="multiEditModeCalendar" :class="[multiEditModeCalendar ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                            <span :class="[multiEditModeCalendar ? 'translate-x-5' : 'translate-x-0', 'inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                <span :class="[multiEditModeCalendar ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-40', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent
                                        icon="IconPencil"
                                        icon-size="h-4 w-4"
                                        :tooltip-text="$t('Edit')"
                                        direction="left"
                                    />
                                </span>
                                <span :class="[multiEditModeCalendar ? 'opacity-100 duration-200 ease-in z-40' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent
                                        icon="IconPencil"
                                        icon-size="h-4 w-4"
                                        :tooltip-text="$t('Edit')"
                                        direction="left"
                                    />
                                </span>
                            </span>
                        </Switch>
                    </template>
                </ShiftPlanFunctionBar>
            </div>
            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div ref="shiftPlan" id="shiftPlan" class="bg-white flex-grow"
                     :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                    <Table>
                        <template #head>
                            <div class="stickyHeader sticky top-0 z-30">
                                <TableHead id="stickyTableHead" ref="stickyTableHead">
                                    <th class="z-0" style="width:192px;"></th>
                                    <th  v-for="day in days" :id="day.isExtraRow ? 'extra_row_' + day.weekNumber : day.fullDay" style="max-width: 204px"
                                         class="z-20 h-8 py-2 border-r-2 border-artwork-navigation-background truncate text-white">
                                        <div v-if="day.isExtraRow" style="width:37px">
                                            <span class="text-[9px] font-bold">KW{{day.weekNumber }}</span>
                                        </div>
                                        <div v-else :style="{width:  '200px'}" class="flex items-center h-full justify-between calendarRoomHeaderBold ml-2">
                                            <div>
                                                {{ day.dayString }} {{ day.fullDay }}
                                            </div>
                                            <div class="mr-5" v-if="day.holidays.length > 0">
                                                <HolidayToolTip>
                                                    <div class="space-y-1 divide-dashed divide-gray-500 divide-y">
                                                        <div v-for="holiday in day.holidays" class="pt-1">
                                                            <div :style="{ color: holiday.color}">
                                                                <div>{{ holiday.name }}</div>
                                                                <div v-if="holiday.subdivisions.length > 0">
                                                                    {{ holiday.subdivisions.map((person) => person).join(', ') }}
                                                                </div>
                                                                <div v-else>
                                                                    {{ $t('Germany-wide') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </HolidayToolTip>
                                            </div>
                                        </div>
                                    </th>
                                </TableHead>
                            </div>
                        </template>
                        <template #body>
                            <TableBody class="events-by-days-container">
                                <tr v-for="(room,index) in newShiftPlanData" class="w-full table-row" :class="$page.props.user.calendar_settings.expand_days ? 'h-full' : 'h-28'">
                                    <th :id="'roomNameContainer_' + index"
                                        class="xsDark w-48 table-cell align-middle"
                                        :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <div class="flex font-semibold items-center ml-4">
                                            {{ room.roomName }}
                                        </div>
                                    </th>
                                    <td :class="[day.isWeekend ? 'bg-backgroundGray' : 'bg-white', day.isSunday ? '' : 'border-dashed', multiEditModeCalendar ? '' : 'border-r-2 ', $page.props.user.calendar_settings.expand_days ? '' : 'h-28']"
                                        class="border-gray-400 relative table-cell align-top day-container"
                                        v-for="day in days" :data-day="day.fullDay">
                                        <div v-if="!day.isExtraRow && multiEditModeCalendar"
                                             :class="[multiEditModeCalendar && !checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ?
                                            'bg-gray-950 opacity-30 hover:bg-opacity-0 hover:border-opacity-100 hover:border-2 border-dashed transition-all duration-150 ease-in-out cursor-pointer border-artwork-buttons-create' : '',
                                            checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ? 'border' : '']"
                                             class="absolute w-full h-full"
                                             @click="addDayAndRoomToMultiEditCalendar(day.fullDay, room.roomId)">
                                        </div>
                                        <div class="bg-backgroundGray2 h-full mb-3" style="width: 37px;" v-if="day.isExtraRow">
                                        </div>
                                        <!-- Build in v-if="this.currentDaysInView.has(day.full_day)" when observer fixed -->
                                        <div v-if="composedCurrentDaysInViewRef.has(day.fullDay)" style="width: 200px" class="cell group " :class="$page.props.user.calendar_settings.expand_days ? 'min-h-12' : 'max-h-28 h-28 overflow-y-auto'">
                                            <div v-if="usePage().props.user.calendar_settings.display_project_groups" v-for="group in getAllProjectGroupsInEventsByDay(room.content[day.fullDay].events)" :key="group.id">
                                                <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectShiftTabId })"  class="bg-artwork-navigation-background text-white text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1">
                                                    <component :is="group.icon" class="size-4" aria-hidden="true"/>
                                                    <span>{{ group.name }}</span>
                                                </Link>
                                            </div>
                                            <div v-for="event in room.content[day.fullDay].events" class="mb-1">
                                                <SingleShiftPlanEvent
                                                    v-if="checkIfEventHasShiftsToDisplay(event)"
                                                    :multiEditMode="multiEditMode"
                                                    :user-for-multi-edit="userForMultiEdit"
                                                    :highlightMode="highlightMode"
                                                    :highlighted-id="idToHighlight"
                                                    :highlighted-type="typeToHighlight"
                                                    :event="event"
                                                    :shift-qualifications="shiftQualifications"
                                                    :day-string="day"
                                                    :firstProjectShiftTabId="firstProjectShiftTabId"
                                                    @dropFeedback="showDropFeedback"
                                                    @event-desires-reload="this.eventDesiresReload"
                                                    @handle-shift-and-event-for-multi-edit="handleShiftAndEventForMultiEdit"
                                                    @click-on-edit="openEditShiftModal"
                                                />
                                                <SingleEventInShiftPlan v-else
                                                                        :event="event"
                                                                        :day="day"
                                                                        :firstProjectShiftTabId="firstProjectShiftTabId"/>
                                            </div>
                                            <div class="space-y-0.5">
                                                <div v-for="shift in room.content[day.fullDay].shifts">
                                                    <div class="bg-gray-50 rounded-lg border border-gray-100 py-0.5"
                                                         v-if="shift.daysOfShift.includes(day.fullDay)">
                                                        <SingleShiftInRoom
                                                            :multiEditMode="multiEditMode"
                                                            :user-for-multi-edit="userForMultiEdit"
                                                            :highlightMode="highlightMode"
                                                            :highlighted-id="idToHighlight"
                                                            :highlighted-type="typeToHighlight"
                                                            :shift="shift"
                                                            :shift-qualifications="shiftQualifications"
                                                            :day-string="day"
                                                            :firstProjectShiftTabId="firstProjectShiftTabId"
                                                            @dropFeedback="showDropFeedback"
                                                            @event-desires-reload="this.eventDesiresReload"
                                                            @handle-shift-and-event-for-multi-edit="handleShiftAndEventForMultiEdit"
                                                            @click-on-edit="openEditShiftModal"

                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!multiEditModeCalendar"
                                                 class="invisible group-hover:visible absolute top-2 right-2 cursor-pointer rounded-full p-0.5 bg-white shadow-md border-2 border-dashed border-artwork-buttons-create"
                                                 @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)">
                                                <ToolTipComponent
                                                    :tooltip-text="$t('Add shift')"
                                                    direction="bottom"
                                                    icon="IconPlus"
                                                    icon-size="h-4 w-4"
                                                    classes="text-artwork-buttons-create"
                                                />
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div id="userOverview" class="w-full fixed bottom-0 z-40">
                <div class="flex justify-center overflow-y-scroll pointer-events-none">
                    <div v-if="this.$can('can plan shifts') || this.$can('can view shift plan') || this.hasAdminRole()" @click="showCloseUserOverview"
                         :class="showUserOverview ? 'rounded-tl-lg' : 'fixed bottom-0 rounded-t-lg'"
                         class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background pointer-events-auto">
                        <div :class="showUserOverview ? '' : ' rotate-180 fixed bottom-0 mb-0.5'">
                            <component is="IconChevronsDown" class="h-4 w-4 text-gray-300"/>
                        </div>
                    </div>
                    <div v-if="showUserOverview" @mousedown="startResize"
                         :class="showUserOverview ? '' : 'fixed bottom-0 '"
                         class="flex h-5 w-8 justify-center items-center cursor-ns-resize bg-artwork-navigation-background pointer-events-auto rounded-tr-lg"
                         :title="$t('Hold and drag to change the size')">
                        <div :class="showUserOverview ? 'rotate-180' : 'fixed bottom-2'">
                            <SelectorIcon class="h-3 w-6 text-gray-400"/>
                        </div>
                    </div>
                </div>
                <div class=" bg-artwork-navigation-background">
                    <div v-show="showUserOverview" ref="userOverview"
                         class="relative w-[97%] bg-artwork-navigation-background overflow-x-scroll z-20 overflow-y-scroll"
                         :style="showUserOverview ? { height: userOverviewHeight + 'px'} : {height: 20 + 'px'}">
                        <div class="flex items-center justify-between w-full fixed py-3 z-20 bg-artwork-navigation-background px-3" :style="{top: calculateTopPositionOfUserOverView}">
                            <div class="flex items-center justify-end gap-x-3">
                                <Switch @click="toggleMultiEditMode" v-model="multiEditMode" :class="[multiEditMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span :class="[multiEditMode ? 'translate-x-5' : 'translate-x-0', 'inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                        <span :class="[multiEditMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-20', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                            <ToolTipComponent
                                                icon="IconPencil"
                                                icon-size="h-4 w-4"
                                                :tooltip-text="$t('Edit')"
                                                direction="right"
                                            />
                                        </span>
                                        <span :class="[multiEditMode ? 'opacity-100 duration-200 ease-in z-20' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                            <ToolTipComponent
                                                icon="IconPencil"
                                                icon-size="h-4 w-4"
                                                :tooltip-text="$t('Edit')"
                                                direction="right"
                                            />
                                        </span>
                                    </span>
                                </Switch>
                                <div class="flex items-center gap-x-2" v-if="dayServices && selectedDayService">
                                    <Switch @click="toggleDayServiceMode" v-model="dayServiceMode"
                                            :class="[dayServiceMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative z-20 inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                        <span class="sr-only">Use setting</span>
                                        <span :class="[dayServiceMode ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                        <span
                                            :class="[dayServiceMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-20', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                            aria-hidden="true">
                                            <ToolTipComponent
                                                :icon="selectedDayService?.icon"
                                                icon-size="h-4 w-4"
                                                :tooltip-text="$t('Day Services')"
                                                direction="bottom"
                                                :style="{color: selectedDayService?.hex_color}"
                                            />
                                        </span>
                                        <span
                                            :class="[dayServiceMode ? 'opacity-100 duration-200 ease-in z-20' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                            aria-hidden="true">
                                            <ToolTipComponent
                                                :icon="selectedDayService?.icon"
                                                icon-size="h-4 w-4"
                                                :tooltip-text="$t('Day Services')"
                                                direction="bottom"
                                                :style="{color: selectedDayService?.hex_color}"
                                            />
                                        </span>
                                    </span>
                                    </Switch>
                                    <DayServiceFilter :current-selected-day-service="selectedDayService"
                                                      :day-services="dayServices"
                                                      @update:current-selected-day-service="updateSelectedDayService"/>
                                </div>
                            </div>
                            <div class="pointer-events-none -mt-1" v-if="multiEditMode">
                                <div v-if="Object.keys(multiEditCellByDayAndUser).length === 0">
                                    <button type="button"
                                            @click="initializeMultiEditSave"
                                            :disabled="this.userForMultiEdit === null"
                                            class="pointer-events-auto"
                                            :class="[
                                                this.userForMultiEdit === null ?
                                                'bg-gray-600' :
                                                'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                        {{ $t('Save') }}
                                    </button>
                                </div>
                                <div v-else class="flex items-center justify-center gap-3">
                                    <button type="button"
                                            @click="showCellMultiEditModal = true"
                                            :disabled="Object.keys(multiEditCellByDayAndUser).length === 0"
                                            class="pointer-events-auto"
                                            :class="[
                                                Object.keys(multiEditCellByDayAndUser).length === 0 ?
                                                'bg-gray-600' :
                                                'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create/90',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                        {{ $t('Edit Entries') }}
                                    </button>
                                    <button type="button"
                                            @click="openCellMultiEditDelete = true"
                                            :disabled="Object.keys(multiEditCellByDayAndUser).length === 0"
                                            class="pointer-events-auto"
                                            :class="[
                                                Object.keys(multiEditCellByDayAndUser).length === 0 ?
                                                'bg-gray-600' :
                                                'cursor-pointer bg-artwork-messages-error hover:bg-artwork-messages-error/90',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                        {{ $t('Delete Entries') }}
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-x-3 pr-24 z-20">
                                <Switch @click="toggleHighlightMode" v-model="highlightMode"
                                        :class="[highlightMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        :class="[highlightMode ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span
                                          :class="[highlightMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-20', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <ToolTipComponent
                                              icon="IconBulb"
                                              icon-size="h-4 w-4"
                                              :tooltip-text="$t('Highlight')"
                                              direction="bottom"
                                          />
                                      </span>
                                      <span
                                          :class="[highlightMode ? 'opacity-100 duration-200 ease-in z-20' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <ToolTipComponent
                                              icon="IconBulb"
                                              icon-size="h-4 w-4"
                                              :tooltip-text="$t('Highlight')"
                                              direction="bottom"
                                          />
                                      </span>
                                </span>
                                </Switch>
                                <Switch @click="toggleCompactMode" v-model="$page.props.user.compact_mode"
                                        :class="[$page.props.user.compact_mode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        :class="[$page.props.user.compact_mode ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span
                                          :class="[$page.props.user.compact_mode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-20', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <ToolTipComponent
                                              icon="IconList"
                                              icon-size="h-4 w-4"
                                              :tooltip-text="$t('Compact view')"
                                              direction="bottom"
                                          />
                                      </span>
                                      <span
                                          :class="[$page.props.user.compact_mode ? 'opacity-100 duration-200 ease-in z-20' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <ToolTipComponent
                                              icon="IconList"
                                              icon-size="h-4 w-4"
                                              :tooltip-text="$t('Compact view')"
                                              direction="bottom"
                                          />
                                      </span>
                                </span>
                                </Switch>
                                <BaseFilter :whiteIcon="true" onlyIcon="true">
                                    <div class="mx-auto w-full max-w-md max-h-44 rounded-2xl border-none mt-2 pb-3">
                                        <div class="relative flex items-start mb-2">
                                            <div class="flex h-6 items-center">
                                                <input id="showFreelancers" v-model="showFreelancers" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
                                            </div>
                                            <div class="ml-2 text-sm leading-6">
                                                <label for="showFreelancers" class="font-medium text-white">{{ $t('Show freelancer') }}</label>
                                            </div>
                                        </div>
                                        <CraftFilter :crafts="crafts" is_tiny/>
                                        <div class="py-4">
                                            <div>
                                                <div class="h-9 flex items-center cursor-pointer" @click="showShiftQualificationFilter = !showShiftQualificationFilter">
                                                    <div class="flex items-center text-white text-xs">
                                                        {{ $t('Shift qualifications') }}
                                                        <IconChevronDown v-if="!showShiftQualificationFilter" class="w-4 h-4 ml-2"/>
                                                        <IconChevronUp v-if="showShiftQualificationFilter" class="w-4 h-4 ml-2"/>
                                                    </div>
                                                </div>
                                                <div v-if="showShiftQualificationFilter">
                                                    <div v-for="shiftQualification in this.shiftQualifications">
                                                        <div class="relative flex items-start mb-2">
                                                            <div class="flex h-6 items-center">
                                                                <input :checked="this.userShiftPlanShiftQualificationFilters.includes(shiftQualification.id)" @change="this.saveShiftQualificationFilter" :value="shiftQualification.id" type="checkbox" class="input-checklist-dark" />
                                                            </div>
                                                            <div class="ml-2 text-sm leading-6">
                                                                <label :for="'shiftQualification-' + shiftQualification.id" class="font-medium text-white">{{ shiftQualification.name }}</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </BaseFilter>
                                <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-fit" right white-icon>
                                    <div class="flex items-center justify-end py-1">
                                    <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="this.resetSort()">
                                        {{ $t('Reset') }}
                                    </span>
                                    </div>
                                    <MenuItem v-for="computedShiftPlanWorkerSortEnum in computedShiftPlanWorkerSortEnums"
                                              v-slot="{ active }">
                                        <div @click="this.applySort(computedShiftPlanWorkerSortEnum)"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_ASCENDING'">
                                                    <span :class="this.$page.props.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                        {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                    </span>
                                                <IconArrowUp class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_DESCENDING'">
                                                    <span :class="this.$page.props.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                        {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                    </span>
                                                <IconArrowDown class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_ASCENDING'">
                                                {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!this.useFirstNameForSort ? $t('First name') : $t('Last name')]) }}
                                                <IconArrowUp class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_DESCENDING'">
                                                {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!this.useFirstNameForSort ? $t('First name') : $t('Last name')]) }}
                                                <IconArrowDown class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING'" class="w-5 h-5"/>
                                            </template>
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>
                        <div class="pt-14 z-10">
                            <table class="w-full text-white overflow-y-scroll z-10">
                                <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                                <div class="z-10">
                                    <tbody class="w-full pt-3" v-for="craft in craftsToDisplay">
                                    <tr class="stickyYAxisNoMarginLeft pl-2 cursor-pointer w-48 xsLight flex justify-between pb-1"
                                        @click="changeCraftVisibility(craft.id)">
                                        {{ craft.name }}
                                        <ChevronDownIcon
                                            :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4 mt-0.5"
                                        />
                                    </tr>
                                    <tr v-if="!closedCrafts.includes(craft.id)" v-for="(user,index) in craft.users"
                                        class="w-full flex ">
                                        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right"
                                            :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                            <DragElement v-if="!highlightMode && !multiEditMode"
                                                         :item="user.element"
                                                         :expected-hours="user.expectedWorkingHours"
                                                         :planned-hours="user.plannedWorkingHours"
                                                         :type="user.type"
                                                         :color="craft.color"
                                                         :craft="craft"
                                                         :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
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
                                                               :craft-id="craft.id"
                                                               :craft="craft"
                                                               :multi-edit-cell-by-day-and-user="multiEditCellByDayAndUser"
                                                               :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                            <HighlightUserCell v-else
                                                               :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                               :item="user.element"
                                                               :expected-hours="user.expectedWorkingHours"
                                                               :planned-hours="user.plannedWorkingHours"
                                                               :type="user.type"
                                                               @highlightShiftsOfUser="highlightShiftsOfUser"
                                                               :color="craft.color"
                                                               :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                        </th>
                                        <td v-for="day in days" class="flex gap-x-0.5 relative">
                                            <div v-if="!day.isExtraRow" :class="[
                                                    highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    $page.props.user.compact_mode ? 'h-8' : '',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && craft.id === userForMultiEdit.craftId ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    multiEditMode && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-100 !overflow-hidden' : ''
                                                ]"
                                                 class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer overflow-y-scroll hover:opacity-100"
                                                 :style="{width: '202px', maxWidth: '202px', maxHeight: '50px'}"
                                                 @click="handleCellClick(user, day)">
                                                <ShiftPlanCell :user="user" :day="day" :classes="[multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-20' : '']"/>
                                            </div>
                                            <div v-else
                                                 class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden"
                                                 style="width: 39px"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && craft.id === userForMultiEdit.craftId ? '' : 'opacity-30' : 'opacity-30' : '']">
                                                <span v-if="user.type === 0">
                                                    {{ user?.weeklyWorkingHours[day.weekNumber]?.difference }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.withoutFormat"
                                                     v-for="(userDayService, position) in userDayServices"
                                                     class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center"
                                                     :class="position > 0 ? '-ml-3' : ''">
                                                    <component :is="userDayService.icon" class="h-4 w-4"
                                                               :style="{color: userDayService.hex_color}"/>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 pl-2 xsLight flex justify-between pb-1"
                                        @click="changeCraftVisibility('noCraft')">
                                        {{ $t('Without craft assignment') }}
                                        <ChevronDownIcon
                                            :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4 mt-0.5"
                                        />
                                    </tr>
                                    <tr v-if="!closedCrafts.includes('noCraft')"
                                        v-for="(user,index) in workersWithoutCraft" class="w-full flex">
                                        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right"
                                            :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                            <DragElement v-if="!highlightMode && !multiEditMode"
                                                         :item="user.element"
                                                         :expected-hours="user.expectedWorkingHours"
                                                         :planned-hours="user.plannedWorkingHours"
                                                         :type="user.type"
                                                         :color="null"
                                                         :craft="null"
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
                                                               :craft-id="0"
                                                               :craft="null"
                                                               :multi-edit-cell-by-day-and-user="multiEditCellByDayAndUser"

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
                                            <div v-if="!day.isExtraRow"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && userForMultiEdit.craftId === 0 ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-100 !overflow-hidden' : '',
                                                    multiEditMode ? '!overflow-hidden' : '']"
                                                 class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer overflow-scroll hover:opacity-100"
                                                 @click="handleCellClick(user, day)"
                                                 :style="{width: '202px', maxWidth: '202px', maxHeight: '50px'}">
                                                <ShiftPlanCell :user="user" :day="day" :classes="[multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-20' : '']"/>
                                            </div>
                                            <div v-else class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden" style="width: 39px"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && userForMultiEdit.craftId === 0 ? '' : 'opacity-30' : 'opacity-30' : '']">
                                                <span v-if="user.type === 0">
                                                    {{ user?.weeklyWorkingHours[day.weekNumber]?.difference }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.withoutFormat"
                                                     v-for="(userDayService, position) in userDayServices"
                                                     class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center"
                                                     :class="position > 0 ? '-ml-3' : ''">
                                                    <component :is="userDayService.icon" class="h-4 w-4"
                                                               :style="{color: userDayService.hex_color}"/>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </div>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <show-user-shifts-modal
                v-if="showUserShifts"
                @closed="showUserShifts = false"
                :user="userToShow"
                :day="dayToShow"
                @desires-reload="userShiftModalDesiresReload"
            />

            <ShiftHistoryModal
                v-if="showHistoryModal"
                :history="history"
                @closed="showHistoryModal = false"
            />

        </ShiftHeader>

    </div>
    <SideNotification
        v-if="dropFeedback"
        type="error"
        :text="dropFeedback"
        @close="dropFeedback = null"
    />

    <ShiftsQualificationsAssignmentModal
        v-if="this.showShiftsQualificationsAssignmentModal"
        :show="this.showShiftsQualificationsAssignmentModal"
        :user="this.userForMultiEdit"
        :shifts="this.showShiftsQualificationsAssignmentModalShifts"
        @close="this.closeShiftsQualificationsAssignmentModal"
    />

    <CellMultiEditModal
        v-if="showCellMultiEditModal"
        :multi-edit-cell-by-day-and-user="multiEditCellByDayAndUser"
        @close="closeMultiEditCellModal"
    />

    <DeleteEntriesModal
        v-if="openCellMultiEditDelete"
        :multi-edit-cell-by-day-and-user="multiEditCellByDayAndUser"
        @close="closeCellMultiEditDelete"
    />

    <DeleteCalendarRoomShiftEntriesModal
        v-if="openCellMultiEditCalendarDelete"
        :multi-edit-cell-by-room-and-dates="multiEditCalendarDays"
        @close="closeCellMultiEditCalendarDelete"
    />

    <AddShiftModal
        v-if="showAddShiftModal"
        :crafts="this.crafts"
        :event="null"
        :shift="shiftToEdit"
        :currentUserCrafts="$page.props.currentUserCrafts"
        :buffer="null"
        :shift-qualifications="$page.props.shiftQualifications"
        @closed="closeAddShiftModal"
        :shift-time-presets="$page.props.shiftTimePresets"
        :room="roomForShiftAdd"
        :day="dayForShiftAdd"
        :shift-plan-modal="true"
        :edit="shiftToEdit !== null"
        :multi-add-mode="multiEditModeCalendar"
        :rooms-and-dates-for-multi-edit="multiEditCalendarDays"
    />
</template>

<script setup>

import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import {IconArrowDown, IconArrowUp, IconCheck, IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import DeleteEntriesModal from "@/Pages/Shifts/Components/DeleteEntriesModal.vue";
import ShiftsQualificationsAssignmentModal
    from "@/Layouts/Components/ShiftPlanComponents/ShiftsQualificationsAssignmentModal.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import SingleEventInShiftPlan from "@/Pages/Shifts/Components/SingleEventInShiftPlan.vue";
import DeleteCalendarRoomShiftEntriesModal from "@/Pages/Shifts/Components/DeleteCalendarRoomShiftEntriesModal.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import ShowUserShiftsModal from "@/Pages/Shifts/Components/ShowUserShiftsModal.vue";
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import SingleShiftInRoom from "@/Pages/Shifts/Components/ShiftWithoutEventComponents/SingleShiftInRoom.vue";
import HighlightUserCell from "@/Pages/Shifts/Components/HighlightUserCell.vue";
import MultiEditUserCell from "@/Pages/Shifts/Components/MultiEditUserCell.vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import ShiftPlanCell from "@/Pages/Shifts/Components/ShiftPlanCell.vue";
import ShiftHistoryModal from "@/Pages/Shifts/Components/ShiftHistoryModal.vue";
import {SelectorIcon} from "@heroicons/vue/solid";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import CellMultiEditModal from "@/Pages/Shifts/Components/CellMultiEditModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import DayServiceFilter from "@/Components/Filter/DayServiceFilter.vue";
import {Link, usePage} from "@inertiajs/vue3";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import Table from "@/Components/Table/Table.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {computed, nextTick, onMounted, onUnmounted, ref} from "vue";
import {useDaysAndEventsIntersectionObserver} from "@/Composeables/IntersectionObserver.js";

const props = defineProps({
    events: Array,
    projects: Array,
    shiftPlan: Object,
    days: Array,
    filterOptions: Object,
    dateValue: [String, Date],
    personalFilters: Object,
    selectedDate: [String, Date],
    history: Array,
    usersForShifts: Array,
    freelancersForShifts: Array,
    serviceProvidersForShifts: Array,
    user_filters: Object,
    crafts: Array,
    shiftQualifications: Array,
    dayServices: Array,
    firstProjectShiftTabId: [Number, null],
    shiftPlanWorkerSortEnums: Array,
    useFirstNameForSort: Boolean,
    userShiftPlanShiftQualificationFilters: Object
});

const { composedCurrentDaysInViewRef, composedStartDaysAndEventsIntersectionObserving } = useDaysAndEventsIntersectionObserver();

const showUserOverview = computed(() => props.$can('can plan shifts') || hasAdminRole());
const isFullscreen = ref(false);
const showHistoryModal = ref(false);
const showUserShifts = ref(false);
const userToShow = ref(null);
const dayToShow = ref(null);
const highlightMode = ref(false);
const idToHighlight = ref(null);
const typeToHighlight = ref(null);
const multiEditMode = ref(false);
const dayServiceMode = ref(false);
const selectedDayService = ref(props.dayServices ? props.dayServices[0] : null);
const userForMultiEdit = ref(null);
const userToMultiEditCurrentShifts = ref([]);
const userToMultiEditCheckedShiftsAndEvents = ref([]);
const dropFeedback = ref(null);
const closedCrafts = ref([]);
const userOverviewHeight = ref(usePage().props.user.drawer_height);
const startY = ref(0);
const startHeight = ref(0);
const windowHeight = ref(window.innerHeight);
const shiftsToHandleOnMultiEdit = ref({
    assignToShift: [],
    removeFromShift: []
});
const showShiftsQualificationsAssignmentModal = ref(false);
const showShiftsQualificationsAssignmentModalShifts = ref([]);
const firstDayPosition = computed(() => props.days ? props.days.find(day => !day.isExtraRow) : null);
const currentDayOnView = computed(() => props.days ? props.days.find(day => !day.isExtraRow) : null);
const currentDaysInView = ref(new Set());
const shiftPlanRef = ref(JSON.parse(JSON.stringify(props.shiftPlan)));
const screenHeight = ref(screen.height);
const showFreelancers = ref(true);
const multiEditCellByDayAndUser = ref({});
const showCellMultiEditModal = ref(false);
const openCellMultiEditDelete = ref(false);
const preventNextNavigation = ref(false);
const resolveModalClose = ref(null);
const waitForModalClose = ref(false);
const navigationGuardActive = ref(true);
const originalVisit = ref(null);
const showShiftQualificationFilter = ref(false);
const multiEditModeCalendar = ref(false);
const multiEditCalendarDays = ref([]);
const dayForShiftAdd = ref(null);
const roomForShiftAdd = ref(null);
const showAddShiftModal = ref(false);
const shiftToEdit = ref(null);
const newShiftPlanData = ref(props.shiftPlan);
const openCellMultiEditCalendarDelete = ref(false);

const shiftPlanElement = ref(null);
const userOverviewElement = ref(null);
// Event Listener für Fenstergröße
const updateWindowHeight = () => {
    windowHeight.value = window.innerHeight;
};

onMounted(() => {
    composedStartDaysAndEventsIntersectionObserving();

    const ShiftCalendarListener = useShiftCalendarListener(newShiftPlanData);
    ShiftCalendarListener.init();

    nextTick(() => {
        if (shiftPlanElement.value) {
            shiftPlanElement.value.addEventListener('scroll', syncScrollShiftPlan);
        }
        if (userOverviewElement.value) {
            userOverviewElement.value.addEventListener('scroll', syncScrollUserOverview);
        }
    });

    window.addEventListener('resize', updateHeight);
    updateHeight();

    setupInertiaNavigationGuard();

    // Intersection Observer für sichtbare Tage
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const day = entry.target.dataset.day;

                if (entry.intersectionRatio > 0) {
                    currentDaysInView.value.add(day);
                } else {
                    currentDaysInView.value.delete(day);
                }
            });
        },
        {
            root: document.querySelector('.eventByDaysContainer'),
            rootMargin: '5000px'
        }
    );

    const dayContainers = document.querySelectorAll('.day-container');
    dayContainers.forEach((container) => observer.observe(container));
});


onUnmounted(() => {
    if (shiftPlanElement.value) {
        shiftPlanElement.value.removeEventListener('scroll', syncScrollShiftPlan);
    }
    if (userOverviewElement.value) {
        userOverviewElement.value.removeEventListener('scroll', syncScrollUserOverview);
    }
    window.removeEventListener('resize', updateHeight);
});

const craftsToDisplay = computed(() => {
    const crafts = props.crafts.map((craft) => ({
        id: craft.id,
        name: craft.name,
        abbreviation: craft.abbreviation,
        users: filterAndSortWorkersOfCraft(craft),
        color: craft?.color,
        universally_applicable: craft.universally_applicable
    }));

    if (!usePage().props.user.show_crafts?.length) {
        return crafts;
    }
    return crafts.filter(craft => usePage().props.user.show_crafts.includes(craft.id));
});

const workersWithoutCraft = computed(() => {
    let workers = filterNonManagingWorkersByShiftQualificationFilter(
        getDropWorkers().filter(user => user.assigned_craft_ids.length === 0)
    );

    if (usePage().props.user.shift_plan_user_sort_by_id === null) {
        return workers;
    }

    if (usePage().props.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING') {
        return sortAscendingByUseFirstNameForSort(workers);
    }

    if (usePage().props.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING') {
        return sortDescendingByUseFirstNameForSort(workers);
    }

    let assignedInternWorkers = workers.filter(worker => isWorkerUser(worker));
    let assignedExternWorkers = workers.filter(worker => isWorkerFreelancer(worker) || isWorkerServiceProvider(worker));

    if (usePage().props.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING') {
        return sortAscendingByUseFirstNameForSort(assignedInternWorkers)
            .concat(sortAscendingByUseFirstNameForSort(assignedExternWorkers));
    }

    if (usePage().props.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING') {
        return sortDescendingByUseFirstNameForSort(assignedExternWorkers)
            .concat(sortDescendingByUseFirstNameForSort(assignedInternWorkers));
    }

    return workers;
});

const computedShiftPlanWorkerSortEnums = computed(() => {
    let nameSortEnums = ['ALPHABETICALLY_NAME_ASCENDING', 'ALPHABETICALLY_NAME_DESCENDING'];
    let sortConfig = [];

    if (nameSortEnums.includes(usePage().props.user.shift_plan_user_sort_by_id)) {
        sortConfig.push('INTERN_EXTERN_ASCENDING');

        if (usePage().props.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING') {
            sortConfig.push('ALPHABETICALLY_NAME_DESCENDING');
        } else {
            sortConfig.push('ALPHABETICALLY_NAME_ASCENDING');
        }

        return sortConfig;
    }

    if (usePage().props.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING') {
        sortConfig.push('INTERN_EXTERN_DESCENDING');
    } else {
        sortConfig.push('INTERN_EXTERN_ASCENDING');
    }

    sortConfig.push('ALPHABETICALLY_NAME_ASCENDING');

    return sortConfig;
});

const getAllProjectGroupsInEventsByDay = (events) => {
    let projectGroups = [];

    events.forEach(event => {
        if (event?.project) {
            let project = event.project;

            if (project.isGroup) {
                // Falls das Projekt selbst eine Gruppe ist, hinzufügen
                if (!projectGroups.some(group => group.id === project.id)) {
                    projectGroups.push(project);
                }
            } else if (project.isInGroup && Array.isArray(project.group)) {
                // Falls das Projekt in einer Gruppe ist, die Gruppen-Infos nutzen
                project.group.forEach(group => {
                    if (!projectGroups.some(g => g.id === group.id)) {
                        projectGroups.push(group);
                    }
                });
            }
        }
    });

    return projectGroups;
}

const checkIfUserIsAdminOrInGroup = (group) => {
    if (hasAdminRole()) {
        return false;
    }

    return !group.userIds.includes(usePage().props.user.id);
}

const initializeCalendarMultiEditSave = () => {
    showAddShiftModal.value = true
}

const closeCellMultiEditCalendarDelete = (boolean) => {
    if(boolean){
        openCellMultiEditCalendarDelete.value = false;
        showAddShiftModal.value = true;
    } else {
        openCellMultiEditCalendarDelete.value = false;
        multiEditCalendarDays.value = [];
    }
}

const openEditShiftModal = (shift) => {
    shiftToEdit.value = shift;
    showAddShiftModal.value = true;
}

const openAddShiftForRoomAndDay = (day, roomId) => {
    shiftToEdit.value = null;
    roomForShiftAdd.value = roomId;
    dayForShiftAdd.value = day;
    showAddShiftModal.value = true;
}
</script>

<style scoped>

</style>