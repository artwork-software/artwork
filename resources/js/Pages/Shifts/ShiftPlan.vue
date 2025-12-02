<template>
    <div class="w-full flex flex-col">
        <ShiftHeader>


            <div aria-live="assertive"
                 class="pointer-events-none fixed inset-0 z-100 flex items-end px-4 py-6 sm:items-start sm:p-6">
                <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
                    <transition
                        enter-active-class="transform ease-out duration-300 transition"
                        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                        enter-to-class="translate-y-0 sm:translate-x-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class=""
                        leave-to-class="opacity-0">
                        <div v-if="notice.show"
                             class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline-1 outline-black/5 dark:bg-gray-800 dark:-outline-offset-1 dark:outline-white/10"
                             role="status">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="shrink-0">
                                        <PropertyIcon :name="noticeIcon" class="size-6" :class="noticeIconClass"
                                                      aria-hidden="true"/>
                                    </div>
                                    <div class="ml-3 w-0 flex-1 pt-0.5">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ notice.title }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ notice.message }}
                                        </p>
                                    </div>
                                    <div class="ml-4 flex shrink-0">
                                        <button type="button" @click="hideNotice"
                                                class="inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:hover:text-white dark:focus:outline-indigo-500">
                                            <span class="sr-only">Close</span>
                                            <PropertyIcon name="IconX" class="size-5" aria-hidden="true"/>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
            <transition name="fade" appear>
                <div
                    class="pointer-events-none fixed inset-x-0 top-5 z-100 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8"
                    v-show="showCalendarWarning.length > 0">
                    <div
                        class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                        <PropertyIcon name="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true"/>
                        <p class="text-sm/6 text-white">
                            {{ showCalendarWarning }}
                        </p>
                        <button type="button" class="-m-1.5 flex-none p-1.5">
                            <span class="sr-only">Dismiss</span>
                            <PropertyIcon name="IconX" class="size-5 text-white" aria-hidden="true"
                                       @click="showCalendarWarning = ''"/>
                        </button>
                    </div>
                </div>
            </transition>
            <div class="bg-white flex-grow">
                <ShiftPlanFunctionBar
                    @previousTimeRange="previousTimeRange"
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
                    :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                    :firstProjectShiftTabId="firstProjectShiftTabId"
                    @select-go-to-next-mode="selectGoToNextMode"
                    @select-go-to-previous-mode="selectGoToPreviousMode"
                    :event-types="eventTypes">
                    <template #multiEditCalendar>
                        <div v-if="multiEditModeCalendar">
                            <div class="flex items-center justify-center gap-x-4">
                                <button type="button" @click="initializeCalendarMultiEditSave"
                                        :disabled="multiEditCalendarDays.length === 0" class="pointer-events-auto"
                                        :class="[multiEditCalendarDays.length === 0 ? 'bg-gray-600' : 'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create/90', 'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create']">
                                    {{ $t('Create') }}
                                </button>
                                <button type="button" @click="openCellMultiEditCalendarDelete = true"
                                        :disabled="multiEditCalendarDays.length === 0" class="pointer-events-auto"
                                        :class="[multiEditCalendarDays.length === 0 ? 'bg-gray-600' : 'cursor-pointer bg-artwork-error hover:bg-artwork-error/90', 'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create']">
                                    {{ $t('Delete') }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <template #moreButtons>
                        <SwitchIconTooltip v-model="dailyViewMode" :tooltip-text="$t('Daily view')" size="md"
                                           @change="changeDailyViewMode" icon="IconCalendarWeek"/>
                        <SwitchIconTooltip v-model="multiEditModeCalendar" :tooltip-text="$t('Edit')" size="md"
                                           @change="toggleMultiEditModeCalendar" icon="IconPencil"/>
                    </template>
                </ShiftPlanFunctionBar>
            </div>
            <div class="z-40" :style="{ '--dynamic-height': windowHeight + 'px' }">
                <div ref="shiftPlanEl" id="shiftPlanEl" class="bg-white flex-grow"
                     :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                    <Table>
                        <template #head>
                            <div class="stickyHeader sticky top-0 z-30">
                                <TableHead id="stickyTableHead" ref="stickyTableHead">
                                    <th class="z-0" style="width:192px;"></th>
                                    <th v-for="day in days" :ref="el => registerMonthSentinel(el as HTMLElement, day)"
                                        :key="`head-${day.fullDay}-${day.weekNumber}`"
                                        :id="day.isExtraRow ? 'extra_row_' + day.weekNumber : day.fullDay"
                                        style="max-width: 204px"
                                        class="z-20 h-8 py-2 px-[1px] border-r-2 border-artwork-navigation-background truncate text-white">
                                        <div v-if="day.isExtraRow" :style="{ width: '202px', maxWidth: '202px' }">
                                            <span class="text-[9px] font-bold">KW{{ day.weekNumber }}</span>
                                        </div>
                                        <div v-else :style="{ width: '200px' }"
                                             class="ml-2 flex h-full items-center justify-between calendarRoomHeaderBold">
                                            <div>{{ day.dayString }} {{ day.fullDay }}</div>
                                            <div class="mr-5" v-if="day.holidays.length > 0">
                                                <HolidayToolTip>
                                                    <div class="space-y-1 divide-y divide-dashed divide-gray-500">
                                                        <div v-for="holiday in day.holidays"
                                                             :key="holiday.date || holiday.name" class="pt-1">
                                                            <div :style="{ color: holiday.color }">
                                                                <div>{{ holiday.name }}</div>
                                                                <div v-if="holiday.subdivisions.length > 0">
                                                                    {{
                                                                        holiday.subdivisions.map((person) => person).join(', ')
                                                                    }}
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
                            <TableBody class="eventByDaysContainer">
                                <tr v-for="(room, index) in newShiftPlanData" :key="room.roomId"
                                    class="w-full table-row divide-x divide-gray-300"
                                    :class="$page.props.auth.user.calendar_settings.expand_days ? 'h-full' : 'h-28'">
                                    <th :id="'roomNameContainer_' + index" class="xsDark table-cell align-middle"
                                        :class="[index % 2 === 0 ? 'bg-background-gray' : 'bg-secondary-hover', isFullscreen || showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <div class="flex items-center font-semibold"
                                             style="width: 191.5px; max-width: 191.5px">
                                            <span class="pl-3">{{ room.roomName }}</span>
                                        </div>
                                    </th>
                                    <td v-for="day in days"
                                        :key="`room-${room.roomId}-${day.fullDay}-${day.weekNumber}`"
                                        :style="{ width: '202px', maxWidth: '202px' }" :data-day="day.fullDay"
                                        class="day-container relative table-cell align-top px-[1px] border-gray-400"
                                        :class="[day.isWeekend ? 'bg-backgroundGray' : 'bg-white', day.isSunday ? '' : '', multiEditModeCalendar ? '' : '', usePage().props.auth.user.calendar_settings.expand_days ? '' : 'h-28']">
                                        <div v-if="!day.isExtraRow && multiEditModeCalendar"
                                             class="absolute h-full w-full"
                                             :class="[multiEditModeCalendar && !checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ? 'bg-gray-950 opacity-30 hover:bg-opacity-0 hover:border-opacity-100 hover:border-2 border-dashed transition-all duration-150 ease-in-out cursor-pointer border-artwork-buttons-create' : '',
                                            checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ? 'border' : '']"
                                             @click="addDayAndRoomToMultiEditCalendar(day.fullDay, room.roomId)"></div>
                                        <div v-if="day.isExtraRow"
                                             class="mb-3 h-full min-w-full bg-background-gray2 border-gray-800"></div>
                                        <div v-else class="cell group"
                                             :class="usePage().props.auth.user.calendar_settings.expand_days ? 'min-h-12' : 'max-h-28 h-28 overflow-y-auto'">
                                            <template
                                                v-if="usePage().props.auth.user.calendar_settings.display_project_groups && room.content[day.fullDay]?.events">
                                                <div
                                                    v-for="group in getAllProjectGroupsInEventsByDay(room.content[day.fullDay].events)"
                                                    :key="group.id">
                                                    <Link :disabled="checkIfUserIsAdminOrInGroup(group)"
                                                          :href="route('projects.tab', {project: group.id,projectTab: firstProjectShiftTabId})"
                                                          class="mb-0.5 flex items-center gap-x-1 rounded-lg bg-artwork-navigation-background px-2 py-1 text-xs font-bold text-white">
                                                        <PropertyIcon :name="group.icon" class="size-4" aria-hidden="true"/>
                                                        <span>{{ group.name }}</span>
                                                    </Link>
                                                </div>
                                            </template>
                                            <template v-if="room.content[day.fullDay]?.events">
                                                <div v-for="event in room.content[day.fullDay].events"
                                                     :key="event.id || event.uuid || event.name" class="mb-1">
                                                    <SingleEventInShiftPlan
                                                        v-if="!checkIfEventHasShiftsToDisplay(event)" :event="event"
                                                        :day="day" :firstProjectShiftTabId="firstProjectShiftTabId"/>
                                                </div>
                                            </template>
                                            <div class="space-y-0.5">
                                                <template v-if="room.content[day.fullDay]?.shifts?.length">
                                                    <!-- 1. Ebene: Projekt-Gruppen -->
                                                    <div
                                                        v-for="group in groupShiftsByProject(room.content[day.fullDay].shifts, day.fullDay)"
                                                        :key="group.projectId ?? `no-project-${day.fullDay}`">
                                                        <!-- Projekt-Gruppen-Container -->
                                                        <div class="rounded-lg border duration-200 ease-in-out"
                                                             :class="group.project ? 'border-sky-300 bg-sky-50/80' : 'border-gray-200 bg-gray-50'">
                                                            <!-- Kopfzeile: Projekt / „ohne Projekt“ -->
                                                            <div>
                                                                <div v-if="group.project"
                                                                     class="flex justify-between items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-sky-800">
                                                                    <span>{{ group.project.name }}</span>
                                                                    <span class="text-[10px] text-sky-600">
                                                                        ({{ group.shifts.length }})
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <!-- 2. Ebene: einzelne Schichten der Gruppe -->
                                                            <div class="space-y-0.5 px-1"
                                                                 :class="group.project ? 'divide-y divide-sky-100' : 'divide-y divide-gray-100'">
                                                                <div v-for="shift in group.shifts"
                                                                     :key="shift.id || shift.dwId || shift.uuid"
                                                                     class="rounded-lg duration-200 ease-in-out"
                                                                     :class="group.project ? 'hover:bg-sky-100' : 'hover:bg-gray-100'">
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
                                                                        @handle-shift-and-event-for-multi-edit="handleShiftAndEventForMultiEdit"
                                                                        @click-on-edit="openEditShiftModal"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <button
                                                type="button"
                                                class="pointer-events-auto group-hover:inline-flex hidden absolute bottom-1 border border-zinc-200 right-1 z-20
                                                     items-center justify-center cursor-pointer gap-1 rounded-md size-7 text-sm font-medium
                                                     ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0
                                                     transition duration-200 ease-in-out"
                                                :aria-label="$t('Add shift')"
                                                v-if="!multiEditModeCalendar"
                                                @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)"
                                            >
                                                <PropertyIcon name="IconPlus" class="size-4"/>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div  id="userOverview" class="fixed bottom-0 z-40 w-full overflow-x-auto pointer-events-none">
                <div class="flex justify-center overflow-y-scroll pointer-events-none">
                    <div class="pointer-events-auto relative mb-2">
                        <!-- Schweben + Shadow + Blur -->
                        <div class="flex items-center justify-between gap-0.5 rounded-full bg-zinc-900/80 backdrop-blur-xl border border-zinc-700/60 px-2.5 py-1.5 transition-all duration-300 ring-1 ring-white/5">
                            <!-- TOGGLE: links -->
                            <button
                                v-if="can('can plan shifts') || can('can view shift plan') || is('artwork admin')"
                                @click="showCloseUserOverview" class="flex items-center justify-center h-7 w-7 rounded-full hover:bg-white/5 active:scale-95 ransition-all duration-200"
                                :aria-pressed="showUserOverview"
                                :title="showUserOverview ? $t('Hide user overview') : $t('Show user overview')">
                                <PropertyIcon
                                    name="IconChevronsDown"
                                    class="h-4 w-4 text-zinc-100 transition-transform duration-400"
                                    :class="!showUserOverview ? 'rotate-180' : ''"
                                />
                            </button>

                            <!-- Divider -->
                            <div v-if="showUserOverview" class="mx-0.5 h-5 w-px bg-white/10"></div>

                            <!-- RESIZE: rechts -->
                            <button
                                v-if="showUserOverview"
                                @mousedown="startResize"
                                @dblclick="() => { showUserOverview = !showUserOverview; updateLayout() }"
                                class="flex items-center justify-center h-7 w-10 cursor-ns-resize rounded-full hover:bg-white/5 active:scale-95 transition-all duration-200"
                                :title="$t('Hold and drag to change the size')">
                                <!-- iOS-Grabber-Style -->
                                <div class="flex flex-col items-center gap-0.5">
                                    <span class="block h-0.5 w-5 rounded-full bg-zinc-300/80"></span>
                                    <span class="block h-0.5 w-5 rounded-full bg-zinc-500/60"></span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-artwork-navigation-background pointer-events-auto">
                    <div
                        v-show="showUserOverview"
                        ref="userOverviewEl"
                        class="relative z-20 w-[97%] overflow-y-scroll bg-artwork-navigation-background "
                        :style="showUserOverview ? { height: userOverviewHeight + 'px' } : { height: 20 + 'px' }"
                    >
                        <div
                            class="fixed z-20 flex w-full items-center justify-between bg-artwork-navigation-background pr-9 py-3"
                            :style="{ top: calculateTopPositionOfUserOverView }"
                        >
                            <div class="flex items-center justify-end gap-x-3">
                                <SwitchIconTooltip v-model="multiEditMode" :tooltip-text="$t('Edit')" size="md" @change="toggleMultiEditMode" icon="IconPencil"/>
                                <ToolTipComponent
                                    direction="right"
                                    :tooltip-text="$t('Create individual time series')"
                                    icon="IconClockShield"
                                    icon-size="h-5 w-5"
                                    @click="showIndividualTimeSeriesModal = true"
                                    classesButton="ui-button-small"
                                />
                                <div v-if="dayServices && selectedDayService" class="flex items-center gap-x-2">
                                    <SwitchIconTooltip v-model="dayServiceMode" :tooltip-text="$t('Day Services')"
                                                       size="md" @change="toggleDayServiceMode"
                                                       :icon="selectedDayService?.icon"/>
                                    <DayServiceFilter :current-selected-day-service="selectedDayService"
                                                      :day-services="dayServices"
                                                      @update:current-selected-day-service="updateSelectedDayService"/>
                                </div>
                            </div>
                            <div class="pointer-events-none -mt-1" v-if="multiEditMode">
                                <div v-if="Object.keys(multiEditCellByDayAndUser).length !== 0"
                                     class="flex items-center justify-center gap-3">
                                    <button type="button" @click="showCellMultiEditModal = true"
                                            :disabled="Object.keys(multiEditCellByDayAndUser).length === 0"
                                            class="pointer-events-auto" :class="[
                                           Object.keys(multiEditCellByDayAndUser).length === 0 ? 'bg-gray-600' : 'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create/90',
                                            'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create']">
                                        {{ $t('Edit Entries') }}
                                    </button>
                                    <button type="button" @click="openCellMultiEditDelete = true"
                                            :disabled="Object.keys(multiEditCellByDayAndUser).length === 0"
                                            class="pointer-events-auto" :class="[
                                            Object.keys(multiEditCellByDayAndUser).length === 0 ? 'bg-gray-600' : 'cursor-pointer bg-artwork-error hover:bg-artwork-error/90',
                                            'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create']">
                                        {{ $t('Delete Entries') }}
                                    </button>
                                </div>
                            </div>
                            <div class="z-20 flex items-center justify-end gap-x-3 pr-24">
                                <SwitchIconTooltip v-model="highlightMode" :tooltip-text="$t('Highlight')" size="md"
                                                   @change="toggleHighlightMode" icon="IconBulb"/>
                                <SwitchIconTooltip v-model="$page.props.auth.user.compact_mode"
                                                   :tooltip-text="$t('Compact view')" size="md"
                                                   @change="toggleCompactMode"
                                                   :icon="!$page.props.auth.user.compact_mode ? 'IconTextDecrease' : 'IconTextIncrease'"/>
                                <BaseFilter :whiteIcon="false" :onlyIcon="true">
                                    <div class="mx-auto mt-2 w-full max-h-44 max-w-md rounded-2xl border-none pb-3">
                                        <div class="mb-2 flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input id="showFreelancers" v-model="showFreelancers"
                                                       aria-describedby="comments-description" name="comments"
                                                       type="checkbox" class="input-checklist"/>
                                            </div>
                                            <div class="ml-2 text-sm leading-6">
                                                <label for="showFreelancers" class="font-medium text-white">
                                                    {{ $t('Show freelancer') }}
                                                </label>
                                            </div>
                                        </div>
                                        <CraftFilter :crafts="crafts" :filtered-craft-ids="user_filters.craft_ids"
                                                     is_tiny/>
                                        <div class="py-4">
                                            <div>
                                                <div class="flex h-9 cursor-pointer items-center"
                                                     @click="showShiftQualificationFilter = !showShiftQualificationFilter">
                                                    <div class="flex items-center text-xs text-white">
                                                        {{ $t('Shift qualifications') }}
                                                        <PropertyIcon name="IconChevronDown" v-if="!showShiftQualificationFilter"
                                                                         class="ml-2 h-4 w-4"/>
                                                        <PropertyIcon name="IconChevronUp" v-else class="ml-2 h-4 w-4"/>
                                                    </div>
                                                </div>
                                                <div v-if="showShiftQualificationFilter">
                                                    <div v-for="shiftQualification in shiftQualifications"
                                                         :key="shiftQualification.id">
                                                        <div class="relative mb-2 flex items-start">
                                                            <div class="flex h-6 items-center">
                                                                <input
                                                                    :checked="userShiftPlanShiftQualificationFilters.includes(shiftQualification.id)"
                                                                    @change="saveShiftQualificationFilter"
                                                                    :value="shiftQualification.id" type="checkbox"
                                                                    class="input-checklist-dark"/>
                                                            </div>
                                                            <div class="ml-2 text-sm leading-6">
                                                                <label
                                                                    :for="'shiftQualification-' + shiftQualification.id"
                                                                    class="font-medium text-white">
                                                                    {{ shiftQualification.name }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </BaseFilter>
                                <BaseMenu show-sort-icon dots-size="size-6" menu-width="w-fit" right
                                          classesButton="ui-button-small hover:!bg-white text-artwork-buttons-context">
                                    <div class="flex items-center justify-end py-1">
                                        <span class="xxsLight w-full cursor-pointer pr-4 pt-0.5 text-right"
                                              @click="resetSort()">
                                            {{ $t('Reset') }}
                                        </span>
                                    </div>
                                    <MenuItem
                                        v-for="computedShiftPlanWorkerSortEnum in computedShiftPlanWorkerSortEnums"
                                        :key="computedShiftPlanWorkerSortEnum" v-slot="{ active }">
                                        <div @click="applySort(computedShiftPlanWorkerSortEnum)"
                                             :class="[active ? 'text-gray-500' : 'text-secondary','group flex cursor-pointer items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                            <template
                                                v-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_ASCENDING'">
                                                <span
                                                    :class="usePage().props.auth.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                    {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                </span>
                                                <PropertyIcon name="IconArrowUp" class="h-5 w-5"/>
                                                <PropertyIcon name="IconCheck"
                                                    v-if="usePage().props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING'"
                                                    class="h-5 w-5"/>
                                            </template>
                                            <template
                                                v-else-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_DESCENDING'">
                                                <span
                                                    :class="usePage().props.auth.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                    {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                </span>
                                                <PropertyIcon name="IconArrowDown" class="h-5 w-5"/>
                                                <PropertyIcon name="IconCheck"
                                                    v-if="usePage().props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING'"
                                                    class="h-5 w-5"/>
                                            </template>

                                            <template
                                                v-else-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_ASCENDING'">
                                                {{
                                                    getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!useFirstNameForSort ? $t('First name') : $t('Last name')])
                                                }}
                                                <PropertyIcon name="IconArrowUp" class="h-5 w-5"/>
                                                <PropertyIcon name="IconCheck"
                                                    v-if="usePage().props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING'"
                                                    class="h-5 w-5"/>
                                            </template>
                                            <template
                                                v-else-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_DESCENDING'">
                                                {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!useFirstNameForSort ? $t('First name') : $t('Last name')]) }}
                                                <PropertyIcon name="IconArrowDown" class="h-5 w-5"/>
                                                <PropertyIcon name="IconCheck"
                                                    v-if="usePage().props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING'"
                                                    class="h-5 w-5"/>
                                            </template>
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>

                        <div class="pt-10 z-10 min-w-max">
                            <!-- Wrapper für die gesamte Achse -->
                            <div class="w-full">
                                <div v-for="craft in craftsToDisplay" :key="craft.id" class="pt-3">
                                    <!-- Craft-Kopf (war vorher <tr> mit <td colspan>) -->
                                    <div class="stickyYAxisNoMarginLeft xsLight !font-lexend mb-1 flex w-96 cursor-pointer justify-between pb-1" @click="changeCraftVisibility(craft.id)">
                                        <div class="flex items-center justify-between gap-x-3 w-full">
                                            <div class="flex items-center gap-2">
                                                <span>{{ craft.name }}</span>
                                                <!-- user count badge -->
                                                <span v-if="craft.users.length > 0" class="inline-flex items-center rounded-full bg-white/10 gap-x-2 px-2 py-0.5 text-[9px] font-normal text-gray-100">
                                                    {{ craft.users.length }}
                                                    <span class="inline-block h-2 w-2 rounded-full bg-gray-100"></span>
                                                </span>
                                            </div>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-normal text-gray-100">
                                                <PropertyIcon name="IconChevronDown"
                                                    class="mt-[1px] h-3.5 w-3.5 transition-transform duration-200"
                                                    :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180'"
                                                />
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Users für dieses Craft -->
                                    <div
                                        v-for="(user, index) in craft.users"
                                        :key="user.element.id + '_' + user.type"
                                        v-if="!closedCrafts.includes(craft.id)"
                                        class="flex w-full"
                                    >
                                        <!-- Linke, sticky Spalte (User-Zelle) -->
                                        <div
                                            class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right pb-[1px]"
                                            :class="[multiEditMode ? '' : '', index % 2 === 0 ? '' : '']"
                                            :style="{ width: '191.5px', maxWidth: '191.5px', minWidth: '191.5px' }"
                                        >
                                            <DragElement
                                                v-if="!highlightMode && !multiEditMode"
                                                :item="user.element"
                                                :work-time-balance="user.workTimeBalance"
                                                :type="user.type"
                                                :color="craft.color"
                                                :craft="craft"
                                                :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                            <MultiEditUserCell
                                                v-else-if="multiEditMode && !highlightMode"
                                                :item="user.element"
                                                :work-time-balance="user.workTimeBalance"
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
                                            <HighlightUserCell
                                                v-else
                                                :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === typeToHighlight : false"
                                                :item="user.element"
                                                :work-time-balance="user.workTimeBalance"
                                                :type="user.type"
                                                @highlightShiftsOfUser="highlightShiftsOfUser"
                                                :color="craft.color"
                                                :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                        </div>
                                        <template v-for="day in days"
                                             :key="'user-' + user.element.id + '-' + user.type + '-' + day.fullDay"
                                             class="relative">
                                            <div v-if="!day.isExtraRow" :style="{ width: '202px', maxWidth: '202px' }"
                                                 :class="[
                                                    highlightMode
                                                        ? idToHighlight
                                                            ? idToHighlight === user.element.id && user.type === typeToHighlight
                                                                ? ''
                                                                : 'opacity-30'
                                                            : 'opacity-30'
                                                        : '',
                                                    $page.props.auth.user.compact_mode ? 'h-8 max-h-8' : 'h-12 max-h-12',
                                                    multiEditMode
                                                        ? userForMultiEdit
                                                            ? userForMultiEdit.id === user.element.id &&
                                                              user.type === userForMultiEdit.type &&
                                                              craft.id === userForMultiEdit.craftId
                                                                ? ''
                                                                : 'opacity-30'
                                                            : 'opacity-30'
                                                        : '',
                                                    multiEditMode &&
                                                    multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type ===
                                                        user.type &&
                                                    multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(
                                                        day.withoutFormat,
                                                    )
                                                        ? '!opacity-100 !overflow-hidden'
                                                        : '',
                                                ]"
                                                                             @click="handleCellClick(user, day)"
                                                                        >
                                                                            <ShiftPlanCell
                                                                                :user="user"
                                                                                :day="day"
                                                                                :classes="[
                                                        multiEditMode &&
                                                        multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type ===
                                                            user.type &&
                                                        multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(
                                                            day.withoutFormat,
                                                        )
                                                            ? '!opacity-20'
                                                            : '',
                                                    ]"
                                                />
                                            </div>

                                            <!-- Extra-Row / Wochenarbeitszeit -->
                                            <div
                                                v-else
                                                class="shiftCell flex h-full items-center justify-center overflow-hidden rounded-lg bg-gray-50/30 p-2 text-center text-white"
                                                :style="{ width: '202px', maxWidth: '202px' }"
                                                :class="[
                                                    highlightMode
                                                        ? idToHighlight
                                                            ? idToHighlight === user.element.id && user.type === typeToHighlight
                                                                ? ''
                                                                : 'opacity-30'
                                                            : 'opacity-30'
                                                        : '',
                                                    $page.props.auth.user.compact_mode ? 'h-8 max-h-8' : '',
                                                    multiEditMode
                                                        ? userForMultiEdit
                                                            ? userForMultiEdit.id === user.element.id &&
                                                              user.type === userForMultiEdit.type &&
                                                              craft.id === userForMultiEdit.craftId
                                                                ? ''
                                                                : 'opacity-30'
                                                            : 'opacity-30'
                                                        : '',
                                                ]"
                                            >
                                                <div
                                                    :class="$page.props.auth.user.compact_mode ? 'flex items-center gap-x-1' : ''">
                                                    <div class="text-[9px]">
                                                        Arbeitszeit KW {{ day.weekNumber }}
                                                    </div>
                                                    <div
                                                        class="font-lexend text-xs"
                                                        :class="
                                                            user?.weeklyWorkingHours[day.weekNumber]?.isMinus
                                                                ? 'text-red-100'
                                                                : 'text-green-100'
                                                        "
                                                    >
                                                        {{ user?.weeklyWorkingHours[day.weekNumber]?.difference }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- DayServices (Icons) -->
                                            <template
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, indexDay) in user.dayServices"
                                                :key="indexDay"
                                                class="absolute right-2 top-1/2 flex -translate-y-1/2 transform"
                                            >
                                                <template v-if="indexDay === day.withoutFormat">
                                                    <template
                                                        v-for="(userDayService, position) in userDayServices"
                                                        :key="userDayService.id || position"
                                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-white p-0.5"
                                                        :class="position > 0 ? '-ml-3' : ''"
                                                    >
                                                        <ToolTipComponent
                                                            :tooltip-text="userDayService.name"
                                                            :icon="userDayService.icon"
                                                            icon-size="h-4 w-4"
                                                            :icon-style="{ color: userDayService.hex_color }"
                                                            :classes-button="'mt-0'"
                                                        />
                                                    </template>
                                                </template>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <ShowUserShiftsModal
                v-if="showUserShifts"
                @closed="showUserShifts = false"
                :user="userToShow"
                :day="dayToShow"
                :shift-qualifications="shiftQualifications"
            />
            <ShiftHistoryModal
                v-if="showHistoryModal"
                :logs="history"
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
        v-if="showShiftsQualificationsAssignmentModal"
        :show="showShiftsQualificationsAssignmentModal"
        :user="userForMultiEdit"
        :shifts="showShiftsQualificationsAssignmentModalShifts"
        @close="closeShiftsQualificationsAssignmentModal"
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
        :crafts="crafts"
        :event="null"
        :shift="shiftToEdit"
        :current-user-crafts="currentUserCrafts"
        :buffer="null"
        :shift-qualifications="shiftQualifications"
        :shift-groups="usePage().props.shiftGroups"
        :global-qualifications="usePage().props.globalQualifications"
        @closed="closeAddShiftModal"
        :shift-time-presets="shiftTimePresets"
        :rooms="rooms"
        :room="roomForShiftAdd"
        :day="dayForShiftAdd"
        :shift-plan-modal="true"
        :edit="shiftToEdit !== null"
        :multi-add-mode="multiEditModeCalendar"
        :rooms-and-dates-for-multi-edit="multiEditCalendarDays"
    />

    <IndividualTimeSeriesModal
        v-if="showIndividualTimeSeriesModal"
        @close="showIndividualTimeSeriesModal = false"
        @updated="handleSeriesUpdated"
    />
