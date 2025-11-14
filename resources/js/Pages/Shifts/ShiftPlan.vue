<template>
    <div class="w-full flex flex-col">
        <ShiftHeader>
            <div aria-live="assertive" class="pointer-events-none fixed z-100 inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6">
                <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
                    <transition
                        enter-active-class="transform ease-out duration-300 transition"
                        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                        enter-to-class="translate-y-0 sm:translate-x-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class=""
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="notice.show"
                            class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline-1 outline-black/5 dark:bg-gray-800 dark:-outline-offset-1 dark:outline-white/10"
                            role="status"
                        >
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="shrink-0">
                                        <PropertyIcon :name="noticeIcon" class="size-6" :class="noticeIconClass" aria-hidden="true" />
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
                                        <button
                                            type="button"
                                            @click="hideNotice"
                                            class="inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-indigo-600 dark:hover:text-white dark:focus:outline-indigo-500"
                                        >
                                            <span class="sr-only">Close</span>
                                            <IconX class="size-5" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
            <transition name="fade" appear>
                <div class="pointer-events-none fixed z-100 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="showCalendarWarning.length > 0">
                    <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                        <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                        <p class="text-sm/6 text-white">
                            {{ showCalendarWarning }}
                        </p>
                        <button type="button" class="-m-1.5 flex-none p-1.5">
                            <span class="sr-only">Dismiss</span>
                            <component :is="IconX" class="size-5 text-white" aria-hidden="true" @click="showCalendarWarning = ''" />
                        </button>
                    </div>
                </div>
            </transition>
            <div class=" bg-white flex-grow">
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
                                      :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                                      :firstProjectShiftTabId="firstProjectShiftTabId"
                                      @select-go-to-next-mode="selectGoToNextMode"
                                      @select-go-to-previous-mode="selectGoToPreviousMode"
                                      :event-types="eventTypes"
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
                                                'cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create/90',
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
                                                'cursor-pointer bg-artwork-error hover:bg-artwork-error/90',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                    {{ $t('Delete') }}
                                </button>
                            </div>
                        </div>
                    </template>
                    <template #moreButtons>
                        <SwitchIconTooltip
                            v-model="dailyViewMode"
                            :tooltip-text="$t('Daily view')"
                            size="md"
                            @change="changeDailyViewMode"
                            :icon="IconCalendarWeek"
                        />

                        <SwitchIconTooltip
                            v-model="multiEditModeCalendar"
                            :tooltip-text="$t('Edit')"
                            size="md"
                            @change="toggleMultiEditModeCalendar"
                            :icon="IconPencil"
                        />
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
                                         class="z-20 h-8 py-2 px-[1px] border-r-2 border-artwork-navigation-background truncate text-white">
                                        <div v-if="day.isExtraRow" :style="{width:  '200px'}">
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
                            <TableBody class="eventByDaysContainer">
                                <tr v-for="(room, index) in newShiftPlanData" :key="room.roomId" class="w-full table-row divide-x divide-gray-300"
                                    :class="[$page.props.auth.user.calendar_settings.expand_days ? 'h-full' : 'h-28']">
                                    <th :id="'roomNameContainer_' + index"
                                        class="xsDark w-48 table-cell align-middle"
                                        :class="[index % 2 === 0 ? 'bg-background-gray' : 'bg-secondary-hover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <div class="flex font-semibold items-center ml-4">
                                            {{ room.roomName }}
                                        </div>
                                    </th>
                                    <td :class="[day.isWeekend ? 'bg-backgroundGray' : 'bg-white', day.isSunday ? '' : '', multiEditModeCalendar ? '' : '', $page.props.auth.user.calendar_settings.expand_days ? '' : 'h-28']"
                                        class="border-gray-400 day-container relative table-cell align-top px-[1px]"
                                        v-for="day in days" :data-day="day.fullDay">
                                        <div
                                            v-if="!day.isExtraRow && multiEditModeCalendar"
                                            :class="[multiEditModeCalendar && !checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ?
                                            'bg-gray-950 opacity-30 hover:bg-opacity-0 hover:border-opacity-100 hover:border-2 border-dashed transition-all duration-150 ease-in-out cursor-pointer border-artwork-buttons-create' : '',
                                            checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ? 'border' : '']"
                                            class="absolute w-full h-full"
                                            @click="addDayAndRoomToMultiEditCalendar(day.fullDay, room.roomId)">
                                        </div>
                                        <div class="bg-background-gray2 h-full min-w-full mb-3 border-l-2 border-gray-800" v-if="day.isExtraRow" :style="{width: '202px', maxWidth: '202px'}">
                                        </div>
                                        <!-- Build in v-if="this.currentDaysInView.has(day.full_day)" when observer fixed -->
                                        <div v-else style="width: 200px" class="cell group " :class="$page.props.auth.user.calendar_settings.expand_days ? 'min-h-12' : 'max-h-28 h-28 overflow-y-auto'">
                                            <div v-if="usePage().props.auth.user.calendar_settings.display_project_groups && room.content[day.fullDay]?.events" v-for="group in getAllProjectGroupsInEventsByDay(room.content[day.fullDay].events)" :key="group.id">
                                                <Link :disabled="checkIfUserIsAdminOrInGroup(group)" :href="route('projects.tab', { project: group.id, projectTab: firstProjectShiftTabId })"  class="bg-artwork-navigation-background text-white text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1">
                                                    <component :is="group.icon" class="size-4" aria-hidden="true"/>
                                                    <span>{{ group.name }}</span>
                                                </Link>
                                            </div>
                                            <div v-if="room.content[day.fullDay]?.events" v-for="event in room.content[day.fullDay].events" class="mb-1"  >
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
                                                <div v-if="room.content[day.fullDay]?.shifts" v-for="shift in room.content[day.fullDay].shifts">
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
                                                    :icon="IconPlus"
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
                            <component :is="IconChevronsDown" class="h-4 w-4 text-gray-300"/>
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
                                <SwitchIconTooltip
                                    v-model="multiEditMode"
                                    :tooltip-text="$t('Edit')"
                                    size="md"
                                    @change="toggleMultiEditMode"
                                    :icon="IconPencil"
                                />
                                <div class="flex items-center gap-x-2" v-if="dayServices && selectedDayService">
                                    <SwitchIconTooltip
                                        v-model="dayServiceMode"
                                        :tooltip-text="$t('Day Services')"
                                        size="md"
                                        @change="toggleDayServiceMode"
                                        :icon="selectedDayService?.icon"
                                    />
                                    <DayServiceFilter :current-selected-day-service="selectedDayService"
                                                      :day-services="dayServices"
                                                      @update:current-selected-day-service="updateSelectedDayService"/>
                                </div>
                            </div>
                            <div class="pointer-events-none -mt-1" v-if="multiEditMode">
                                <!--<div v-if="">
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
                                </div>-->
                                <div v-if="Object.keys(multiEditCellByDayAndUser).length !== 0" class="flex items-center justify-center gap-3">
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
                                                'cursor-pointer bg-artwork-error hover:bg-artwork-error/90',
                                                'rounded-md px-14 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create'
                                            ]">
                                        {{ $t('Delete Entries') }}
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-x-3 pr-24 z-20">
                                <SwitchIconTooltip
                                    v-model="highlightMode"
                                    :tooltip-text="$t('Highlight')"
                                    size="md"
                                    @change="toggleHighlightMode"
                                    :icon="IconBulb"
                                />
                                <SwitchIconTooltip
                                    v-model="$page.props.auth.user.compact_mode"
                                    :tooltip-text="$t('Compact view')"
                                    size="md"
                                    @change="toggleCompactMode"
                                    :icon="!$page.props.auth.user.compact_mode ? 'IconTextDecrease' : 'IconTextIncrease'"
                                />
                                <BaseFilter :whiteIcon="false" :onlyIcon="true">
                                    <div class="mx-auto w-full max-w-md max-h-44 rounded-2xl border-none mt-2 pb-3">
                                        <div class="relative flex items-start mb-2">
                                            <div class="flex h-6 items-center">
                                                <input id="showFreelancers" v-model="showFreelancers" aria-describedby="comments-description" name="comments" type="checkbox" class="input-checklist" />
                                            </div>
                                            <div class="ml-2 text-sm leading-6">
                                                <label for="showFreelancers" class="font-medium text-white">{{ $t('Show freelancer') }}</label>
                                            </div>
                                        </div>
                                        <CraftFilter :crafts="crafts" :filtered-craft-ids="user_filters.craft_ids" is_tiny/>
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
                                <BaseMenu show-sort-icon dots-size="size-6" menu-width="w-fit" right classesButton="ui-button hover:!bg-artwork-navigation-color/10 text-artwork-buttons-context">
                                    <div class="flex items-center justify-end py-1">
                                    <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="this.resetSort()">
                                        {{ $t('Reset') }}
                                    </span>
                                    </div>
                                    <MenuItem v-for="computedShiftPlanWorkerSortEnum in computedShiftPlanWorkerSortEnums" v-slot="{ active }">
                                        <div @click="this.applySort(computedShiftPlanWorkerSortEnum)" :class="[active ? 'text-gray-500' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_ASCENDING'">
                                                    <span :class="this.$page.props.auth.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                        {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                    </span>
                                                <IconArrowUp class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'INTERN_EXTERN_DESCENDING'">
                                                    <span :class="this.$page.props.auth.user.shift_plan_user_sort_by_id === computedShiftPlanWorkerSortEnum ? 'text-white' : ''">
                                                        {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum) }}
                                                    </span>
                                                <IconArrowDown class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_ASCENDING'">
                                                {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!this.useFirstNameForSort ? $t('First name') : $t('Last name')]) }}
                                                <IconArrowUp class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING'" class="w-5 h-5"/>
                                            </template>
                                            <template v-if="computedShiftPlanWorkerSortEnum === 'ALPHABETICALLY_NAME_DESCENDING'">
                                                {{ getSortEnumTranslation(computedShiftPlanWorkerSortEnum, [!this.useFirstNameForSort ? $t('First name') : $t('Last name')]) }}
                                                <IconArrowDown class="w-5 h-5"/>
                                                <IconCheck v-if="this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING'" class="w-5 h-5"/>
                                            </template>
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>
                        <div class="pt-20 z-10">
                            <table class="w-full text-white overflow-y-scroll z-10">
                                <div class="z-10">
                                    <tbody class="w-full pt-3" v-for="craft in craftsToDisplay">
                                    <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 xsLight !font-lexend flex justify-between pb-1"
                                        @click="changeCraftVisibility(craft.id)">
                                        {{ craft.name }}
                                        <ChevronDownIcon
                                            :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4 mt-0.5"
                                        />
                                    </tr>
                                    <tr v-if="!closedCrafts.includes(craft.id)" v-for="(user,index) in craft.users"
                                        class="w-full flex">
                                        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right pb-[1px]"
                                            :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                            <DragElement v-if="!highlightMode && !multiEditMode"
                                                         :item="user.element"
                                                         :work-time-balance="user.workTimeBalance"
                                                         :type="user.type"
                                                         :color="craft.color"
                                                         :craft="craft"
                                                         :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                            <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
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
                                            <HighlightUserCell v-else
                                                               :highlighted-user="idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight  : false"
                                                               :item="user.element"
                                                               :work-time-balance="user.workTimeBalance"
                                                               :type="user.type"
                                                               @highlightShiftsOfUser="highlightShiftsOfUser"
                                                               :color="craft.color"
                                                               :is-managing-craft="user.element.managing_craft_ids.includes(craft.id)"
                                            />
                                        </th>
                                        <td v-for="day in days" class="flex pr-[1px] relative pb-[1px]">
                                            <div v-if="!day.isExtraRow" :class="[
                                                    highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    $page.props.auth.user.compact_mode ? 'h-8 max-h-8' : 'h-12 max-h-12',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && craft.id === userForMultiEdit.craftId ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    multiEditMode && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-100 !overflow-hidden' : ''
                                                ]"
                                                 class=""
                                                 :style="{width: '202px', maxWidth: '202px'}"
                                                 @click="handleCellClick(user, day)">
                                                <ShiftPlanCell :user="user" :day="day" :classes="[multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-20' : '']"/>
                                            </div>
                                            <div v-else
                                                 class="p-2 bg-gray-50/30 text-center flex items-center justify-center text-white h-full rounded-lg shiftCell cursor-default overflow-hidden"
                                                 :style="{width: '202px', maxWidth: '202px'}"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.auth.user.compact_mode ? 'h-8 max-h-8' : '',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && craft.id === userForMultiEdit.craftId ? '' : 'opacity-30' : 'opacity-30' : '']">
                                                <div :class="$page.props.auth.user.compact_mode ? 'flex items-center gap-x-1' : ''">
                                                    <div class="text-[9px]">
                                                        Arbeitszeit KW {{ day.weekNumber }}
                                                    </div>
                                                    <div class="font-lexend text-xs" :class="user?.weeklyWorkingHours[day.weekNumber]?.isMinus ? 'text-red-100' : 'text-green-100'">
                                                        {{ user?.weeklyWorkingHours[day.weekNumber]?.difference }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.withoutFormat"
                                                     v-for="(userDayService, position) in userDayServices"
                                                     class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center"
                                                     :class="position > 0 ? '-ml-3' : ''">
                                                    <ToolTipComponent
                                                        :tooltip-text="userDayService.name"
                                                        :icon="userDayService.icon"
                                                        icon-size="h-4 w-4"
                                                        :icon-style="{ color: userDayService.hex_color }"
                                                        :classes-button="'mt-0'"
                                                    />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    <tr class="stickyYAxisNoMarginLeft cursor-pointer w-48 !font-lexend xsLight flex justify-between pb-1"
                                        @click="changeCraftVisibility('noCraft')">
                                        {{ $t('Without craft assignment') }}
                                        <ChevronDownIcon
                                            :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4 mt-0.5"
                                        />
                                    </tr>
                                    <tr v-if="!closedCrafts.includes('noCraft')"
                                        v-for="(user,index) in workersWithoutCraft" class="w-full flex">
                                        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right pb-[1px]"
                                            :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
                                            <DragElement v-if="!highlightMode && !multiEditMode"
                                                         :item="user.element"
                                                         :work-time-balance="user.workTimeBalance"
                                                         :type="user.type"
                                                         :color="null"
                                                         :craft="null"
                                            />
                                            <MultiEditUserCell v-else-if="multiEditMode && !highlightMode"
                                                               :item="user.element"
                                                               :work-time-balance="user.workTimeBalance"
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
                                                               :work-time-balance="user.workTimeBalance"
                                                               :type="user.type"
                                                               @highlightShiftsOfUser="highlightShiftsOfUser"
                                                               :color="null"/>
                                        </th>
                                        <td v-for="day in days" class="flex pr-[1px] relative pb-[1px]">
                                            <div v-if="!day.isExtraRow"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.auth.user.compact_mode ? 'h-8 max-h-8' : 'h-12 max-h-12',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && userForMultiEdit.craftId === 0 ? '' : 'opacity-30' : 'opacity-30' : '',
                                                    multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-100 !overflow-hidden' : '',
                                                    multiEditMode ? '!overflow-hidden' : '']"
                                                 class=""
                                                 @click="handleCellClick(user, day)"
                                                 :style="{width: '202px', maxWidth: '202px'}">
                                                <ShiftPlanCell :user="user" :day="day" :classes="[multiEditMode &&  multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.type === user.type && multiEditCellByDayAndUser[user.element.id + '_' + user.type]?.days.includes(day.withoutFormat) ? '!opacity-20' : '']"/>
                                            </div>
                                            <div v-else class="p-2 bg-gray-50/30 text-center flex items-center justify-center text-white h-full rounded-lg shiftCell cursor-default overflow-hidden" :style="{width: '202px', maxWidth: '202px', maxHeight: '50px'}"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.auth.user.compact_mode ? 'h-8' : '',
                                                    multiEditMode ? userForMultiEdit ? userForMultiEdit.id === user.element.id && user.type === userForMultiEdit.type && userForMultiEdit.craftId === 0 ? '' : 'opacity-30' : 'opacity-30' : '']">
                                                <div :class="$page.props.auth.user.compact_mode ? 'flex items-center gap-x-1' : ''">
                                                    <div class="text-[9px]">
                                                        Arbeitszeit KW {{ day.weekNumber }}
                                                    </div>
                                                    <div class="font-lexend text-xs" :class="user?.weeklyWorkingHours[day.weekNumber]?.isMinus ? 'text-red-100' : 'text-green-100'">
                                                        {{ user?.weeklyWorkingHours[day.weekNumber]?.difference }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.withoutFormat"
                                                     v-for="(userDayService, position) in userDayServices"
                                                     class="rounded-full h-6 w-6 bg-white p-0.5 flex items-center justify-center"
                                                     :class="position > 0 ? '-ml-3' : ''">
                                                    <ToolTipComponent
                                                        :tooltip-text="userDayService.name"
                                                        :icon="userDayService.icon"
                                                        icon-size="h-4 w-4"
                                                        :icon-style="{ color: userDayService.hex_color }"
                                                        :classes-button="'mt-0'"
                                                    />
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
                :shift-qualifications="shiftQualifications"
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
<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import Permissions from "@/Mixins/Permissions.vue";
import {ChevronDownIcon, LightBulbIcon} from "@heroicons/vue/outline";
import SingleShiftPlanEvent from "@/Layouts/Components/ShiftPlanComponents/SingleShiftPlanEvent.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import SingleCalendarEvent from "@/Layouts/Components/SingleCalendarEvent.vue";
import CalendarFunctionBar from "@/Layouts/Components/CalendarFunctionBar.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import ShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue";
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import ShiftHistoryModal from "@/Pages/Shifts/Components/ShiftHistoryModal.vue";
import ShowUserShiftsModal from "@/Pages/Shifts/Components/ShowUserShiftsModal.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import HighlightUserCell from "@/Pages/Shifts/Components/HighlightUserCell.vue";
import {MenuItem, Switch} from "@headlessui/vue";
import MultiEditUserCell from "@/Pages/Shifts/Components/MultiEditUserCell.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import Table from "@/Components/Table/Table.vue";
import TableHead from "@/Components/Table/TableHead.vue";
import TableBody from "@/Components/Table/TableBody.vue";
import {SelectorIcon} from "@heroicons/vue/solid";
import ShiftsQualificationsAssignmentModal
    from "@/Layouts/Components/ShiftPlanComponents/ShiftsQualificationsAssignmentModal.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {
    IconAlertSquareRounded,
    IconArrowDown,
    IconArrowUp, IconBulb, IconCalendarWeek,
    IconChevronDown, IconChevronsDown,
    IconFileText, IconGeometry, IconList,
    IconPencil, IconPlus,
    IconX
} from "@tabler/icons-vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import SingleEventInShiftPlan from "@/Pages/Shifts/Components/SingleEventInShiftPlan.vue";
import IconLib from "@/Mixins/IconLib.vue";
import DayServiceFilter from "@/Components/Filter/DayServiceFilter.vue";
import {ref} from "vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {useSortEnumTranslation} from "@/Composeables/SortEnumTranslation.js";
import dayjs from "dayjs";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ShiftPlanCell from "@/Pages/Shifts/Components/ShiftPlanCell.vue";
import debounce from "lodash.debounce";
import CellMultiEditModal from "@/Pages/Shifts/Components/CellMultiEditModal.vue";
import DeleteEntriesModal from "@/Pages/Shifts/Components/DeleteEntriesModal.vue";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import NewShiftWithoutEventModal
    from "@/Pages/Shifts/Components/ShiftWithoutEventComponents/NewShiftWithoutEventModal.vue";