</template>

<script setup lang="ts">

import Permissions from '@/Mixins/Permissions.vue'
import axios from 'axios'
import {Link, router, usePage} from '@inertiajs/vue3'
import ShiftPlanFunctionBar from '@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue'
import ShiftHeader from '@/Pages/Shifts/ShiftHeader.vue'
import {MenuItem} from '@headlessui/vue'
import Table from '@/Components/Table/Table.vue'
import TableHead from '@/Components/Table/TableHead.vue'
import TableBody from '@/Components/Table/TableBody.vue'
import BaseFilter from '@/Layouts/Components/BaseFilter.vue'
import {computed, onBeforeUnmount, onMounted, reactive, ref, watch, getCurrentInstance, defineAsyncComponent} from 'vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import {useSortEnumTranslation} from '@/Composeables/SortEnumTranslation.js'
import dayjs from 'dayjs'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import {useShiftCalendarListener} from '@/Composeables/Listener/useShiftCalendarListener.js'
import SwitchIconTooltip from '@/Artwork/Toggles/SwitchIconTooltip.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {can, is} from 'laravel-permission-to-vuejs'
import {useUserOverviewLayout} from '@/Pages/Shifts/Composables/useUserOverviewLayout.ts'
import {useSyncedHorizontalScroll} from '@/Pages/Shifts/Composables/useSyncedHorizontalScroll.ts'

defineOptions({
    name: 'ShiftPlan',
    mixins: [Permissions],
})

const DragElement = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/DragElement.vue'),
    delay: 200,
    timeout: 3000,
})

const ShiftPlanCell = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftPlanCell.vue'),
    delay: 200,
    timeout: 3000,
})

const SingleShiftInRoom = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftWithoutEventComponents/SingleShiftInRoom.vue'),
    delay: 200,
    timeout: 3000,
})

const HolidayToolTip = defineAsyncComponent({
    loader: () => import('@/Components/ToolTips/HolidayToolTip.vue'),
    delay: 200,
    timeout: 3000,
})

const HighlightUserCell = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/HighlightUserCell.vue'),
    delay: 200,
    timeout: 3000,
})

const MultiEditUserCell = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/MultiEditUserCell.vue'),
    delay: 200,
    timeout: 3000,
})

const CraftFilter = defineAsyncComponent({
    loader: () => import('@/Components/Filter/CraftFilter.vue'),
    delay: 200,
    timeout: 3000,
})

const SingleEventInShiftPlan = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/SingleEventInShiftPlan.vue'),
    delay: 200,
    timeout: 3000,
})

const DayServiceFilter = defineAsyncComponent({
    loader: () => import('@/Components/Filter/DayServiceFilter.vue'),
    delay: 200,
    timeout: 3000,
})

const ShowUserShiftsModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShowUserShiftsModal.vue'),
    delay: 200,
    timeout: 3000,
})

const ShiftHistoryModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftHistoryModal.vue'),
    delay: 200,
    timeout: 3000,
})

const SideNotification = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/General/SideNotification.vue'),
    delay: 200,
    timeout: 3000,
})

const ShiftsQualificationsAssignmentModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ShiftPlanComponents/ShiftsQualificationsAssignmentModal.vue'),
    delay: 200,
    timeout: 3000,
})

const CellMultiEditModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/CellMultiEditModal.vue'),
    delay: 200,
    timeout: 3000,
})

const DeleteEntriesModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/DeleteEntriesModal.vue'),
    delay: 200,
    timeout: 3000,
})

const DeleteCalendarRoomShiftEntriesModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/DeleteCalendarRoomShiftEntriesModal.vue'),
    delay: 200,
    timeout: 3000,
})

const AddShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/AddShiftModal.vue'),
    delay: 200,
    timeout: 3000,
})

const IndividualTimeSeriesModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/IndividualTimeSeriesModal.vue'),
    delay: 200,
    timeout: 3000,
})

type ShiftPlanProps = {
    events?: any[]
    projectId?: number | null
    projects?: any[]
    shiftPlan?: any
    days?: any[]
    dateValue?: [string, string]
    filterOptions?: any
    user_filters?: any
    personalFilters?: any
    selectedDate?: any
    history?: any
    usersForShifts?: any[]
    freelancersForShifts?: any[]
    serviceProvidersForShifts?: any[]
    crafts?: any[]
    shiftQualifications?: any[]
    dayServices?: any[]
    firstProjectShiftTabId?: number | null
    shiftPlanWorkerSortEnums?: any[]
    useFirstNameForSort?: boolean
    userShiftPlanShiftQualificationFilters?: number[]
    projectNameUsedForProjectTimePeriod?: any
    calendarWarningText?: string
    eventTypes?: any[],
    shiftTimePresets?: any[],
    currentUserCrafts?: any[],
}

const props = withDefaults(defineProps<ShiftPlanProps>(), {
    events: () => [],
    projectId: null,
    projects: () => [],
    shiftPlan: () => ({}),
    days: () => [],
    dateValue: () => ['1970-01-01', '1970-01-01'],
    filterOptions: () => ({}),
    user_filters: () => ({}),
    personalFilters: () => ({}),
    selectedDate: null,
    history: () => ({}),
    usersForShifts: () => [],
    freelancersForShifts: () => [],
    serviceProvidersForShifts: () => [],
    crafts: () => [],
    shiftQualifications: () => [],
    dayServices: () => [],
    firstProjectShiftTabId: null,
    shiftPlanWorkerSortEnums: () => [],
    useFirstNameForSort: false,
    userShiftPlanShiftQualificationFilters: () => [],
    projectNameUsedForProjectTimePeriod: null,
    calendarWarningText: '',
    eventTypes: () => [],
    shiftTimePresets: () => [],
    currentUserCrafts: () => [],
})

type Day = {
    isExtraRow?: boolean
    isMonday?: boolean
    isFirstDayOfMonth?: boolean
    weekNumber?: number
    monthNumber?: number
    fullDay?: string
    withoutFormat?: string
    [key: string]: any
}

const days = ref<any[]>(props.days ?? [])
const rooms = ref([])
const isFullscreen = ref(false)
const showHistoryModal = ref(false)
const showUserShifts = ref(false)
const userToShow = ref<any | null>(null)
const dayToShow = ref<any | null>(null)
const highlightMode = ref(false)
const idToHighlight = ref<number | string | null>(null)
const typeToHighlight = ref<number | null>(null)
const multiEditMode = ref(false)
const dayServiceMode = ref(false)
const selectedDayService = ref(props.dayServices?.[0] ?? null)
const userForMultiEdit = ref<any | null>(null)
const userToMultiEditCurrentShifts = ref<number[]>([])
const userToMultiEditCheckedShiftsAndEvents = ref<any[]>([])
const dropFeedback = ref<string | null>(null)
const closedCrafts = ref<number[]>([])
const shiftsToHandleOnMultiEdit = reactive<{ assignToShift: any[]; removeFromShift: any[] }>({
    assignToShift: [],
    removeFromShift: [],
})

const currentDayOnView = ref<Day | null>(
    days.value && days.value.length
        ? (days.value.find((d: any) => !d.isExtraRow) ?? null)
        : null,
)

const showFreelancers = ref(true)
const multiEditCellByDayAndUser = ref<Record<string, any>>({})
const showCellMultiEditModal = ref(false)
const openCellMultiEditDelete = ref(false)
const showIndividualTimeSeriesModal = ref(false)
const preventNextNavigation = ref(false)
const isLoadingMonth = ref(false)
const originalVisit = ref<any | null>(null)
const showShiftQualificationFilter = ref(false)
const multiEditModeCalendar = ref(false)
const multiEditCalendarDays = ref<{ day: string; roomId: number }[]>([])
const dayForShiftAdd = ref<any | null>(null)
const roomForShiftAdd = ref<number | null>(null)
const showAddShiftModal = ref(false)
const shiftToEdit = ref<any | null>(null)
const newShiftPlanData = ref(props.shiftPlan)
const openCellMultiEditCalendarDelete = ref(false)
const dailyViewMode = ref(!!usePage().props.auth.user.daily_view)
const showCalendarWarning = ref<string>(props.calendarWarningText || '')
const saveQueue = ref<Promise<any>>(Promise.resolve())
const savingShiftIds = ref<Set<number>>(new Set())
const showShiftsQualificationsAssignmentModal = ref(false)
const showShiftsQualificationsAssignmentModalShifts = ref<any[]>([])
const waitForModalClose = ref(false)
const resolveModalClose = ref<null | (() => void)>(null)
const _singlePickResolve = ref<any | null>(null)
const _singlePickShiftId = ref<number | null>(null)
const multiEditSessionId = ref(0)
const notice = reactive<{
    show: boolean;
    kind: 'success' | 'error' | 'info';
    title: string;
    message: string;
    _timeoutId: any | null
}>({
    show: false,
    kind: 'success',
    title: '',
    message: '',
    _timeoutId: null,
})

const instance = getCurrentInstance()
const $t = (instance?.proxy as any)?.$t ?? ((s: string) => s)
const $toast = (instance?.proxy as any)?.$toast

const showUserOverview = ref(true)
const pageProps = usePage().props

const {userOverviewHeight, windowHeight, startResize, updateLayout} = useUserOverviewLayout(showUserOverview, {
    headerHeight: 100,
    topGap: 0,
    minTop: 100,
    minMain: 200,
    pageProps,
})

const shiftPlanEl = ref<HTMLElement | null>(null)
const userOverviewEl = ref<HTMLElement | null>(null)
const daysRef = ref<any[]>(days.value)
const currentDayRef = ref<any>(null)

const {attach, detach} = useSyncedHorizontalScroll(
    shiftPlanEl,
    userOverviewEl,
    daysRef,
    currentDayRef,
    {roomNameOffsetPx: 200},
)


type ShiftGroup = {
    project: any | null
    projectId: number | null
    shifts: any[]
}

function groupShiftsByProject(shifts: any[] = [], dayLabel: string): ShiftGroup[] {
    const groupsMap = new Map<string, ShiftGroup>()
    const PROJECTLESS_KEY = 'no_project'

    for (const shift of shifts) {
        // nur Schichten für den aktuellen Tag
        if (!shift.daysOfShift?.includes(dayLabel)) continue

        const hasProject = !!shift.project
        const key = hasProject ? `project_${shift.project.id}` : PROJECTLESS_KEY

        if (!groupsMap.has(key)) {
            groupsMap.set(key, {
                project: hasProject ? shift.project : null,
                projectId: hasProject ? shift.project.id : null,
                shifts: [],
            })
        }

        groupsMap.get(key)!.shifts.push(shift)
    }

    const groups = Array.from(groupsMap.values())

    // Sortierung: erst Projekte (optional nach Name), dann "ohne Projekt"
    return groups.sort((a, b) => {
        if (a.project && !b.project) return -1
        if (!a.project && b.project) return 1
        if (a.project && b.project) {
            return (a.project.name || '').localeCompare(b.project.name || '')
        }
        return 0
    })
}