import SingleShiftInRoom from "@/Pages/Shifts/Components/ShiftWithoutEventComponents/SingleShiftInRoom.vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import DeleteCalendarMultiEditEntities from "@/Pages/Shifts/Components/DeleteCalendarMultiEditEntities.vue";
import DeleteCalendarRoomShiftEntriesModal from "@/Pages/Shifts/Components/DeleteCalendarRoomShiftEntriesModal.vue";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ShiftCommitDateSelectModal from "@/Pages/Shifts/Components/ShiftCommitDateSelectModal.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const {getSortEnumTranslation} = useSortEnumTranslation();

const HEADER_H  = 100;   // Höhe des Headers/Topbars o.ä.
const TOP_GAP   = 16;    // Fester Abstand nach oben (bleibt immer erhalten)
const MIN_TOP   = 100;   // Mindesthöhe des oberen Paneels
const MIN_MAIN  = 200;   // Mindesthöhe des Hauptbereichs unten

export default {
    name: "ShiftPlan",
    mixins: [Permissions, IconLib],
    components: {
        PropertyIcon,
        SwitchIconTooltip,
        ShiftCommitDateSelectModal,
        BaseMenuItem,
        DeleteCalendarRoomShiftEntriesModal,
        DeleteCalendarMultiEditEntities,
        AddShiftModal,
        SingleShiftInRoom,
        NewShiftWithoutEventModal,
        HolidayToolTip,
        DeleteEntriesModal,
        CellMultiEditModal,
        ShiftPlanCell,
        ToolTipComponent,
        MenuItem,
        BaseMenu,
        DayServiceFilter,
        IconPencil,
        SingleEventInShiftPlan,
        CraftFilter,
        IconChevronDown,
        IconX,
        IconFileText,
        BaseFilter,
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
        Link,
        CalendarFunctionBar,
        SingleCalendarEvent,
        EventComponent,
        SingleShiftPlanEvent,
        LightBulbIcon,
        AppLayout,
        ShiftPlanFunctionBar,
        HighlightUserCell,
        SelectorIcon,
        IconArrowDown,
        IconArrowUp
    },
    props: [
        'events',
        'projects',
        'shiftPlan',
        'days',
        'dateValue',
        'filterOptions',
        'user_filters',
        'personalFilters',
        'selectedDate',
        'history',
        'usersForShifts',
        'freelancersForShifts',
        'serviceProvidersForShifts',
        'crafts',
        'shiftQualifications',
        'dayServices',
        'firstProjectShiftTabId',
        'shiftPlanWorkerSortEnums',
        'useFirstNameForSort',
        'userShiftPlanShiftQualificationFilters',
        'projectNameUsedForProjectTimePeriod',
        'calendarWarningText',
        'eventTypes'
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
            userForMultiEdit: null,
            userToMultiEditCurrentShifts: [],
            userToMultiEditCheckedShiftsAndEvents: [],
            dropFeedback: null,
            closedCrafts: [],
            userOverviewHeight: usePage().props.auth.user.drawer_height,
            startY: 0,
            startHeight: 0,
            windowHeight: window.innerHeight,
            shiftsToHandleOnMultiEdit: {
                assignToShift: [],
                removeFromShift: []
            },
            firstDayPosition: this.days ? this.days[this.days.findIndex((day) => !day.isExtraRow)] : null,
            currentDayOnView:  this.days ? this.days[this.days.findIndex((day) => !day.isExtraRow)] : null,
            currentDaysInView: new Set(),
            shiftPlanRef: ref(JSON.parse(JSON.stringify(this.shiftPlan))),
            screenHeight: screen.height,
            showFreelancers: true,
            multiEditCellByDayAndUser: {},
            showCellMultiEditModal: false,
            openCellMultiEditDelete: false,
            preventNextNavigation: false,
            navigationGuardActive: true,
            originalVisit: null,
            showShiftQualificationFilter: false,
            multiEditModeCalendar: false,
            multiEditCalendarDays: [],
            dayForShiftAdd: null,
            roomForShiftAdd: null,
            showAddShiftModal: false,
            shiftToEdit: null,
            newShiftPlanData: ref(this.shiftPlan),
            openCellMultiEditCalendarDelete: false,
            dailyViewMode: usePage().props.auth.user.daily_view ?? false,
            showCalendarWarning: ref(this.calendarWarningText),
            _heightRatio: 0.4,        // Verhältnis topPane/available (initialer Guess)
            _resizingHandler: null,
            _stopHandler: null,
            _resizeObs: null,
            saveQueue: Promise.resolve(),
            savingShiftIds: new Set(),
            showShiftsQualificationsAssignmentModal: false,
            showShiftsQualificationsAssignmentModalShifts: [],
            waitForModalClose: false,
            resolveModalClose: null,

            // Single-Pick (Variante A oder B)
            _singlePickResolve: null,
            _singlePickShiftId: null,

            // Session-Abgrenzung gegen Stale-Tasks
            multiEditSessionId: 0,
            notice: {
                show: false,
                kind: 'success', // 'success' | 'error' | 'info'
                title: '',
                message: '',
                _timeoutId: null,
            },
        }
    },
    mounted() {
        // Convert the Proxy object to a proper array for the listener
        const shiftPlanArray = Object.values(this.newShiftPlanData);
        const shiftPlanDataRef = ref(shiftPlanArray);
        const ShiftCalendarListener = useShiftCalendarListener(shiftPlanDataRef);
        ShiftCalendarListener.init();

        // Listen for scroll events on both sections
        this.$refs.shiftPlan?.addEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview?.addEventListener('scroll', this.syncScrollUserOverview);
        //window.addEventListener('resize', this.updateHeight);
        //this.updateHeight();

        this.setupInertiaNavigationGuard();

        // Initial berechnen
        this.updateLayout(true);

        // Fenster-Resize: Proportional anpassen + Clamping
        this._onWindowResize = () => {
            const avail = this.availableHeight();
            // Zielhöhe aus Ratio, dann clampen + Layout aktualisieren
            this.userOverviewHeight = Math.round(avail * this._heightRatio);
            this.updateLayout();
        };

        window.addEventListener('resize', this._onWindowResize, { passive: true });

        setTimeout(() => {
            this.showCalendarWarning = ''
        }, 5000)
        /**
         * this code needs to be built in, when the observer is fixed and the observer is used
         *  const observer = new IntersectionObserver(
         *                 (entries) => {
         *                     entries.forEach((entry) => {
         *                         const day = entry.target.dataset.day;
         *
         *                         if (entry.intersectionRatio > 0) {
         *                             this.currentDaysInView.add(day);
         *                         } else {
         *                             this.currentDaysInView.delete(day);
         *                         }
         *                     });
         *                 },
         *                 {
         *                     root: document.getElementsByClassName('.eventByDaysContainer')[0],
         *                     rootMargin: '5000px'
         *                 }
         *             ),
         *             dayContainers = document.querySelectorAll('.day-container');
         *
         *         dayContainers.forEach((container) => {
         *             observer.observe(container);
         *         });
         */
    },
    created() {
        window.addEventListener("resize", this.updateHeight);
    },
    computed: {
        noticeIcon() {
            // Namen der Heroicons/Lucide-Komponenten anpassen, wenn du andere nutzt
            switch (this.notice.kind) {
                case 'error': return 'IconExclamationCircle';
                case 'info':  return 'IconInfoCircle';
                default:      return 'IconCircleCheck';
            }
        },
        noticeIconClass() {
            return {
                'text-green-400': this.notice.kind === 'success',
                'text-red-400':   this.notice.kind === 'error',
                'text-blue-400':  this.notice.kind === 'info',
            };
        },
        craftsToDisplay() {
            const crafts = this.crafts
                .map(
                    (craft) => {
                        return {
                            id: craft.id,
                            name: craft.name,
                            abbreviation: craft.abbreviation,
                            users: this.filterAndSortWorkersOfCraft(craft),
                            color: craft?.color,
                            universally_applicable: craft.universally_applicable,
                        };
                    }
                );

            crafts.forEach((craft) => {
                crafts.users = craft.users;
            });

            if (this.user_filters.craft_ids?.length === 0 || this.user_filters.craft_ids === null) {
                return crafts;
            } else {
                return crafts.filter((craft) => this.user_filters.craft_ids.includes(craft.id));
            }
        },
        workersWithoutCraft() {
            let workersWithoutCraft = this.filterNonManagingWorkersByShiftQualificationFilter(
                this.getDropWorkers().filter(user => user.assigned_craft_ids.length === 0)
            );

            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === null) {
                return workersWithoutCraft;
            }

            //simple sort by first/last name ascending or descending
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING') {
                return this.sortAscendingByUseFirstNameForSort(workersWithoutCraft);
            }

            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING') {
                return this.sortDescendingByUseFirstNameForSort(workersWithoutCraft);
            }

            //prepare intern/extern sort
            let assignedInternWorkers = workersWithoutCraft.filter(
                (workerWithoutCraft) => this.isWorkerUser(workerWithoutCraft)
            ), assignedExternWorkers = workersWithoutCraft.filter(
                (assignedNonManagingWorker) => this.isWorkerFreelancer(assignedNonManagingWorker) ||
                    this.isWorkerServiceProvider(assignedNonManagingWorker)
            );

            //intern/extern alphabetically by name ascending -> managing workers first, intern, extern afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING') {
                return this
                    .sortAscendingByUseFirstNameForSort(assignedInternWorkers)
                    .concat(this.sortAscendingByUseFirstNameForSort(assignedExternWorkers));
            }

            //intern/extern alphabetically by name descending -> managing workers first, extern, intern afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING') {
                return this
                    .sortDescendingByUseFirstNameForSort(assignedExternWorkers)
                    .concat(this.sortDescendingByUseFirstNameForSort(assignedInternWorkers));
            }

            return workersWithoutCraft;
        },
        computedShiftPlanWorkerSortEnums() {
            let nameSortEnums = [
                    'ALPHABETICALLY_NAME_ASCENDING',
                    'ALPHABETICALLY_NAME_DESCENDING'
                ],
                sortConfig = [];

            if (nameSortEnums.includes(this.$page.props.auth.user.shift_plan_user_sort_by_id)) {
                sortConfig.push('INTERN_EXTERN_ASCENDING');

                if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING') {
                    sortConfig.push('ALPHABETICALLY_NAME_DESCENDING');
                } else {
                    sortConfig.push('ALPHABETICALLY_NAME_ASCENDING');
                }

                return sortConfig;
            }

            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING') {
                sortConfig.push('INTERN_EXTERN_DESCENDING');
            } else {
                sortConfig.push('INTERN_EXTERN_ASCENDING');
            }

            sortConfig.push('ALPHABETICALLY_NAME_ASCENDING');

            return sortConfig;
        },
    },
    methods: {
        IconGeometry,
        IconList,
        IconBulb,
        IconChevronsDown,
        IconPlus,
        IconPencil,
        IconCalendarWeek,
        IconX,
        IconAlertSquareRounded,
        usePage,
        changeDailyViewMode(){
            router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
                daily_view: this.dailyViewMode
            }, {
                preserveScroll: false,
                preserveState: false
            })
        },
        getAllProjectGroupsInEventsByDay(events){
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
        },
        checkIfUserIsAdminOrInGroup(group){
            if (this.hasAdminRole()) {
                return false;
            }

            return !group.userIds.includes(usePage().props.auth.user.id);
        },
        initializeCalendarMultiEditSave() {
            this.showAddShiftModal = true
        },
        closeCellMultiEditCalendarDelete(boolean) {
            if(boolean){
                this.openCellMultiEditCalendarDelete = false;
                this.showAddShiftModal = true;
            } else {
                this.openCellMultiEditCalendarDelete = false;
                this.multiEditCalendarDays = [];
            }
        },
        openEditShiftModal(shift) {
            this.shiftToEdit = shift;
            this.showAddShiftModal = true;

        },
        openAddShiftForRoomAndDay(day, roomId){
            this.shiftToEdit = null;
            this.roomForShiftAdd = roomId;
            this.dayForShiftAdd = day;
            this.showAddShiftModal = true;
        },
        closeAddShiftModal() {
            this.showAddShiftModal = false;
            this.roomForShiftAdd = null;
            this.dayForShiftAdd = null;
            this.multiEditCalendarDays = [];
        },
        checkIfRoomAndDayIsInMultiEditCalendar(day, roomId){
            return this.multiEditCalendarDays.some((dayAndRoom) => dayAndRoom.day === day && dayAndRoom.roomId === roomId)
        },
        addDayAndRoomToMultiEditCalendar(day, roomId){
            const exists = this.multiEditCalendarDays.some(
                (dayAndRoom) => dayAndRoom.day === day && dayAndRoom.roomId === roomId
            );

            if (exists) {
                this.multiEditCalendarDays = this.multiEditCalendarDays.filter(
                    (dayAndRoom) => !(dayAndRoom.day === day && dayAndRoom.roomId === roomId)
                );
            } else {
                this.multiEditCalendarDays.push({ day: day, roomId: roomId });
            }
        },
        setupInertiaNavigationGuard() {
            this.originalVisit = router.visit;
            window.onbeforeunload = (event) => {
                if (this.multiEditMode && this.userForMultiEdit && !this.preventNextNavigation) {
                    event.preventDefault();
                    event.returnValue = this.$t('Would you like to save the changes before you leave the page?');
                }
            };

            router.visit = async (url, options = {}) => {
                if (this.multiEditMode && this.userForMultiEdit && !this.preventNextNavigation) {
                    this.preventNextNavigation = true;

                    try {
                        //await this.initializeMultiEditSave();
                        if (!this.waitForModalClose) {
                            this.originalVisit.call(router, url, options);
                        }
                    } finally {
                        this.preventNextNavigation = false;
                    }
                } else if (!this.waitForModalClose) {
                    this.originalVisit.call(router, url, options);
                }
            };
        },
        isWorkerUser(worker) {
            return worker.type === 0;
        },
        isWorkerFreelancer(worker) {
            return worker.type === 1;
        },
        isWorkerServiceProvider(worker) {
            return worker.type === 2;
        },
        isManagingWorker(craft, worker) {
            if (this.isWorkerUser(worker)) {
                return craft.managing_users.findIndex(
                    (managingUser) => managingUser.id === worker.element.id
                ) > -1;
            }

            if (this.isWorkerFreelancer(worker)) {
                return craft.managing_freelancers.findIndex(
                    (managingUser) => managingUser.id === worker.element.id
                ) > -1;
            }

            if (this.isWorkerServiceProvider(worker)) {
                return craft.managing_service_providers.findIndex(
                    (managingUser) => managingUser.id === worker.element.id
                ) > -1;
            }
        },
        getAssignedWorkerOfCraft(craftId, dropWorkers) {
            return dropWorkers.filter((dropWorker) => dropWorker.assigned_craft_ids.includes(craftId))
        },
        sortAscendingByUseFirstNameForSort(workers) {
            if (!this.useFirstNameForSort) {
                return workers.sort((a, b) => {
                    let compareNameA = (this.isWorkerServiceProvider(a)) ?
                        a.element.provider_name :
                        a.element.first_name;

                    let compareNameB = (this.isWorkerServiceProvider(b)) ?
                        b.element.provider_name :
                        b.element.first_name;

                    if (compareNameA < compareNameB) return -1;
                    if (compareNameA > compareNameB) return 1;
                    return 0;
                });
            } else {
                return workers.sort((a, b) => {
                    let compareNameA = (this.isWorkerServiceProvider(a)) ?
                        a.element.provider_name :
                        a.element.last_name;

                    let compareNameB = (this.isWorkerServiceProvider(b)) ?
                        b.element.provider_name :
                        b.element.last_name;

                    if (compareNameA < compareNameB) return -1;
                    if (compareNameA > compareNameB) return 1;
                    return 0;
                });
            }
        },
        sortDescendingByUseFirstNameForSort(workers) {
            if (!this.useFirstNameForSort) {
                return workers.sort((a, b) => {
                    let compareNameA = (this.isWorkerServiceProvider(a)) ?
                        a.element.provider_name :
                        a.element.first_name;

                    let compareNameB = (this.isWorkerServiceProvider(b)) ?
                        b.element.provider_name :
                        b.element.first_name;

                    if (compareNameA > compareNameB) return -1;
                    if (compareNameA < compareNameB) return 1;
                    return 0;
                });
            } else {
                return workers.sort((a, b) => {
                    let compareNameA = (this.isWorkerServiceProvider(a)) ?
                        a.element.provider_name :
                        a.element.last_name;

                    let compareNameB = (this.isWorkerServiceProvider(b)) ?
                        b.element.provider_name :
                        b.element.last_name;

                    if (compareNameA > compareNameB) return -1;
                    if (compareNameA < compareNameB) return 1;
                    return 0;
                });
            }
        },
        filterAndSortWorkersOfCraft(craft) {
            let dropWorkers = this.getDropWorkers(),
                //all assigned workers of given craft contained
                assignedWorkersOfCraft = this.getAssignedWorkerOfCraft(craft.id, dropWorkers),
                //all managing worker of given craft filtered from assignedWorkersOfCraft
                assignedManagingWorkers = assignedWorkersOfCraft.filter(
                    (assignedWorkerOfCraft) => this.isManagingWorker(craft, assignedWorkerOfCraft)
                ),
                //all non managing worker of given craft filtered from assignedWorkersOfCraft
                assignedNonManagingWorkers = assignedWorkersOfCraft.filter(
                    (assignedWorkerOfCraft) => !this.isManagingWorker(craft, assignedWorkerOfCraft)
                ),
                assignedNonManagingWorkersFiltered = this.filterNonManagingWorkersByShiftQualificationFilter(
                    assignedNonManagingWorkers
                );

            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === null) {
                return assignedManagingWorkers.concat(assignedNonManagingWorkersFiltered);
            }

            //alphabetically by name ascending -> managing workers first, other users afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_ASCENDING') {
                return this
                    .sortAscendingByUseFirstNameForSort(assignedManagingWorkers)
                    .concat(this.sortAscendingByUseFirstNameForSort(assignedNonManagingWorkersFiltered));
            }

            //alphabetically by name descending -> managing workers first, other users afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'ALPHABETICALLY_NAME_DESCENDING') {
                return this
                    .sortDescendingByUseFirstNameForSort(assignedManagingWorkers)
                    .concat(this.sortDescendingByUseFirstNameForSort(assignedNonManagingWorkersFiltered));
            }

            //prepare intern/extern sort (respect current shift qualification filter)
            let assignedNonManagingInternWorkers = assignedNonManagingWorkersFiltered.filter(
                (assignedNonManagingWorker) => this.isWorkerUser(assignedNonManagingWorker)
            ), assignedNonManagingExternWorkers = assignedNonManagingWorkersFiltered.filter(
                (assignedNonManagingWorker) => this.isWorkerFreelancer(assignedNonManagingWorker) ||
                    this.isWorkerServiceProvider(assignedNonManagingWorker)
            );

            //intern/extern alphabetically by name ascending -> managing workers first, intern, extern afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_ASCENDING') {
                return this
                    .sortAscendingByUseFirstNameForSort(assignedManagingWorkers)
                    .concat(this.sortAscendingByUseFirstNameForSort(assignedNonManagingInternWorkers))
                    .concat(this.sortAscendingByUseFirstNameForSort(assignedNonManagingExternWorkers));
            }

            //intern/extern alphabetically by name descending -> managing workers first, extern, intern afterward
            if (this.$page.props.auth.user.shift_plan_user_sort_by_id === 'INTERN_EXTERN_DESCENDING') {
                return this
                    .sortDescendingByUseFirstNameForSort(assignedManagingWorkers)
                    .concat(this.sortDescendingByUseFirstNameForSort(assignedNonManagingExternWorkers))
                    .concat(this.sortDescendingByUseFirstNameForSort(assignedNonManagingInternWorkers));
            }
        },
        filterNonManagingWorkersByShiftQualificationFilter(workers) {
            if (this.userShiftPlanShiftQualificationFilters.length === 0) {
                return workers;
            }

            let workersWithShiftQualifications = workers.filter(
                (worker) => worker.element.shift_qualifications.length > 0
            );

            return workersWithShiftQualifications.filter(
                (worker) => worker.element.shift_qualifications.some(
                    (shift_qualification) => this.userShiftPlanShiftQualificationFilters.includes(
                        shift_qualification.id
                    )
                )
            );
        },
        getSortEnumTranslation,
        userShiftModalDesiresReload(shiftId, userId, userType, desiredDay) {

        },
        eventDesiresReload(userId, userType, event, seriesShiftData) {

        },
        getDropWorkers() {
            const users = [];
            this.usersForShifts.forEach((user) => {
                if(!this.showFreelancers && user.user.is_freelancer) {
                    return;
                }
                users.push({
                    element: user.user,
                    type: 0,
                    workTimeBalance: user.workTimeBalance,
                    //plannedWorkingHours: user.plannedWorkingHours,
                    //expectedWorkingHours: user.expectedWorkingHours,
                    vacations: user.vacations,
                    assigned_craft_ids: user.user.assigned_craft_ids,
                    availabilities: user.availabilities,
                    weeklyWorkingHours: user.weeklyWorkingHours,
                    dayServices: user.dayServices,
                    individual_times: user.individual_times,
                    shift_comments: user.shift_comments
                });
            })
            if (this.showFreelancers) {
                this.freelancersForShifts.forEach((freelancer) => {
                    users.push({
                        element: freelancer.freelancer,
                        type: 1,
                        //plannedWorkingHours: freelancer.plannedWorkingHours,
                        vacations: freelancer.vacations,
                        assigned_craft_ids: freelancer.freelancer.assigned_craft_ids,
                        availabilities: freelancer.availabilities,
                        dayServices: freelancer.dayServices,
                        individual_times: freelancer.individual_times,
                        shift_comments: freelancer.shift_comments,
                        weeklyWorkingHours: freelancer.weeklyWorkingHours,
                    });
                })
            }

            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    //plannedWorkingHours: service_provider.plannedWorkingHours,
                    assigned_craft_ids: service_provider.service_provider.assigned_craft_ids,
                    dayServices: service_provider.dayServices,
                    individual_times: service_provider.individual_times,
                    shift_comments: service_provider.shift_comments,
                    weeklyWorkingHours: service_provider.weeklyWorkingHours,
                });
            })
            return users;
        },
        handleMultiEdit(user, day) {
            // Erstelle einen eindeutigen Schlüssel aus der User-ID und dem Typ
            const userKey = `${user.element.id}_${user.type}`;

            // Prüfen, ob der Eintrag für die Kombination aus User-ID und Typ existiert
            if (!this.multiEditCellByDayAndUser[userKey]) {
                this.multiEditCellByDayAndUser[userKey] = {
                    days: [],
                    type: user.type,
                    id: user.element.id,
                    entity: user.element,
                };
            }
            const userData = this.multiEditCellByDayAndUser[userKey];

            // Prüfen, ob der Tag bereits für diesen Benutzer vorhanden ist
            if (userData.days.includes(day.withoutFormat)) {
                // Entferne den Tag (deselect)
                userData.days = userData.days.filter((d) => d !== day.withoutFormat);

                // Wenn keine Tage mehr vorhanden sind, lösche das Benutzerobjekt
                if (userData.days.length === 0) {
                    delete this.multiEditCellByDayAndUser[userKey];
                }
            } else {
                // Wenn der Tag nicht vorhanden ist, füge ihn hinzu (select)
                userData.days.push(day.withoutFormat);
            }
        },
        handleCellClick(user, day) {
            if(this.multiEditMode && !this.userForMultiEdit) {
                this.handleMultiEdit(user, day);
                return;
            }

            if(this.multiEditMode && this.userForMultiEdit) {
                return;
            }

            if (this.dayServiceMode) {
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
                const hasDayService = user.dayServices?.[day.withoutFormat]?.some(dayService => dayService.id === this.selectedDayService.id)
                // check if user has allready the selected day service, if yes remove it. the dayServices in User are group by day

                let setWorkerDayServicesCallback = (response) => {
                    let data = response.data;
                    let dayServicesRef = data.dayServices;

                    switch (data.type) {
                        case 'user':
                            this.usersForShifts.find(
                                (user) => user.user.id === data.id
                            ).dayServices = dayServicesRef;
                            break;
                        case 'freelancer':
                            this.freelancersForShifts.find(
                                (freelancer) => freelancer.freelancer.id === data.id
                            ).dayServices = dayServicesRef;
                            break;
                        case 'service_provider':
                            this.serviceProvidersForShifts.find(
                                (service_provider) => service_provider.service_provider.id === data.id
                            ).dayServices = dayServicesRef;
                            break;
                    }
                };

                if (hasDayService) {
                    axios.patch(
                        route(
                            'remove.day.service.from.user',
                            {
                                dayServiceable: user.element.id,
                            }
                        ),
                        {
                            dayService: this.selectedDayService.id,
                            date: day.withoutFormat,
                            modelType: type
                        }
                    ).then(setWorkerDayServicesCallback);
                } else {
                    axios.post(
                        route(
                            'day-service.attach',
                            {
                                dayServiceable: user.element.id,
                                dayService: this.selectedDayService.id,
                            }
                        ),
                        {
                            date: day.withoutFormat,
                            modelType: type
                        }
                    ).then(setWorkerDayServicesCallback);
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
            if (this.$page.props.auth.user?.show_crafts?.length === 0 || this.$page.props.auth.user?.show_crafts === null || this.$page.props.auth.user?.show_crafts === undefined) {
                return event.shifts?.length > 0;
            } else {
                return event.shifts?.length > 0 && event.shifts.some(shift => this.$page.props.auth.user.show_crafts?.includes(shift.craft.id));
            }
        },
        showDropFeedback(feedback) {
            this.dropFeedback = feedback;
            setTimeout(() => {
                this.dropFeedback = null
            }, 2000)
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
            const dateDifference = this.calculateDateDifference();
            this.dateValue[0] = dayjs(this.dateValue[0]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD');
            this.dateValue[1] = dayjs(this.dateValue[1]).subtract(dateDifference + 1, 'day').format('YYYY-MM-DD');
            this.updateTimes();

        },
        nextTimeRange() {
            const dateDifference = this.calculateDateDifference();
            this.dateValue[0] = dayjs(this.dateValue[0]).add(dateDifference + 1, 'day').format('YYYY-MM-DD');
            this.dateValue[1] = dayjs(this.dateValue[1]).add(dateDifference + 1, 'day').format('YYYY-MM-DD');
            this.updateTimes();
        },

        calculateDateDifference() {
            const date1 = new Date(this.dateValue[0]);
            const date2 = new Date(this.dateValue[1]);
            const timeDifference = date2.getTime() - date1.getTime();
            return timeDifference / (1000 * 3600 * 24);
        },
        updateTimes() {
            router.patch(route('update.user.shift.calendar.filter.dates', this.$page.props.auth.user.id), {
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
            //this.updateLayout()
        },
        syncScrollShiftPlan(event) {
            if (this.$refs.userOverview) {
                this.$refs.userOverview.scrollLeft = event.target.scrollLeft;
                const scrollableContainer = this.$refs.shiftPlan;
                const roomNameFixedPosition = scrollableContainer.getBoundingClientRect().left + 200;
                let closestDayIndex = null;
                let closestDayDistance = Infinity;

                for (let i = 0; i < this.days.length; i++) {
                    const day = this.days[i];
                    const dayElement = document.getElementById(day.fullDay || `extra_row_${day.weekNumber}`);
                    if (!dayElement) continue;
                    const elementLeft = dayElement.getBoundingClientRect().left;
                    const elementCenter = elementLeft + (dayElement.offsetWidth / 2);
                    const distanceToRoomName = Math.abs(roomNameFixedPosition - elementCenter);
                    if (distanceToRoomName < closestDayDistance) {
                        closestDayDistance = distanceToRoomName;
                        closestDayIndex = i;
                    }
                }

                if (closestDayIndex !== null) {
                    const selectedDay = this.days[closestDayIndex];
                    if (selectedDay.isExtraRow) {
                        for (let j = closestDayIndex + 1; j < this.days.length; j++) {
                            if (this.days[j].isMonday) {
                                this.currentDayOnView = this.days[j];
                                return;
                            }
                        }
                    } else {
                        this.currentDayOnView = selectedDay;
                    }
                }
            }
        },
        selectGoToMode(direction) {
            const gotoMode = this.$page.props.auth.user.goto_mode;
            this.scrollToPeriod(gotoMode, direction);
        },
        scrollToPeriod(period, direction) {
            let indexModifier = direction === 'next' ? 1 : -1;
            let periodKey, periodValue, scrollOffset;

            if (period === 'day') {
                let currentIndex = this.days.indexOf(this.currentDayOnView);
                let targetIndex = currentIndex + indexModifier;
                while (targetIndex >= 0 && targetIndex < this.days.length) {
                    const targetDay = this.days[targetIndex];
                    if (!targetDay.isExtraRow) {
                        scrollOffset = targetIndex;
                        break;
                    }
                    targetIndex += indexModifier;
                }

            } else if (period === 'week') {
                periodKey = 'weekNumber';
                periodValue = this.currentDayOnView.weekNumber;
                scrollOffset = this.getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, day => day.isMonday);
            } else if (period === 'month') {
                periodKey = 'monthNumber';
                periodValue = this.currentDayOnView.monthNumber;
                scrollOffset = this.getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, day => day.isFirstDayOfMonth);
            }
            if (scrollOffset !== undefined && scrollOffset !== null) {
                const targetDay = this.days[scrollOffset];
                this.currentDayOnView = targetDay;
                const targetElement = document.getElementById(targetDay.fullDay);
                if (targetElement) {
                    const roomNameElement = document.getElementById('roomNameContainer_0');
                    const scrollableContainer = this.$refs.shiftPlan;

                    if (roomNameElement) {
                        const roomNameLeft = roomNameElement.getBoundingClientRect().left;
                        const containerLeft = scrollableContainer.getBoundingClientRect().left;
                        scrollableContainer.scrollLeft = targetElement.offsetLeft - (roomNameLeft + containerLeft + 65);
                    }
                }
            }
        },
        getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, conditionCallback) {
            let targetIndex = this.days.findIndex(day => day[periodKey] === periodValue && conditionCallback(day));
            while (true) {
                targetIndex += indexModifier;
                if (targetIndex < 0 || targetIndex >= this.days.length) {
                    return null;
                }
                const day = this.days[targetIndex];
                if (!day.isExtraRow && conditionCallback(day)) {
                    return targetIndex;
                }
            }
        },
        selectGoToNextMode() {
            this.selectGoToMode('next');
        },
        selectGoToPreviousMode() {
            this.selectGoToMode('previous');
        },
        syncScrollUserOverview(event) {
            if (this.$refs.shiftPlan) {
                this.$refs.shiftPlan.scrollLeft = event.target.scrollLeft;
            }
        },
        openShowUserShiftModal(user, day) {
            this.userToShow = user
            this.dayToShow = day
            this.showUserShifts = true
        },
        toggleHighlightMode() {
            this.idToHighlight = null;
            this.typeToHighlight = null;
            this.multiEditMode = false;
            this.dayServiceMode = false;
        },
        toggleMultiEditMode() {
            this.highlightMode = false;
            this.dayServiceMode = false;

            if (!this.multiEditMode) {
                this.userForMultiEdit = null;
                this.multiEditCellByDayAndUser = {};
            }
        },
        toggleMultiEditModeCalendar() {
            this.highlightMode = false;
            this.dayServiceMode = false;

            if (!this.multiEditModeCalendar){
                this.multiEditCalendarDays = [];
            }
        },
        closeMultiEditCellModal(eventData){
            this.showCellMultiEditModal = false;
            if (eventData && eventData.saved) {
                // Save action: reload cells, deselect cells, keep multi-edit mode active
                this.multiEditCellByDayAndUser = {};
                // Reload only the necessary data to refresh cell values, similar to shift assignment
                router.reload({
                    only: ['shifts', 'users', 'rooms', 'days', 'usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts']
                });
            } else {
                // X button close: just close modal, keep cells selected, no backend action
                // multiEditCellByDayAndUser remains unchanged to keep cells selected
            }
        },
        closeCellMultiEditDelete(boolean) {
            if(boolean.closing) {
                this.openCellMultiEditDelete = false;
                return;
            }

            this.openCellMultiEditDelete = false;

            if(boolean) {
                this.showCellMultiEditModal = true;
            }
            if (!boolean) {
                this.multiEditCellByDayAndUser = {};
            }
        },
        toggleDayServiceMode() {
            this.highlightMode = false;
            this.multiEditMode = false;
        },
        toggleCompactMode() {
            router.post(route('user.compact.mode.toggle', {user: this.$page.props.auth.user.id}), {
                compact_mode: this.$page.props.auth.user.compact_mode
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
            // 1) Bestehende UI-Interaktionen sauber schließen
            this._cancelOpenPickersAndModals();

            // 2) Neue Session starten -> alle alten Saves ignorieren
            this.multiEditSessionId++;
            this.saveQueue = Promise.resolve();

            // 3) UI-State leeren
            this.userToMultiEditCheckedShiftsAndEvents = [];
            this.savingShiftIds = new Set();
            this.showShiftsQualificationsAssignmentModalShifts = [];
            this.showShiftsQualificationsAssignmentModal = false;
            this.waitForModalClose = false;

            // 4) User setzen (und Arrays KOPIEREN, nicht referenzteilen!)
            this.userForMultiEdit = item;
            const ids = Array.isArray(item?.shift_ids) ? [...item.shift_ids] : [];
            this.userForMultiEdit.shift_ids = ids;               // editierbarer, eigener Array
            this.userToMultiEditCurrentShifts = [...ids];        // Snapshot für removeFromShift etc.

            // optional: Qualifikationen defensiv kopieren
            if (Array.isArray(item?.shift_qualifications)) {
                this.userForMultiEdit.shift_qualifications = [...item.shift_qualifications];
            }
        },

        _cancelOpenPickersAndModals() {
            // Bulk-Modal Resolver beenden
            if (this.resolveModalClose) {
                try { this.resolveModalClose(); } catch(_) {}
                this.resolveModalClose = null;
            }
            // Single-Picker Resolver beenden
            if (this._singlePickResolve) {
                try { this._singlePickResolve(null); } catch(_) {}
                this._singlePickResolve = null;
                this._singlePickShiftId = null;
            }
            // Sichtbarkeiten & Flags zurücksetzen
            this.showShiftsQualificationsAssignmentModal = false;
            this.showShiftsQualificationsAssignmentModalShifts = [];
            this.waitForModalClose = false;
        },
        async initializeMultiEditSave() {
            this.shiftsToHandleOnMultiEdit.removeFromShift = this.userToMultiEditCurrentShifts.filter(
                (shift_id) => !this.userForMultiEdit.shift_ids.includes(shift_id)
            );

            if (this.userForMultiEdit.shift_qualifications.length > 0) {
                this.userToMultiEditCheckedShiftsAndEvents.forEach((shiftAndEvent) => {
                    let desiredShift = shiftAndEvent.shift;

                    if (desiredShift.shifts_qualifications.length === 0) {
                        this.showShiftsQualificationsAssignmentModalShifts.push({
                            shift: desiredShift,
                            availableSlots: this.userForMultiEdit.shift_qualifications
                        });
                        return;
                    }

                    if (this.userForMultiEdit.shift_qualifications.length === 1) {
                        this.shiftsToHandleOnMultiEdit.assignToShift.push({
                            shiftId: desiredShift.id,
                            shiftQualificationId: this.userForMultiEdit.shift_qualifications[0].id
                        });
                        return;
                    }

                    let availableShiftQualificationSlots = [];
                    this.userForMultiEdit.shift_qualifications.forEach((userShiftQualification) => {
                        desiredShift.shifts_qualifications.forEach((shiftsQualification) => {
                            if (userShiftQualification.id === shiftsQualification.shift_qualification_id) {
                                availableShiftQualificationSlots.push(userShiftQualification);
                            }
                        });
                    });

                    if (availableShiftQualificationSlots.length === 0) {
                        return;
                    }

                    if (availableShiftQualificationSlots.length === 1) {
                        this.shiftsToHandleOnMultiEdit.assignToShift.push({
                            shiftId: desiredShift.id,
                            shiftQualificationId: availableShiftQualificationSlots[0].id
                        });
                        return;
                    }

                    this.showShiftsQualificationsAssignmentModalShifts.push({
                        shift: desiredShift,
                        availableSlots: availableShiftQualificationSlots
                    });
                });
            }

            if (this.showShiftsQualificationsAssignmentModalShifts.length > 0) {
                this.showShiftsQualificationsAssignmentModal = true;
                this.waitForModalClose = true;

                return new Promise((resolve) => {
                    this.resolveModalClose = resolve;
                });
            }
            await this.saveMultiEdit();
            this.waitForModalClose = false;
        },
        async closeShiftsQualificationsAssignmentModal(closedForAssignment, assignedShifts) {
            this.showShiftsQualificationsAssignmentModal = false;
            const resolver = this.resolveModalClose;     // Bulk-Resolver (falls gesetzt)
            const singleResolver = this._singlePickResolve; // Single-Resolver (Checkbox)
            const singleShiftId = this._singlePickShiftId;

            // immer resetten
            this.resolveModalClose = null;
            this._singlePickResolve = null;
            this._singlePickShiftId = null;

            this.showShiftsQualificationsAssignmentModalShifts = [];

            try {
                if (!closedForAssignment) {
                    // Abbruch
                    if (singleResolver) {
                        // Single-Pick: User hat abgebrochen → null
                        singleResolver(null);
                        return;
                    }
                    // Bulk-Fall: nur Promise auflösen
                    resolver?.();
                    return;
                }

                if (singleResolver) {
                    // SINGLE-PICK: wir erwarten exakt 1 Auswahl für unseren Shift
                    const picked = (assignedShifts ?? []).find(s => s.shiftId === singleShiftId);
                    singleResolver(picked ? { id: picked.shiftQualificationId } : null);
                    return;
                }

                // BULK-FALL: wie gehabt speichern
                (assignedShifts ?? []).forEach((s) => {
                    this.shiftsToHandleOnMultiEdit.assignToShift.push({
                        shiftId: s.shiftId,
                        shiftQualificationId: s.shiftQualificationId
                    });
                });

                await this.saveMultiEdit();
            } finally {
                this.waitForModalClose = false;
                resolver?.(); // Bulk-Fall abschließen
            }
        },
        async saveMultiEdit() {
            if (
                this.shiftsToHandleOnMultiEdit.assignToShift.length === 0 &&
                this.shiftsToHandleOnMultiEdit.removeFromShift.length === 0
            ) {
                this.resetMultiEditMode();
                return;
            }
            axios.post(route('shift.multi.edit.save'), {
                userType: this.userForMultiEdit.type,
                userTypeId: this.userForMultiEdit.id,
                craft_abbreviation: this.userForMultiEdit.craft_abbreviation,
                shiftsToHandle: this.shiftsToHandleOnMultiEdit
            }).then(() => {
                this.resetMultiEditMode(false);
            });
        },
        resetMultiEditMode(closeMultiEdit = true) {
            this.shiftsToHandleOnMultiEdit = {
                assignToShift: [],
                removeFromShift: []
            };
            this.userToMultiEditCheckedShiftsAndEvents = [];

            // Update current shifts to match the user's actual shift_ids after save
            if (this.userForMultiEdit && !closeMultiEdit) {
                this.userToMultiEditCurrentShifts = [...this.userForMultiEdit.shift_ids];
            }

            if (closeMultiEdit) {
                this.multiEditMode = false;
                this.multiEditCellByDayAndUser = {};
            }
        },
        changeCraftVisibility(id) {
            if (this.closedCrafts.includes(id)) {
                this.closedCrafts.splice(this.closedCrafts.indexOf(id), 1);
            } else {
                this.closedCrafts.push(id);
            }
        },
        availableHeight() {
            return Math.max(0, window.innerHeight - HEADER_H);
        },
        clamp(n, min, max) {
            return Math.min(Math.max(n, min), max);
        },
        updateLayout(initial = false) {
            const avail = this.availableHeight();
            const maxTop = Math.max(MIN_TOP, avail - MIN_MAIN - TOP_GAP);
            if (!this.showUserOverview) {
                this.windowHeight = Math.max(MIN_MAIN, avail - TOP_GAP);
                return;
            }
            this.userOverviewHeight = this.clamp(this.userOverviewHeight, MIN_TOP, maxTop);
            this.windowHeight = Math.max(
                MIN_MAIN,
                avail - this.userOverviewHeight - TOP_GAP
            );
            if (!initial && avail > 0) {
                this._heightRatio = this.clamp(this.userOverviewHeight / avail, 0.05, 0.9);
            }
        },

        // === Resize-Interaktion ===
        startResize(event) {
            event.preventDefault();
            this.startY = event.clientY;
            this.startHeight = this.userOverviewHeight;
            document.addEventListener('mousemove', this.resizing);
            document.addEventListener('mouseup', this.stopResize);
        },

        resizing(event) {
            const diff = this.startY - event.clientY;
            this.userOverviewHeight = this.startHeight + diff;
            this.updateLayout();
        },

        stopResize(event) {
            event.preventDefault();
            const avail = this.availableHeight();
            if (avail > 0) {
                this._heightRatio = this.clamp(this.userOverviewHeight / avail, 0.05, 0.9);
            }
            document.removeEventListener('mousemove', this.resizing);
            document.removeEventListener('mouseup', this.stopResize);
            this.saveUserOverviewHeight?.(this.userOverviewHeight);
        },
        saveUserOverviewHeight: debounce(function () {
            this.applyUserOverviewHeight();
        }, 500),
        applyUserOverviewHeight() {
            router.patch(route('user.update.userOverviewHeight', {user: usePage().props.auth.user.id}), {
                drawer_height: this.userOverviewHeight
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        applySort(shiftPlanWorkerSortEnumName) {
            // Toggle sort direction when clicking the same option again
            const current = this.$page.props.auth.user.shift_plan_user_sort_by_id;
            const toggleMap = {
                'INTERN_EXTERN_ASCENDING': 'INTERN_EXTERN_DESCENDING',
                'INTERN_EXTERN_DESCENDING': 'INTERN_EXTERN_ASCENDING',
                'ALPHABETICALLY_NAME_ASCENDING': 'ALPHABETICALLY_NAME_DESCENDING',
                'ALPHABETICALLY_NAME_DESCENDING': 'ALPHABETICALLY_NAME_ASCENDING',
            };

            let nextSort = shiftPlanWorkerSortEnumName;
            if (current && current === shiftPlanWorkerSortEnumName && toggleMap[shiftPlanWorkerSortEnumName]) {
                nextSort = toggleMap[shiftPlanWorkerSortEnumName];
            }

            this.$page.props.auth.user.shift_plan_user_sort_by_id = nextSort;
            router.patch(
                route('user.update.shiftPlanUserSortBy', {user: this.$page.props.auth.user.id}),
                {
                    sortBy: nextSort
                }, {
                    preserveState: true,
                    preserveScroll: true,
                }
            );
        },
        resetSort() {
            this.$page.props.auth.user.shift_plan_user_sort_by_id = null;
            router.patch(
                route('user.update.shiftPlanUserSortBy', {user: this.$page.props.auth.user.id}),
                {
                    sortBy: null
                }, {
                    preserveState: false,
                    preserveScroll: true,
                }
            );
        },
        handleShiftAndEventForMultiEdit(checked, shift, event) {

            this.onToggleShift(checked, shift, event);
            /*if (checked && this.userToMultiEditCurrentShifts.includes(shift.id)) {
                //return as the shift is already assigned to user and checkbox was unchecked and checked again
                return;
            }

            if (!checked) {
                //remove shift and event from shiftsAndEventsForMultiEdit
                let idx = this.userToMultiEditCheckedShiftsAndEvents.findIndex(
                    (shiftAndEvent) => shiftAndEvent.shift.id === shift.id
                );

                if (idx > -1) {
                    this.userToMultiEditCheckedShiftsAndEvents.splice(idx, 1);
                }

                return;
            }

            this.userToMultiEditCheckedShiftsAndEvents.push({
                shift: shift,
                event: event
            });*/
        },
        onToggleShift(checked, shift, event) {
            if (!this.userForMultiEdit) return;
            if (this.isSaving(shift.id)) return;

            const oldIds = new Set(this.userForMultiEdit.shift_ids ?? []);
            const alreadyAssigned = oldIds.has(shift.id);

            if ((checked && alreadyAssigned) || (!checked && !alreadyAssigned)) {
                return;
            }

            this.savingShiftIds.add(shift.id);

            if (checked) {
                // Optimistic add
                const optimistic = new Set(oldIds);
                optimistic.add(shift.id);
                this.userForMultiEdit.shift_ids = Array.from(optimistic);

                this.enqueueSave(async () => {
                    // Qualifikation nur prüfen, wenn der Shift welche verlangt
                    const needsQuali = (shift.shifts_qualifications ?? []).length > 0;
                    let qualificationId = null;

                    if (needsQuali) {
                        qualificationId = await this.resolveQualificationFor(shift);
                        if (!qualificationId) {
                            // harter Rollback auf Snapshot
                            this.userForMultiEdit.shift_ids = Array.from(oldIds);

                            // existierender Toast
                            const msg = this.$t?.('No matching qualification for this shift') ?? 'No matching qualification for this shift';
                            if (this.$toast?.error) this.$toast.error(msg);

                            // 👉 NEW: Notice in EN
                            this.showNotice(
                                'error',
                                'Qualification required',
                                'This user does not have a matching qualification for this shift.'
                            );

                            throw new Error('no_qualification');
                        }
                    }

                    await this.persistAssign(shift.id, qualificationId);

                    // 👉 NEW: Success Notice in EN
                    this.showNotice(
                        'success',
                        'Assigned',
                        'The user was successfully added to the shift.'
                    );
                })
                    .catch(err => {
                        // Rollback auf Snapshot
                        this.userForMultiEdit.shift_ids = Array.from(oldIds);

                        if (err?.message !== 'no_qualification') {
                            if (this.$toast?.error) this.$toast.error(this.$t?.('Saving failed') ?? 'Saving failed');

                            // 👉 NEW: Error Notice in EN
                            this.showNotice(
                                'error',
                                'Save failed',
                                'Something went wrong while saving. Please try again.'
                            );
                            console.error(err);
                        }
                    })
                    .finally(() => {
                        this.savingShiftIds.delete(shift.id);
                    });

            } else {
                // Optimistic remove
                const optimistic = new Set(oldIds);
                optimistic.delete(shift.id);
                this.userForMultiEdit.shift_ids = Array.from(optimistic);

                this.enqueueSave(async () => {
                    await this.persistRemove(shift.id);

                    // 👉 NEW: Success Notice in EN
                    this.showNotice(
                        'success',
                        'Removed',
                        'The user was successfully removed from the shift.'
                    );
                })
                    .catch(err => {
                        // Rollback auf Snapshot
                        this.userForMultiEdit.shift_ids = Array.from(oldIds);
                        if (this.$toast?.error) this.$toast.error(this.$t?.('Saving failed') ?? 'Saving failed');

                        // 👉 NEW: Error Notice in EN
                        this.showNotice(
                            'error',
                            'Save failed',
                            'Something went wrong while saving. Please try again.'
                        );

                        console.error(err);
                    })
                    .finally(() => {
                        this.savingShiftIds.delete(shift.id);
                    });
            }
        },
        async resolveQualificationFor(desiredShift) {
            const userQualis = this.userForMultiEdit?.shift_qualifications ?? [];
            const required = (desiredShift.shifts_qualifications ?? []);

            // Kein Bedarf → keine ID nötig
            if (required.length === 0) return null;

            const available = userQualis.filter(uq =>
                required.some(req => req.shift_qualification_id === uq.id)
            );

            if (available.length === 0) return null;
            if (available.length === 1) return available[0].id;

            // Mehrere Optionen → Picker (falls vorhanden)
            if (this.openQualificationPicker) {
                const picked = await this.openQualificationPicker(desiredShift, available);
                return picked?.id ?? null;
            }

            // Fallback
            return available[0].id;
        },
        openQualificationPicker(desiredShift, options) {
            // Wir verwenden deinen bestehenden Modal-Mechanismus:
            // - showShiftsQualificationsAssignmentModalShifts: Array aus { shift, availableSlots }
            // - closeShiftsQualificationsAssignmentModal(closedForAssignment, assignedShifts)
            //   -> assignedShifts: Array aus { shiftId, shiftQualificationId }

            // Schutz: falls schon ein Modal offen ist, warten wir bis es zu ist
            if (this.waitForModalClose) {
                // optional: du könntest hier eine Queue einbauen – für jetzt lösen wir ab
                return Promise.resolve(null);
            }

            return new Promise((resolve) => {
                // Markiere, dass wir im "Single-Pick"-Modus sind
                this._singlePickResolve = resolve;          // interner Resolver nur für 1-Shift
                this._singlePickShiftId = desiredShift.id;  // merken, welchen Shift wir fragen

                // Modal-Daten auf einen einzigen Eintrag setzen
                this.showShiftsQualificationsAssignmentModalShifts = [{
                    shift: desiredShift,
                    availableSlots: options
                }];

                this.showShiftsQualificationsAssignmentModal = true;
                this.waitForModalClose = true;
            });
        },
        async persistAssign(shiftId, shiftQualificationId) {
            const payload = {
                userType: this.userForMultiEdit.type,
                userTypeId: this.userForMultiEdit.id,
                craft_abbreviation: this.userForMultiEdit.craft_abbreviation,
                shiftsToHandle: {
                    assignToShift: [
                        ...(shiftQualificationId != null
                            ? [{ shiftId, shiftQualificationId }]
                            : [{ shiftId }]), // ohne Quali-Key
                    ],
                    removeFromShift: [],
                },
            };
            return axios.post(route('shift.multi.edit.save'), payload);
        },

        async persistRemove(shiftId) {
            return axios.post(route('shift.multi.edit.save'), {
                userType: this.userForMultiEdit.type,
                userTypeId: this.userForMultiEdit.id,
                craft_abbreviation: this.userForMultiEdit.craft_abbreviation,
                shiftsToHandle: {
                    assignToShift: [],
                    removeFromShift: [shiftId],
                },
            });
        },
        enqueueSave(taskFn) {
            const session = this.multiEditSessionId;
            this.saveQueue = this.saveQueue
                .then(() => {
                    if (session !== this.multiEditSessionId) return; // User gewechselt -> Task verwerfen
                    return taskFn();
                })
                .catch((e) => {
                    if (session !== this.multiEditSessionId) return; // verwerfen
                    return taskFn(); // alten Fehler nicht blockieren lassen
                });
            return this.saveQueue;
        },
        isSaving(shiftId) {
            return this.savingShiftIds.has(shiftId);
        },
        showNotice(kind, title, message, { timeout = 3500 } = {}) {
            // evtl. i18n Keys direkt durchreichen: this.$t?.(title) ?? title
            if (this.notice._timeoutId) clearTimeout(this.notice._timeoutId);
            this.notice.kind = kind;
            this.notice.title = this.$t?.(title) ?? title;
            this.notice.message = this.$t?.(message) ?? message;
            this.notice.show = true;
            this.notice._timeoutId = setTimeout(() => { this.notice.show = false; }, timeout);
        },
        hideNotice() {
            if (this.notice._timeoutId) clearTimeout(this.notice._timeoutId);
            this.notice.show = false;
            this.notice._timeoutId = null;
        },
        saveShiftQualificationFilter(event) {
            let isChecked = event.target.checked,
                shiftQualificationId = Number.parseInt(event.target.value);

            if (!isChecked) {
                this.userShiftPlanShiftQualificationFilters.splice(
                    this.userShiftPlanShiftQualificationFilters.findIndex((id) => id === shiftQualificationId),
                    1
                );
            } else {
                this.userShiftPlanShiftQualificationFilters.push(shiftQualificationId);
            }

            router.patch(
                route(
                    'user.update.show_shift-qualifications',
                    {
                        user: this.$page.props.auth.user.id
                    }
                ),
                {
                    show_qualifications: this.userShiftPlanShiftQualificationFilters
                },
                {
                    preserveScroll: true,
                    preserveState: true
                }
            );
        },
    },
    beforeUnmount() {
        window.removeEventListener('resize', this._onWindowResize);
        document.removeEventListener('mousemove', this.resizing);
        document.removeEventListener('mouseup', this.stopResize);
        if (this.originalVisit) {
            router.visit = this.originalVisit;
        }
        this.applyUserOverviewHeight();
    },
    beforeDestroy() {
        this.$refs.shiftPlan.removeEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.removeEventListener('scroll', this.syncScrollUserOverview);
    },
    watch: {
        showUserOverview: {
            handler() {
                this.updateLayout();
            },
            deep: true
        },
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