const monthObserver = ref<IntersectionObserver | null>(null)
const watchedMonths = new Map<Element, string>()

function initMonthObserver() {
    if (monthObserver.value) return

    monthObserver.value = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            const monthKey = watchedMonths.get(entry.target)
            if (!monthKey) continue

            if (entry.isIntersecting) {
                const bounding = entry.boundingClientRect
                const rootRect = entry.rootBounds

                if (!rootRect) continue

                const isRightEdge = bounding.right > rootRect.right - 50
                const isLeftEdge = bounding.left < rootRect.left + 50

                if (isRightEdge) {
                    loadMonth('next')
                } else if (isLeftEdge) {
                    loadMonth('prev')
                }
            }
        }
    }, {
        root: shiftPlanEl.value ?? null,
        rootMargin: '0px',
        threshold: 0.1,
    })
}

function registerMonthSentinel(el: HTMLElement | null, day: any) {
    if (!el || !day) return
    initMonthObserver()

    const monthKey = day.monthNumber ?? day.monthKey ?? day.fullDay?.slice(0, 7)

    // If user selected a fixed date range, skip observing months that are completely outside that range
    try {
        const selectedStart = props.dateValue && props.dateValue[0] ? dayjs(props.dateValue[0]).startOf('day') : null
        const selectedEnd = props.dateValue && props.dateValue[1] ? dayjs(props.dateValue[1]).endOf('day') : null

        if (selectedStart && selectedEnd && monthKey) {
            const monthStart = dayjs(monthKey + '-01')
            const monthEnd = monthStart.endOf('month')
            if (monthEnd.isBefore(selectedStart, 'day') || monthStart.isAfter(selectedEnd, 'day')) {
                // Do not observe months outside selected range
                return
            }
        }
    } catch (e) {
        // parsing failed - fall back to observing
    }

    watchedMonths.set(el, monthKey)

    monthObserver.value?.observe(el)
}

function getCurrentRange() {
    if (!days.value.length) {
        return {start: null as dayjs.Dayjs | null, end: null as dayjs.Dayjs | null}
    }

    const first = dayjs(days.value[0].withoutFormat ?? days.value[0].fullDay)
    const last = dayjs(days.value[days.value.length - 1].withoutFormat ?? days.value[days.value.length - 1].fullDay)

    return {start: first, end: last}
}


function setCurrentDayRef(day: any) {
    currentDayRef.value = day
}


type LoadDirection = 'next' | 'prev'

async function loadMonth(direction: LoadDirection) {
    if (isLoadingMonth.value) return
    isLoadingMonth.value = true

    try {
        const {start, end} = getCurrentRange()
        if (!start || !end) return

        let rangeStart: dayjs.Dayjs
        let rangeEnd: dayjs.Dayjs

        if (direction === 'next') {
            const firstOfNextMonth = end.add(1, 'day').startOf('month')
            rangeStart = firstOfNextMonth
            rangeEnd = firstOfNextMonth.endOf('month')
        } else {
            const lastOfPrevMonth = start.subtract(1, 'day').endOf('month')
            rangeEnd = lastOfPrevMonth
            rangeStart = lastOfPrevMonth.startOf('month')
        }
        const selectedStart = props.dateValue && props.dateValue[0] ? dayjs(props.dateValue[0]) : null
        const selectedEnd = props.dateValue && props.dateValue[1] ? dayjs(props.dateValue[1]) : null

        if (selectedStart && selectedEnd) {
            // If the candidate range is completely outside the selected range -> skip loading
            if (rangeEnd.isBefore(selectedStart, 'day') || rangeStart.isAfter(selectedEnd, 'day')) {
                return
            }

            // Clamp the requested range to the selected range so we never request outside of it
            if (rangeStart.isBefore(selectedStart, 'day')) {
                rangeStart = selectedStart.startOf('day')
            }
            if (rangeEnd.isAfter(selectedEnd, 'day')) {
                rangeEnd = selectedEnd.endOf('day')
            }
        }
    } catch (e) {
        // dayjs parsing error - fall back to original behavior (do not block loading)
        // eslint-disable-next-line no-console
        console.debug('loadMonth: could not parse props.dateValue, proceeding without range guard', e)
    } finally {
        isLoadingMonth.value = false
    }

    const {data} = await axios.get(route('shift.plan.all'), {
        params: {
            start_date: rangeStart.format('YYYY-MM-DD'),
            end_date: rangeEnd.format('YYYY-MM-DD'),
            projectId: props.projectId
        },
    })

    const newDays = data.days ?? []
    const newRooms = data.shiftPlan ?? []
    if (direction === 'next') {
        const existing = new Set(days.value.map(d => d.fullDay ?? d.withoutFormat))
        const toAdd = newDays.filter((d: any) => !existing.has(d.fullDay ?? d.withoutFormat))
        days.value = [...days.value, ...toAdd]
    } else {
        const existing = new Set(days.value.map(d => d.fullDay ?? d.withoutFormat))
        const toAdd = newDays.filter((d: any) => !existing.has(d.fullDay ?? d.withoutFormat))
        days.value = [...toAdd, ...days.value]
    }

    const roomsById = new Map<number | string, any>()

    for (const r of rooms.value) {
        const id = r.roomId ?? r.id
        roomsById.set(id, {...r})
    }

    for (const nr of newRooms) {
        const id = nr.roomId ?? nr.id
        const existingRoom = roomsById.get(id)

        if (!existingRoom) {
            roomsById.set(id, nr)
            continue
        }
        if (Array.isArray(existingRoom.days) && Array.isArray(nr.days)) {
            existingRoom.days = [...existingRoom.days, ...nr.days]
        }

        roomsById.set(id, existingRoom)
    }

    rooms.value = Array.from(roomsById.values())
    daysRef.value = days.value
}

const noticeIcon = computed(() => {
    switch (notice.kind) {
        case 'error':
            return 'IconExclamationCircle'
        case 'info':
            return 'IconInfoCircle'
        default:
            return 'IconCircleCheck'
    }
})

const noticeIconClass = computed(() => ({
    'text-green-400': notice.kind === 'success',
    'text-red-400': notice.kind === 'error',
    'text-blue-400': notice.kind === 'info',
}))

const craftsToDisplay = computed(() => {
    const crafts = (props.crafts ?? []).map((craft: any) => ({
        id: craft.id,
        name: craft.name,
        abbreviation: craft.abbreviation,
        users: filterAndSortWorkersOfCraft(craft),
        color: craft?.color,
        universally_applicable: craft.universally_applicable,
    }))
    if (!props.user_filters?.craft_ids || props.user_filters.craft_ids.length === 0) return crafts
    return crafts.filter((c: any) => props.user_filters.craft_ids.includes(c.id))
})

const computedShiftPlanWorkerSortEnums = computed(() => {
    const nameSortEnums = ['ALPHABETICALLY_NAME_ASCENDING', 'ALPHABETICALLY_NAME_DESCENDING']
    const sortConfig: string[] = []
    const sortBy = usePage().props.auth.user.shift_plan_user_sort_by_id

    if (nameSortEnums.includes(sortBy)) {
        sortConfig.push('INTERN_EXTERN_ASCENDING')
        if (sortBy === 'ALPHABETICALLY_NAME_ASCENDING') {
            sortConfig.push('ALPHABETICALLY_NAME_DESCENDING')
        } else {
            sortConfig.push('ALPHABETICALLY_NAME_ASCENDING')
        }
        return sortConfig
    }

    if (sortBy === 'INTERN_EXTERN_ASCENDING') {
        sortConfig.push('INTERN_EXTERN_DESCENDING')
    } else {
        sortConfig.push('INTERN_EXTERN_ASCENDING')
    }
    sortConfig.push('ALPHABETICALLY_NAME_ASCENDING')
    return sortConfig
})

const {getSortEnumTranslation} = useSortEnumTranslation()

async function initializeShiftPlan() {
    const hasInitialDays = Array.isArray(props.days) && props.days.length > 0
    const hasInitialShiftPlan =
        props.shiftPlan && (
            Array.isArray(props.shiftPlan)
                ? props.shiftPlan.length > 0
                : Object.keys(props.shiftPlan).length > 0
        )

    if (!hasInitialDays || !hasInitialShiftPlan) {
        const {data} = await axios.get(route('shift.plan.all'), {
            params: {
                start_date: props.dateValue[0],
                end_date: props.dateValue[1],
                projectId: props.projectId,
            },
        })

        days.value = data.days ?? []
        daysRef.value = days.value
        rooms.value = data.shiftPlan ?? []
        newShiftPlanData.value = data.shiftPlan ?? []

    } else {
        days.value = [...(props.days ?? [])]
        daysRef.value = days.value

        const sp = props.shiftPlan ?? []
        rooms.value = Array.isArray(sp) ? [...sp] : Object.values(sp)
        newShiftPlanData.value = rooms.value
    }
    currentDayOnView.value =
        days.value.find((d: any) => !d.isExtraRow) ?? null

    setCurrentDayRef(currentDayOnView.value)
}

onMounted(async () => {
    await initializeShiftPlan()
    currentDayRef.value = currentDayOnView.value
    watch(
        () => currentDayOnView.value,
        (v) => {
            currentDayRef.value = v
        },
    )
    watch(
        () => props.days,
        (v) => {
            days.value = (v ?? []) as any[]
            daysRef.value = days.value
        },
        {deep: true},
    )

    attach()
    const shiftPlanArray = Array.isArray(newShiftPlanData.value)
        ? newShiftPlanData.value
        : Object.values(newShiftPlanData.value ?? {})

    const shiftPlanDataRef = ref(shiftPlanArray)
    const ShiftCalendarListener = useShiftCalendarListener(shiftPlanDataRef)
    ShiftCalendarListener.init()
    setupInertiaNavigationGuard()
    setTimeout(() => {
        showCalendarWarning.value = ''
    }, 5000)
})


onBeforeUnmount(() => {
    detach()
    monthObserver.value?.disconnect()
    monthObserver.value = null

    if (originalVisit.value) {
        router.visit = originalVisit.value
    }
})


function changeDailyViewMode() {
    router.patch(
        route('user.update.daily_view', usePage().props.auth.user.id),
        {daily_view: dailyViewMode.value},
        {preserveScroll: false, preserveState: false},
    )
}

function getAllProjectGroupsInEventsByDay(events: any[]) {
    const projectGroups: any[] = []
    events.forEach((event: any) => {
        if (event?.project) {
            const project = event.project
            if (project.isGroup) {
                if (!projectGroups.some((g) => g.id === project.id)) {
                    projectGroups.push(project)
                }
            } else if (project.isInGroup && Array.isArray(project.group)) {
                project.group.forEach((group: any) => {
                    if (!projectGroups.some((g) => g.id === group.id)) {
                        projectGroups.push(group)
                    }
                })
            }
        }
    })
    return projectGroups
}

function checkIfUserIsAdminOrInGroup(group: any) {
    if ((getCurrentInstance()?.proxy as any)?.hasAdminRole?.()) return false
    return !group.userIds.includes(usePage().props.auth.user.id)
}

function initializeCalendarMultiEditSave() {
    showAddShiftModal.value = true
}

function closeCellMultiEditCalendarDelete(booleanVal: boolean) {
    if (booleanVal) {
        openCellMultiEditCalendarDelete.value = false
        showAddShiftModal.value = true
    } else {
        openCellMultiEditCalendarDelete.value = false
        multiEditCalendarDays.value = []
    }
}

function openEditShiftModal(shift: any) {
    shiftToEdit.value = shift
    showAddShiftModal.value = true
}

function openAddShiftForRoomAndDay(day: any, roomId: number) {
    shiftToEdit.value = null
    roomForShiftAdd.value = roomId
    dayForShiftAdd.value = day
    showAddShiftModal.value = true
}

function closeAddShiftModal() {
    showAddShiftModal.value = false
    roomForShiftAdd.value = null
    dayForShiftAdd.value = null
    shiftToEdit.value = null
    multiEditCalendarDays.value = []
}

function checkIfRoomAndDayIsInMultiEditCalendar(day: string, roomId: number) {
    return multiEditCalendarDays.value.some((x) => x.day === day && x.roomId === roomId)
}

function addDayAndRoomToMultiEditCalendar(day: string, roomId: number) {
    const exists = multiEditCalendarDays.value.some((x) => x.day === day && x.roomId === roomId)
    if (exists) {
        multiEditCalendarDays.value = multiEditCalendarDays.value.filter((x) => !(x.day === day && x.roomId === roomId))
    } else {
        multiEditCalendarDays.value.push({day, roomId})
    }
}

function setupInertiaNavigationGuard() {
    originalVisit.value = router.visit
    window.onbeforeunload = (event) => {
        if (multiEditMode.value && userForMultiEdit.value && !preventNextNavigation.value) {
            event.preventDefault()
            ;(event as any).returnValue = $t('Would you like to save the changes before you leave the page?')
        }
    }

    router.visit = async (url: string, options: any = {}) => {
        // Standardmäßig Scroll-Position beibehalten, außer explizit überschrieben
        options = {preserveScroll: true, ...options}
        if (multiEditMode.value && userForMultiEdit.value && !preventNextNavigation.value) {
            preventNextNavigation.value = true
            try {
                if (!waitForModalClose.value) {
                    originalVisit.value.call(router, url, options)
                }
            } finally {
                preventNextNavigation.value = false
            }
        } else if (!waitForModalClose.value) {
            originalVisit.value.call(router, url, options)
        }
    }
}

function isWorkerUser(worker: any) {
    return worker.type === 0
}

function isWorkerFreelancer(worker: any) {
    return worker.type === 1
}

function isWorkerServiceProvider(worker: any) {
    return worker.type === 2
}

function isManagingWorker(craft: any, worker: any) {
    if (isWorkerUser(worker)) {
        return craft.managing_users.findIndex((m: any) => m.id === worker.element.id) > -1
    }
    if (isWorkerFreelancer(worker)) {
        return craft.managing_freelancers.findIndex((m: any) => m.id === worker.element.id) > -1
    }
    if (isWorkerServiceProvider(worker)) {
        return craft.managing_service_providers.findIndex((m: any) => m.id === worker.element.id) > -1
    }
    return false
}

function getAssignedWorkerOfCraft(craftId: number, dropWorkers: any[]) {
    return dropWorkers.filter((dw) => dw.assigned_craft_ids.includes(craftId))
}

function sortAscendingByUseFirstNameForSort(workers: any[]) {
    if (!props.useFirstNameForSort) {
        return workers.sort((a, b) => {
            const aName = isWorkerServiceProvider(a) ? a.element.provider_name : a.element.first_name
            const bName = isWorkerServiceProvider(b) ? b.element.provider_name : b.element.first_name
            if (aName < bName) return -1
            if (aName > bName) return 1
            return 0
        })
    }
    return workers.sort((a, b) => {
        const aName = isWorkerServiceProvider(a) ? a.element.provider_name : a.element.last_name
        const bName = isWorkerServiceProvider(b) ? b.element.provider_name : b.element.last_name
        if (aName < bName) return -1
        if (aName > bName) return 1
        return 0
    })
}

function sortDescendingByUseFirstNameForSort(workers: any[]) {
    if (!props.useFirstNameForSort) {
        return workers.sort((a, b) => {
            const aName = isWorkerServiceProvider(a) ? a.element.provider_name : a.element.first_name
            const bName = isWorkerServiceProvider(b) ? b.element.provider_name : b.element.first_name
            if (aName > bName) return -1
            if (aName < bName) return 1
            return 0
        })
    }
    return workers.sort((a, b) => {
        const aName = isWorkerServiceProvider(a) ? a.element.provider_name : a.element.last_name
        const bName = isWorkerServiceProvider(b) ? b.element.provider_name : b.element.last_name
        if (aName > bName) return -1
        if (aName < bName) return 1
        return 0
    })
}

function filterNonManagingWorkersByShiftQualificationFilter(workers: any[]) {
    const filters = props.userShiftPlanShiftQualificationFilters ?? []
    if (filters.length === 0) return workers
    const withQuali = workers.filter((w) => (w.element.shift_qualifications ?? []).length > 0)
    return withQuali.filter((w) =>
        w.element.shift_qualifications.some((sq: any) => filters.includes(sq.id)),
    )
}

function filterAndSortWorkersOfCraft(craft: any) {
    const dropWorkers = getDropWorkers()
    const assignedWorkersOfCraft = getAssignedWorkerOfCraft(craft.id, dropWorkers)
    const assignedManagingWorkers = assignedWorkersOfCraft.filter((x) => isManagingWorker(craft, x))
    const assignedNonManagingWorkers = assignedWorkersOfCraft.filter((x) => !isManagingWorker(craft, x))
    const assignedNonManagingWorkersFiltered = filterNonManagingWorkersByShiftQualificationFilter(assignedNonManagingWorkers)

    const sortBy = usePage().props.auth.user.shift_plan_user_sort_by_id

    if (sortBy === null) {
        return assignedManagingWorkers.concat(assignedNonManagingWorkersFiltered)
    }

    if (sortBy === 'ALPHABETICALLY_NAME_ASCENDING') {
        return sortAscendingByUseFirstNameForSort(assignedManagingWorkers)
            .concat(sortAscendingByUseFirstNameForSort(assignedNonManagingWorkersFiltered))
    }

    if (sortBy === 'ALPHABETICALLY_NAME_DESCENDING') {
        return sortDescendingByUseFirstNameForSort(assignedManagingWorkers)
            .concat(sortDescendingByUseFirstNameForSort(assignedNonManagingWorkersFiltered))
    }

    // INTERN = type=0 UND NICHT is_freelancer
    const intern = assignedNonManagingWorkersFiltered.filter((w) =>
        w.type === 0 && w.element?.is_freelancer !== true
    )

    // EXTERN = Freelancer-User, Freelancer-Models, ServiceProvider
    const extern = assignedNonManagingWorkersFiltered.filter((w) =>
        w.element?.is_freelancer === true || w.type === 1 || w.type === 2
    )

    if (sortBy === 'INTERN_EXTERN_ASCENDING') {
        return sortAscendingByUseFirstNameForSort(assignedManagingWorkers)
            .concat(sortAscendingByUseFirstNameForSort(intern))
            .concat(sortAscendingByUseFirstNameForSort(extern))
    }

    if (sortBy === 'INTERN_EXTERN_DESCENDING') {
        return sortDescendingByUseFirstNameForSort(assignedManagingWorkers)
            .concat(sortDescendingByUseFirstNameForSort(extern))
            .concat(sortDescendingByUseFirstNameForSort(intern))
    }

    return assignedManagingWorkers.concat(assignedNonManagingWorkersFiltered)
}


function getDropWorkers() {
    const users: any[] = []
    ;(props.usersForShifts ?? []).forEach((user: any) => {
        if (!showFreelancers.value && user.user.is_freelancer) return
        users.push({
            element: user.user,
            type: 0,
            workTimeBalance: user.workTimeBalance,
            vacations: user.vacations,
            assigned_craft_ids: user.user.assigned_craft_ids,
            availabilities: user.availabilities,
            weeklyWorkingHours: user.weeklyWorkingHours,
            dayServices: user.dayServices,
            individual_times: user.individual_times,
            shift_comments: user.shift_comments,
        })
    })
    if (showFreelancers.value) {
        ;(props.freelancersForShifts ?? []).forEach((freelancer: any) => {
            users.push({
                element: freelancer.freelancer,
                type: 1,
                vacations: freelancer.vacations,
                assigned_craft_ids: freelancer.freelancer.assigned_craft_ids,
                availabilities: freelancer.availabilities,
                dayServices: freelancer.dayServices,
                individual_times: freelancer.individual_times,
                shift_comments: freelancer.shift_comments,
                weeklyWorkingHours: freelancer.weeklyWorkingHours,
            })
        })
    }
    ;(props.serviceProvidersForShifts ?? []).forEach((sp: any) => {
        users.push({
            element: sp.service_provider,
            type: 2,
            assigned_craft_ids: sp.service_provider.assigned_craft_ids,
            dayServices: sp.dayServices,
            individual_times: sp.individual_times,
            shift_comments: sp.shift_comments,
            weeklyWorkingHours: sp.weeklyWorkingHours,
        })
    })
    return users
}

function handleMultiEdit(user: any, day: any) {
    const key = `${user.element.id}_${user.type}`
    if (!multiEditCellByDayAndUser.value[key]) {
        multiEditCellByDayAndUser.value[key] = {
            days: [],
            type: user.type,
            id: user.element.id,
            entity: user.element,
        }
    }
    const data = multiEditCellByDayAndUser.value[key]
    if (data.days.includes(day.withoutFormat)) {
        data.days = data.days.filter((d: string) => d !== day.withoutFormat)
        if (data.days.length === 0) delete multiEditCellByDayAndUser.value[key]
    } else {
        data.days.push(day.withoutFormat)
    }
}

function openShowUserShiftModal(user: any, day: any) {
    userToShow.value = user
    dayToShow.value = day
    showUserShifts.value = true
}

function handleCellClick(user: any, day: any) {
    if (multiEditMode.value && !userForMultiEdit.value) {
        handleMultiEdit(user, day)
        return
    }
    if (multiEditMode.value && userForMultiEdit.value) {
        return
    }

    if (dayServiceMode.value) {
        let type: 'user' | 'freelancer' | 'service_provider' | null = null
        switch (user.type) {
            case 0:
                type = 'user'
                break
            case 1:
                type = 'freelancer'
                break
            case 2:
                type = 'service_provider'
                break
        }
        const hasDayService = user.dayServices?.[day.withoutFormat]?.some((x: any) => x.id === selectedDayService.value?.id)
        const setWorkerDayServicesCallback = (response: any) => {
            const data = response.data
            const dayServicesRef = data.dayServices
            switch (data.type) {
                case 'user':
                    ;(props.usersForShifts as any[]).find((u) => u.user.id === data.id).dayServices = dayServicesRef
                    break
                case 'freelancer':
                    ;(props.freelancersForShifts as any[]).find((f) => f.freelancer.id === data.id).dayServices = dayServicesRef
                    break
                case 'service_provider':
                    ;(props.serviceProvidersForShifts as any[]).find((s) => s.service_provider.id === data.id).dayServices = dayServicesRef
                    break
            }
        }

        if (hasDayService) {
            axios.patch(route('remove.day.service.from.user', {dayServiceable: user.element.id}), {
                dayService: selectedDayService.value.id, date: day.withoutFormat, modelType: type,
            }).then(setWorkerDayServicesCallback)
        } else {
            axios.post(route('day-service.attach', {
                dayServiceable: user.element.id,
                dayService: selectedDayService.value.id
            }), {
                date: day.withoutFormat, modelType: type,
            }).then(setWorkerDayServicesCallback)
        }
        return
    }

    openShowUserShiftModal(user, day)
}

function updateSelectedDayService(dayService: any) {
    selectedDayService.value = dayService
}

function calculateTopPositionOfUserOverView() {
    return showUserOverview.value ? userOverviewHeight.value + 'px' : '0px'
}

function checkIfEventHasShiftsToDisplay(event: any) {
    const showCrafts = usePage().props.auth.user?.show_crafts
    if (!showCrafts || showCrafts.length === 0) return event.shifts?.length > 0
    return event.shifts?.length > 0 && event.shifts.some((s: any) => showCrafts.includes(s.craft.id))
}

function showDropFeedback(feedback: string) {
    dropFeedback.value = feedback
    setTimeout(() => {
        dropFeedback.value = null
    }, 2000)
}

function openFullscreen() {
    const elem = document.getElementById('shiftPlan') as any
    if (!elem) return
    if (elem.requestFullscreen) {
        elem.requestFullscreen()
        isFullscreen.value = true
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen()
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen()
    }
}

function previousTimeRange() {
    const dateDifference = calculateDateDifference()
    props.dateValue[0] = dayjs(props.dateValue[0]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD')
    props.dateValue[1] = dayjs(props.dateValue[1]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD')
    updateTimes()
    loadMonth('prev')
}

function nextTimeRange() {
    const dateDifference = calculateDateDifference()
    props.dateValue[0] = dayjs(props.dateValue[0]).add(dateDifference + 1, 'day').format('YYYY-MM-DD')
    props.dateValue[1] = dayjs(props.dateValue[1]).add(dateDifference + 1, 'day').format('YYYY-MM-DD')
    updateTimes()
    loadMonth('next')
}

function calculateDateDifference() {
    const date1 = new Date(props.dateValue[0])
    const date2 = new Date(props.dateValue[1])
    const timeDifference = date2.getTime() - date1.getTime()
    return timeDifference / (1000 * 3600 * 24)
}

function updateTimes() {
    router.patch(
        route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
        {start_date: props.dateValue[0], end_date: props.dateValue[1]},
        {preserveScroll: true, preserveState: false},
    )
}

function openHistoryModal() {
    showHistoryModal.value = true
}

function showCloseUserOverview() {
    showUserOverview.value = !showUserOverview.value
}

function selectGoToMode(direction: 'next' | 'previous') {
    const gotoMode = usePage().props.auth.user.goto_mode
    scrollToPeriod(gotoMode, direction)
}

const scrollToPeriod = (period: 'day' | 'week' | 'month', direction: 'next' | 'previous') => {
    const container = shiftPlanEl.value ?? document.getElementById('shiftPlan')
    if (!container || !days.value.length || !currentDayOnView.value) return

    const indexModifier = direction === 'next' ? 1 : -1
    let scrollOffset: number | null = null

    if (period === 'day') {
        const currentIndex = days.value.indexOf(currentDayOnView.value)
        let targetIndex = currentIndex + indexModifier
        while (targetIndex >= 0 && targetIndex < days.value.length) {
            const targetDay = days.value[targetIndex]
            if (!targetDay.isExtraRow) {
                scrollOffset = targetIndex
                break
            }
            targetIndex += indexModifier
        }
    } else {
        const periodKey = period === 'week' ? 'weekNumber' : 'monthNumber'
        const condition = (d: Day) => (period === 'week' ? d.isMonday : d.isFirstDayOfMonth)
        const periodValue = (currentDayOnView.value as any)?.[periodKey]

        let targetIndex = days.value.findIndex(d => (d as any)[periodKey] === periodValue && condition(d))
        while (true) {
            targetIndex += indexModifier
            if (targetIndex < 0 || targetIndex >= days.value.length) break
            const day = days.value[targetIndex]
            if (!day.isExtraRow && condition(day)) {
                scrollOffset = targetIndex
                break
            }
        }
    }

    if (scrollOffset == null) return

    const targetDay: any = days.value[scrollOffset]
    currentDayOnView.value = targetDay

    const targetElement = document.getElementById(targetDay.fullDay)
    if (!targetElement) return

    const roomNameElement = document.getElementById('roomNameContainer_0')
    const containerRect = container.getBoundingClientRect()
    const roomNameLeft = roomNameElement ? roomNameElement.getBoundingClientRect().left : 0

    container.scrollLeft = targetElement.offsetLeft - (roomNameLeft + containerRect.left + 65)
}

function selectGoToNextMode() {
    selectGoToMode('next')
}

function selectGoToPreviousMode() {
    selectGoToMode('previous')
}

function toggleHighlightMode() {
    idToHighlight.value = null
    typeToHighlight.value = null
    multiEditMode.value = false
    dayServiceMode.value = false
}

function toggleMultiEditMode() {
    highlightMode.value = false
    dayServiceMode.value = false
    if (!multiEditMode.value) {
        userForMultiEdit.value = null
        multiEditCellByDayAndUser.value = {}
    }
}

function toggleMultiEditModeCalendar() {
    highlightMode.value = false
    dayServiceMode.value = false
    if (!multiEditModeCalendar.value) {
        multiEditCalendarDays.value = []
    }
}

function closeMultiEditCellModal(eventData: any) {
    showCellMultiEditModal.value = false
    if (eventData && eventData.saved) {
        multiEditCellByDayAndUser.value = {}
        router.reload({
            only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
        })
    }
}

function closeCellMultiEditDelete(booleanVal: any) {
    if (booleanVal?.closing) {
        openCellMultiEditDelete.value = false
        return
    }
    openCellMultiEditDelete.value = false
    if (booleanVal) {
        showCellMultiEditModal.value = true
    } else {
        multiEditCellByDayAndUser.value = {}
    }
}

function toggleDayServiceMode() {
    highlightMode.value = false
    multiEditMode.value = false
}

function toggleCompactMode() {
    router.post(
        route('user.compact.mode.toggle', {user: usePage().props.auth.user.id}),
        {compact_mode: usePage().props.auth.user.compact_mode},
        {preserveScroll: true, preserveState: true},
    )
}

function highlightShiftsOfUser(id: number | string, type: number) {
    idToHighlight.value = id
    typeToHighlight.value = type
}

function addUserToMultiEdit(item: any) {
    _cancelOpenPickersAndModals()
    multiEditSessionId.value++
    saveQueue.value = Promise.resolve()
    userToMultiEditCheckedShiftsAndEvents.value = []
    savingShiftIds.value = new Set()
    showShiftsQualificationsAssignmentModalShifts.value = []
    showShiftsQualificationsAssignmentModal.value = false
    waitForModalClose.value = false

    userForMultiEdit.value = item
    const ids = Array.isArray(item?.shift_ids) ? [...item.shift_ids] : []
    userForMultiEdit.value.shift_ids = ids
    userToMultiEditCurrentShifts.value = [...ids]
    if (Array.isArray(item?.shift_qualifications)) {
        userForMultiEdit.value.shift_qualifications = [...item.shift_qualifications]
    }
}

function _cancelOpenPickersAndModals() {
    if (resolveModalClose.value) {
        try {
            resolveModalClose.value()
        } catch {
        }
        resolveModalClose.value = null
    }
    if (_singlePickResolve.value) {
        try {
            _singlePickResolve.value(null)
        } catch {
        }
        _singlePickResolve.value = null
        _singlePickShiftId.value = null
    }
    showShiftsQualificationsAssignmentModal.value = false
    showShiftsQualificationsAssignmentModalShifts.value = []
    waitForModalClose.value = false
}

async function closeShiftsQualificationsAssignmentModal(closedForAssignment: boolean, assignedShifts: any[]) {
    showShiftsQualificationsAssignmentModal.value = false

    const resolver = resolveModalClose.value
    const singleResolver = _singlePickResolve.value
    const singleShiftId = _singlePickShiftId.value

    resolveModalClose.value = null
    _singlePickResolve.value = null
    _singlePickShiftId.value = null
    showShiftsQualificationsAssignmentModalShifts.value = []

    try {
        if (!closedForAssignment) {
            if (singleResolver) {
                singleResolver(null)
                return
            }
            resolver?.()
            return
        }

        if (singleResolver) {
            const picked = (assignedShifts ?? []).find((s: any) => s.shiftId === singleShiftId)
            singleResolver(picked ? {id: picked.shiftQualificationId} : null)
            return
        }

        ;(assignedShifts ?? []).forEach((s: any) => {
            shiftsToHandleOnMultiEdit.assignToShift.push({
                shiftId: s.shiftId,
                shiftQualificationId: s.shiftQualificationId
            })
        })

        await saveMultiEdit()
    } finally {
        waitForModalClose.value = false
        resolver?.()
    }
}

async function saveMultiEdit() {
    if (shiftsToHandleOnMultiEdit.assignToShift.length === 0 && shiftsToHandleOnMultiEdit.removeFromShift.length === 0) {
        resetMultiEditMode()
        return
    }
    await axios.post(route('shift.multi.edit.save'), {
        userType: userForMultiEdit.value.type,
        userTypeId: userForMultiEdit.value.id,
        craft_abbreviation: userForMultiEdit.value.craft_abbreviation,
        shiftsToHandle: shiftsToHandleOnMultiEdit,
    })
    resetMultiEditMode(false)
}

function resetMultiEditMode(closeMultiEdit = true) {
    shiftsToHandleOnMultiEdit.assignToShift = []
    shiftsToHandleOnMultiEdit.removeFromShift = []
    userToMultiEditCheckedShiftsAndEvents.value = []
    if (userForMultiEdit.value && !closeMultiEdit) {
        userToMultiEditCurrentShifts.value = [...(userForMultiEdit.value.shift_ids ?? [])]
    }
    if (closeMultiEdit) {
        multiEditMode.value = false
        multiEditCellByDayAndUser.value = {}
    }
}

function changeCraftVisibility(id: number) {
    if (closedCrafts.value.includes(id)) {
        closedCrafts.value.splice(closedCrafts.value.indexOf(id), 1)
    } else {
        closedCrafts.value.push(id)
    }
}

function applySort(shiftPlanWorkerSortEnumName: string) {
    usePage().props.auth.user.shift_plan_user_sort_by_id = shiftPlanWorkerSortEnumName
    router.patch(
        route('user.update.shiftPlanUserSortBy', {user: usePage().props.auth.user.id}),
        {sortBy: shiftPlanWorkerSortEnumName},
        {preserveState: true, preserveScroll: true},
    )
}

function resetSort() {
    usePage().props.auth.user.shift_plan_user_sort_by_id = null
    router.patch(
        route('user.update.shiftPlanUserSortBy', {user: usePage().props.auth.user.id}),
        {sortBy: null},
        {preserveState: false, preserveScroll: true},
    )
}

function handleShiftAndEventForMultiEdit(checked: boolean, shift: any, event: any) {
    onToggleShift(checked, shift, event)
}

function onToggleShift(checked: boolean, shift: any, event: any) {
    if (!userForMultiEdit.value) return
    if (isSaving(shift.id)) return

    const oldIds = new Set<number>(userForMultiEdit.value.shift_ids ?? [])
    const alreadyAssigned = oldIds.has(shift.id)
    if ((checked && alreadyAssigned) || (!checked && !alreadyAssigned)) return

    savingShiftIds.value.add(shift.id)

    if (checked) {
        const optimistic = new Set(oldIds)
        optimistic.add(shift.id)
        userForMultiEdit.value.shift_ids = Array.from(optimistic)

        enqueueSave(async () => {
            const needsQuali = (shift.shifts_qualifications ?? []).length > 0
            let qualificationId: number | null = null

            if (needsQuali) {
                qualificationId = await resolveQualificationFor(shift)
                if (!qualificationId) {
                    userForMultiEdit.value.shift_ids = Array.from(oldIds)
                    const msg = $t('No matching qualification for this shift')
                    $toast?.error?.(msg)
                    showNotice('error', 'Qualification required', 'This user does not have a matching qualification for this shift.')
                    throw new Error('no_qualification')
                }
            }

            await persistAssign(shift.id, qualificationId)
            showNotice('success', 'Assigned', 'The user was successfully added to the shift.')
        })
            .catch((err) => {
                userForMultiEdit.value.shift_ids = Array.from(oldIds)
                if (err?.message !== 'no_qualification') {
                    $toast?.error?.($t('Saving failed'))
                    showNotice('error', 'Save failed', 'Something went wrong while saving. Please try again.')
                    console.error(err)
                }
            })
            .finally(() => {
                savingShiftIds.value.delete(shift.id)
            })
    } else {
        const optimistic = new Set(oldIds)
        optimistic.delete(shift.id)
        userForMultiEdit.value.shift_ids = Array.from(optimistic)

        enqueueSave(async () => {
            await persistRemove(shift.id)
            showNotice('success', 'Removed', 'The user was successfully removed from the shift.')
        })
            .catch((err) => {
                userForMultiEdit.value.shift_ids = Array.from(oldIds)
                $toast?.error?.($t('Saving failed'))
                showNotice('error', 'Save failed', 'Something went wrong while saving. Please try again.')
                console.error(err)
            })
            .finally(() => {
                savingShiftIds.value.delete(shift.id)
            })
    }
}

async function resolveQualificationFor(desiredShift: any) {
    const userQualis = userForMultiEdit.value?.shift_qualifications ?? []
    const required = desiredShift.shifts_qualifications ?? []
    if (required.length === 0) return null
    const shiftCraftId =
        desiredShift.craft_id ??
        desiredShift.craftId ??
        desiredShift.craft?.id ??
        null
    const available = userQualis.filter((uq: any) => {
        const matchesQualification = required.some(
            (req: any) => req.shift_qualification_id === uq.id,
        )

        if (!matchesQualification) return false
        if (!shiftCraftId) return true

        const uqCraftId = uq.pivot?.craft_id ?? null
        if (uqCraftId === null) return true
        return uqCraftId === shiftCraftId
    })

    if (available.length === 0) return null
    if (available.length === 1) return available[0].id
    if (openQualificationPicker) {
        const picked = await openQualificationPicker(desiredShift, available)
        return picked?.id ?? null
    }
    return available[0].id
}


function openQualificationPicker(desiredShift: any, options: any[]) {
    if (waitForModalClose.value) return Promise.resolve(null)

    return new Promise((resolve) => {
        _singlePickResolve.value = resolve
        _singlePickShiftId.value = desiredShift.id
        showShiftsQualificationsAssignmentModalShifts.value = [{shift: desiredShift, availableSlots: options}]
        showShiftsQualificationsAssignmentModal.value = true
        waitForModalClose.value = true
    })
}

async function persistAssign(shiftId: number, shiftQualificationId: number | null) {
    const payload = {
        userType: userForMultiEdit.value.type,
        userTypeId: userForMultiEdit.value.id,
        craft_abbreviation: userForMultiEdit.value.craft_abbreviation,
        shiftsToHandle: {
            assignToShift: [
                ...(shiftQualificationId != null ? [{shiftId, shiftQualificationId}] : [{shiftId}]),
            ],
            removeFromShift: [],
        },
    }
    return axios.post(route('shift.multi.edit.save'), payload)
}

async function persistRemove(shiftId: number) {
    return axios.post(route('shift.multi.edit.save'), {
        userType: userForMultiEdit.value.type,
        userTypeId: userForMultiEdit.value.id,
        craft_abbreviation: userForMultiEdit.value.craft_abbreviation,
        shiftsToHandle: {assignToShift: [], removeFromShift: [shiftId]},
    })
}

function enqueueSave(taskFn: () => Promise<any>) {
    const session = multiEditSessionId.value
    saveQueue.value = saveQueue.value
        .then(() => {
            if (session !== multiEditSessionId.value) return
            return taskFn()
        })
        .catch((e) => {
            if (session !== multiEditSessionId.value) return
            return taskFn()
        })
    return saveQueue.value
}

function isSaving(shiftId: number) {
    return savingShiftIds.value.has(shiftId)
}

function showNotice(kind: 'success' | 'error' | 'info', title: string, message: string, {timeout = 3500} = {}) {
    if (notice._timeoutId) clearTimeout(notice._timeoutId)
    notice.kind = kind
    notice.title = $t(title)
    notice.message = $t(message)
    notice.show = true
    notice._timeoutId = setTimeout(() => {
        notice.show = false
    }, timeout)
}

function hideNotice() {
    if (notice._timeoutId) clearTimeout(notice._timeoutId)
    notice.show = false
    notice._timeoutId = null
}

function saveShiftQualificationFilter(event: any) {
    const isChecked = event.target.checked
    const shiftQualificationId = Number.parseInt(event.target.value)
    const list = props.userShiftPlanShiftQualificationFilters as number[]

    if (!isChecked) {
        const idx = list.findIndex((id) => id === shiftQualificationId)
        if (idx >= 0) list.splice(idx, 1)
    } else {
        list.push(shiftQualificationId)
    }

    router.patch(
        route('user.update.show_shift-qualifications', {user: usePage().props.auth.user.id}),
        {show_qualifications: list},
        {preserveScroll: true, preserveState: true},
    )
}

function handleSeriesUpdated() {
    showIndividualTimeSeriesModal.value = false;
    router.reload({
        only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
    });
}

</script>


<style scoped>
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
    z-index: 12;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 12;
}
</style>

