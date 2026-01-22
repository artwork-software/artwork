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
            <div class="bg-white grow">

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


            <div class="z-40 min-h-0" :style="{ '--dynamic-height': showUserOverview ? windowHeight + 'px' : '100%' }">
                <div
                    class="min-h-0 h-[calc(var(--dynamic-height))]"
                    :class="[isFullscreen ? '' : '']"
                >
                    <Virtual2DGridWithHeader
                        ref="shiftGridRef"
                        class="h-full"
                        :rows="shiftRows"
                        :cols="shiftCols"
                        :row-height="shiftRowHeight"
                        :col-width="shiftColWidth"
                        :sticky-col-width="shiftLeftWidth"
                        :header-height="shiftHeaderHeight"
                    >
                        <!-- Corner (oben links) -->
                        <template #corner>
                            <div :style="{ width: shiftLeftWidth + 'px' }" class="bg-artwork-navigation-background"></div>
                        </template>

                        <!-- Column Header (Tage) -->
                        <template #colHeader="{ day }">
                            <div
                                class="h-full w-full flex items-center justify-between
                                   px-2 text-[11px] leading-none text-white
                                   bg-artwork-navigation-background/95 backdrop-blur
                                   border-b border-white/10 border-r
                                   hover:bg-artwork-navigation-background transition-colors"
                            >
                                <!-- ExtraRow (KW) -->
                                <div v-if="day.isExtraRow" class="flex items-center gap-1">
                                  <span class="text-[10px] font-semibold tracking-wide opacity-90">
                                    KW {{ day.weekNumber }}
                                  </span>
                                </div>

                                <!-- Normal day -->
                                <div v-else class="flex items-center justify-between w-full gap-2 min-w-0">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1 min-w-0">
                                      <span class="font-semibold truncate">
                                        {{ day.dayString }}
                                      </span>
                                            <span class="opacity-70">·</span>
                                            <span class="opacity-90 tabular-nums truncate">
                                        {{ day.fullDay }}
                                      </span>
                                        </div>
                                    </div>

                                    <!-- Holiday badge -->
                                    <div v-if="day.holidays?.length" class="shrink-0">
                                        <HolidayToolTip>
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded-full
                   bg-white/10 hover:bg-white/15
                   border border-white/10
                   px-2 py-0.5 text-[10px] font-medium"
                                            >
                                                <PropertyIcon name="IconSparkles" class="h-3 w-3 opacity-90" />
                                                <span class="tabular-nums">{{ day.holidays.length }}</span>
                                            </button>

                                            <div class="space-y-1 divide-y divide-dashed divide-white/20">
                                                <div
                                                    v-for="holiday in day.holidays"
                                                    :key="holiday.date || holiday.name"
                                                    class="pt-1 text-[11px]"
                                                >
                                                    <div :style="{ color: holiday.color }" class="font-semibold">
                                                        {{ holiday.name }}
                                                    </div>
                                                    <div class="text-white/80 text-[10px]" v-if="holiday.subdivisions?.length">
                                                        {{ holiday.subdivisions.map((p:any) => p).join(', ') }}
                                                    </div>
                                                    <div class="text-white/70 text-[10px]" v-else>
                                                        {{ $t('Germany-wide') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </HolidayToolTip>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Row Header (Room Name) -->
                        <template #rowHeader="{ room, rowIndex }">
                            <div
                                class="xsDark h-full flex items-center"
                                :class="[rowIndex % 2 === 0 ? 'bg-background-gray' : 'bg-secondary-hover']"
                                :style="{ width: shiftLeftWidth + 'px', maxWidth: shiftLeftWidth + 'px' }"
                            >
                                <div class="flex items-center font-semibold w-full">
                                    <span class="pl-3">{{ room.roomName }}</span>
                                </div>
                            </div>
                        </template>

                        <!-- Cell -->
                        <template #cell="{ room, day }">
                            <div
                                class="day-container relative h-full w-full align-top px-[1px] border-gray-400"
                                :class="[
        day.isWeekend ? 'bg-backgroundGray' : 'bg-white',
        usePage().props.auth.user.calendar_settings.expand_days ? '' : 'h-full',
      ]"
                            >
                                <!-- MultiEditCalendar Overlay -->
                                <div
                                    v-if="!day.isExtraRow && multiEditModeCalendar"
                                    class="absolute inset-0 z-100"
                                    :class="[
                                          multiEditModeCalendar && !checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId)
                                            ? 'bg-gray-950 opacity-30 hover:bg-opacity-0 hover:border-opacity-100 hover:border-2 border-dashed transition-all duration-150 ease-in-out cursor-pointer border-artwork-buttons-create'
                                            : '',
                                          checkIfRoomAndDayIsInMultiEditCalendar(day.fullDay, room.roomId) ? 'border' : '',
                                    ]"
                                    @click="addDayAndRoomToMultiEditCalendar(day.fullDay, room.roomId)"
                                ></div>

                                <!-- ExtraRow -->
                                <div v-if="day.isExtraRow" class="mb-3 h-full w-full bg-background-gray2 border-gray-800"></div>

                                <!-- Normal cell -->
                                <div
                                    v-else
                                    class="cell group relative"
                                    :class="usePage().props.auth.user.calendar_settings.expand_days ? 'min-h-12 h-full' : 'h-full overflow-y-auto'"
                                >
                                    <!-- Project Groups in Events -->
                                    <template v-if="usePage().props.auth.user.calendar_settings.display_project_groups && room.content?.[day.fullDay]?.events">
                                        <template
                                            v-for="group in getAllProjectGroupsInEventsByDay(room.content[day.fullDay].events)"
                                            :key="group.id"
                                        >
                                            <Link
                                                :disabled="checkIfUserIsAdminOrInGroup(group)"
                                                :href="route('projects.tab', { project: group.id, projectTab: firstProjectShiftTabId })"
                                                class="mb-0.5 flex items-center gap-x-1 rounded-lg bg-artwork-navigation-background px-2 py-1 text-xs font-bold text-white"
                                            >
                                                <PropertyIcon :name="group.icon" class="size-4" />
                                                <span>{{ group.name }}</span>
                                            </Link>
                                        </template>
                                    </template>

                                    <!-- Events -->
                                    <template v-if="room.content?.[day.fullDay]?.events">
                                        <div v-for="event in room.content[day.fullDay].events" :key="event.id || event.uuid || event.name" class="mb-1">
                                            <SingleEventInShiftPlan
                                                v-if="!checkIfEventHasShiftsToDisplay(event)"
                                                :event="event"
                                                :day="day"
                                                :firstProjectShiftTabId="firstProjectShiftTabId"
                                            />
                                        </div>
                                    </template>

                                    <!-- Shifts -->
                                    <div class="space-y-0.5">
                                        <template v-if="room.content?.[day.fullDay]?.shifts?.length">
                                            <template
                                                v-for="group in groupShiftsByProject(room.content[day.fullDay].shifts, day.fullDay)"
                                                :key="group.projectId ?? `no-project-${day.fullDay}`"
                                            >
                                                <div
                                                    class="rounded-lg border duration-200 ease-in-out"
                                                    :class="group.project ? 'border-sky-300 bg-sky-50/80' : 'border-gray-200 bg-gray-50'"
                                                >
                                                    <div
                                                        v-if="group.project"
                                                        class="flex justify-between items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-sky-800"
                                                    >
                                                        <span>{{ group.project.name }}</span>
                                                        <span class="text-[10px] text-sky-600">({{ group.shifts.length }})</span>
                                                    </div>

                                                    <div
                                                        class="space-y-0.5 px-1"
                                                        :class="group.project ? 'divide-y divide-sky-100' : 'divide-y divide-gray-100'"
                                                    >
                                                        <div
                                                            v-for="shift in group.shifts"
                                                            :key="shift.id || shift.dwId || shift.uuid"
                                                            class="rounded-lg duration-200 ease-in-out"
                                                            :class="group.project ? 'hover:bg-sky-100' : 'hover:bg-gray-100'"
                                                        >
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
                                                                :highlightedShiftId="highlightedShiftId"
                                                                @highlight-shift-users="highlightUsersOfShift"
                                                                @hover-shift-users="setHoverUsersOfShift"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </template>
                                    </div>

                                    <div class=" group-hover:inline-flex hidden absolute bottom-2 right-1 z-20 gap-x-1">

                                        <ToolTipComponent
                                            direction="left"
                                            :tooltip-text="$t('Add Shift based on templates')"
                                            icon="IconCopyPlus"
                                            icon-size="size-4"
                                            v-if="can('can plan shifts') || is('artwork admin')"
                                            @click="openAddShiftByPresetOrGroup(day, room)"
                                            classes-button="pointer-events-auto -1 border border-zinc-200 z-20 inline-flex
                    items-center justify-center cursor-pointer gap-1 rounded-md size-7 text-sm font-medium
                    ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0 transition duration-200 ease-in-out"
                                        />

                                        <ToolTipComponent
                                            direction="left"
                                            :tooltip-text="$t('Add Shift')"
                                            icon="IconPlus"
                                            icon-size="size-4"
                                            v-if="!multiEditModeCalendar && can('can plan shifts') || is('artwork admin')"
                                            @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)"
                                            classes-button="pointer-events-auto -1 border border-zinc-200 z-20 inline-flex
                    items-center justify-center cursor-pointer gap-1 rounded-md size-7 text-sm font-medium
                    ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0 transition duration-200 ease-in-out"
                                        />
                                    </div>

                                    <!-- Add button -->

                                </div>
                            </div>
                        </template>
                    </Virtual2DGridWithHeader>
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
                    <div v-show="showUserOverview"
                        class="relative z-20 w-[97%] overflow-y-scroll bg-artwork-navigation-background "
                        :style="showUserOverview ? { height: userOverviewHeight + 'px' } : { height: 20 + 'px' }">
                        <div class="fixed z-20 flex w-full items-center justify-between bg-artwork-navigation-background pr-9 py-3">
                            <div class="flex items-center justify-end gap-x-3">
                                <SwitchIconTooltip v-if="can('can plan shifts') || is('artwork admin')" v-model="multiEditMode" :tooltip-text="$t('Edit')" size="md" @change="toggleMultiEditMode" icon="IconPencil"/>
                                <ToolTipComponent
                                    direction="right"
                                    :tooltip-text="$t('Create individual time series')"
                                    icon="IconClockShield"
                                    icon-size="h-5 w-5"
                                    v-if="can('can plan shifts') || is('artwork admin')"
                                    @click="showIndividualTimeSeriesModal = true"
                                    classesButton="ui-button-small"
                                />
                                <div v-if="dayServices && selectedDayService && (can('can plan shifts') || is('artwork admin'))" class="flex items-center gap-x-2">
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
                                                                      class="ml-2 h-4 w-4 text-white"/>
                                                        <PropertyIcon name="IconChevronUp" v-else class="ml-2 h-4 w-4 text-white"/>
                                                    </div>
                                                </div>
                                                <div v-if="showShiftQualificationFilter">
                                                    <template v-for="shiftQualification in shiftQualifications":key="shiftQualification.id">
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
                                                    </template>
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


                        <Virtual2DGrid
                            ref="userOverviewGridRef"
                            :rows="gridRows"
                            :cols="gridCols"
                            :row-height="rowHeight"
                            :col-width="202"
                            :sticky-col-width="191.5"
                            class="h-full"
                            :top-padding="10"
                        >
                            <template #rowHeader="{ row }">
                                <div v-if="row.kind === 'craft'" class="w-full px-2">
                                    <div
                                        class="xsLight font-lexend! flex w-96 justify-between cursor-pointer pb-1 "
                                        @click="changeCraftVisibility(row.craft.id)"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span>{{ row.craft.name }}</span>
                                            <span
                                                v-if="row.craft.users?.length > 0"
                                                class="inline-flex items-center rounded-full bg-white/10 gap-x-2 px-2 py-0.5 text-[9px] font-normal text-gray-100"
                                            >
            {{ row.craft.users.length }}
            <span class="inline-block h-2 w-2 rounded-full bg-gray-100"></span>
          </span>
                                        </div>

                                        <PropertyIcon
                                            name="IconChevronDown"
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="usePage().props.auth.user.opened_crafts?.includes(row.craft.id) ? 'rotate-180' : ''"
                                        />
                                    </div>
                                </div>

                                <div v-else class="w-full">
                                    <DragElement
                                        v-if="!highlightMode && !multiEditMode"
                                        :item="row.worker.element"
                                        :work-time-balance="row.worker.workTimeBalance"
                                        :type="row.worker.type"
                                        :color="row.craft.color"
                                        :craft="row.craft"
                                        :is-managing-craft="row.worker.element.managing_craft_ids.includes(row.craft.id)"
                                    />
                                    <MultiEditUserCell
                                        v-else-if="multiEditMode && !highlightMode"
                                        :item="row.worker.element"
                                        :work-time-balance="row.worker.workTimeBalance"
                                        :type="row.worker.type"
                                        :userForMultiEdit="userForMultiEdit"
                                        :multiEditMode="multiEditMode"
                                        @addUserToMultiEdit="addUserToMultiEdit"
                                        :color="row.craft.color"
                                        :craft-id="row.craft.id"
                                        :craft="row.craft"
                                        :multi-edit-cell-by-day-and-user="multiEditCellByDayAndUser"
                                        :is-managing-craft="row.worker.element.managing_craft_ids.includes(row.craft.id)"
                                    />
                                    <HighlightUserCell
                                        v-else
                                        :highlighted-user="idToHighlight ? (idToHighlight === row.worker.element.id && row.worker.type === typeToHighlight) : false"
                                        :item="row.worker.element"
                                        :work-time-balance="row.worker.workTimeBalance"
                                        :type="row.worker.type"
                                        @highlightShiftsOfUser="highlightShiftsOfUser"
                                        :color="row.craft.color"
                                        :is-managing-craft="row.worker.element.managing_craft_ids.includes(row.craft.id)"
                                    />
                                </div>
                            </template>

                            <template #cell="{ row, day }">
                                <!-- Craft row: keine Zellen -->
                                <div v-if="row.kind === 'craft'" class="h-full w-full"></div>

                                <!-- Worker row -->
                                <div v-else class="relative h-full w-full">
                                    <!-- ExtraRow / Wochenarbeitszeit -->
                                    <div
                                        v-if="day.isExtraRow"
                                        class="shiftCell flex h-full items-center justify-center overflow-hidden rounded-lg bg-gray-50/30 p-2 text-center text-white"
                                        :class="cellWrapperClass(row, day)"
                                    >
                                        <div :class="$page.props.auth.user.compact_mode ? 'flex items-center gap-x-1' : ''">
                                            <div class="text-[9px]">Arbeitszeit KW {{ day.weekNumber }}</div>
                                            <div
                                                class="font-lexend text-xs"
                                                :class="row.worker?.weeklyWorkingHours?.[day.weekNumber]?.isMinus ? 'text-red-100' : 'text-green-100'"
                                            >
                                                {{ row.worker?.weeklyWorkingHours?.[day.weekNumber]?.difference }}
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Normaler ShiftCell -->
                                    <div
                                        v-else
                                        class="relative h-full w-full"
                                        :class="cellWrapperClass(row, day)"
                                        @click="handleCellClick(row.worker, day)"
                                    >
                                        <ShiftPlanCell
                                            :user="row.worker"
                                            :day="day"
                                            :classes="[shiftPlanCellInnerClass(row, day)]"
                                        />

                                        <!-- DayServices (Icons) -->
                                        <div
                                            v-if="getDayServicesForCell(row.worker, day)"
                                            class="absolute right-2 top-1/2 flex -translate-y-1/2 transform"
                                        >
                                            <div
                                                v-for="(svc, idx) in getDayServicesForCell(row.worker, day)"
                                                :key="svc.id || idx"
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-white p-0.5"
                                                :class="idx > 0 ? '-ml-3' : ''"
                                            >
                                                <ToolTipComponent
                                                    :tooltip-text="svc.name"
                                                    :icon="svc.icon"
                                                    icon-size="h-4 w-4"
                                                    :icon-style="{ color: svc.hex_color }"
                                                    :classes-button="'mt-0'"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Virtual2DGrid>
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
                :crafts="crafts"
                :initialStartDate="dateValue[0]"
                :initialEndDate="dateValue[1]"
                @close="showHistoryModal = false"
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

    <AddShiftsByPresetsAndGroupsModal
        :single-shift-presets="page.props.singleShiftPresets"
        :preset-groups="shiftGroupPresets"
        :day="dayForPreset"
        :room="roomForPreset"
        :projects="props.projects"
        :initial-project-id="props.projectId"
        :initial-project="pageProps?.currentProject ?? null"
        v-if="showAddShiftByPresetOrGroupModal"
        @close="showAddShiftByPresetOrGroupModal = false"
        />
</template>

<script setup lang="ts">
import 'vue-virtual-scroller/dist/vue-virtual-scroller.css'
import Permissions from '@/Mixins/Permissions.vue'
import axios from 'axios'
import {Link, router, usePage} from '@inertiajs/vue3'
import ShiftPlanFunctionBar from '@/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue'
import ShiftHeader from '@/Pages/Shifts/ShiftHeader.vue'
import {MenuItem} from '@headlessui/vue'
import BaseFilter from '@/Layouts/Components/BaseFilter.vue'
import {
    computed,
    defineAsyncComponent,
    getCurrentInstance, inject,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    shallowRef,
    watch,
    watchEffect
} from 'vue'
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
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import SingleEventInShiftPlan from "@/Pages/Shifts/Components/SingleEventInShiftPlan.vue";
import SingleShiftInRoom from "@/Pages/Shifts/Components/ShiftWithoutEventComponents/SingleShiftInRoom.vue";
import DayServiceFilter from "@/Components/Filter/DayServiceFilter.vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import MultiEditUserCell from "@/Pages/Shifts/Components/MultiEditUserCell.vue";
import HighlightUserCell from "@/Pages/Shifts/Components/HighlightUserCell.vue";
import ShiftPlanCell from "@/Pages/Shifts/Components/ShiftPlanCell.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import Virtual2DGrid from "@/Pages/Shifts/Components/Virtual2DGrid.vue";
import Virtual2DGridWithHeader from "@/Pages/Shifts/Components/Virtual2DGridWithHeader.vue";
import AddShiftsByPresetsAndGroupsModal from "@/Pages/Shifts/Components/AddShiftsByPresetsAndGroupsModal.vue";

defineOptions({
    name: 'ShiftPlan',
    mixins: [Permissions],
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
    shiftGroupPresets?: any
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
    shiftGroupPresets: () => ({})
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

const days = shallowRef<any[]>(props.days ?? [])
const rooms = shallowRef([])
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

const dayForPreset = ref()
const roomForPreset = ref()
const showAddShiftByPresetOrGroupModal = ref(false)
const shiftsToHandleOnMultiEdit = reactive<{ assignToShift: any[]; removeFromShift: any[] }>({
    assignToShift: [],
    removeFromShift: [],
})

// closed crafts filter bei usePage().props.auth.user.opened_crafts
const closedCrafts = computed(() => {
    const openedCrafts: number[] = usePage().props.auth.user.opened_crafts || []
    return props.crafts
        .map((c) => c.id)
        .filter((craftId) => !openedCrafts.includes(craftId))
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

type HighlightSelectionKind = 'user' | 'shift' | null
const highlightSelectionKind = ref<HighlightSelectionKind>(null)

const shiftUsersToHighlight = ref<{
    userIds: Array<number | string>
    freelancerIds: Array<number | string>
    providerIds: Array<number | string>
} | null>(null)

const hoverShiftUsersToHighlight = ref<{
    userIds: Array<number | string>
    freelancerIds: Array<number | string>
    providerIds: Array<number | string>
} | null>(null)

// optional (falls du später Shift oben optisch markieren willst)
const highlightedShiftId = ref<number | string | null>(null)

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
const shiftClickedInHighlightMode = ref(null)
const showUserOverview = ref(true)
const pageProps = usePage().props
const auth = pageProps.auth.user;

const {userOverviewHeight, windowHeight, startResize, updateLayout} = useUserOverviewLayout(showUserOverview, {
    headerHeight: 100,
    topGap: 0,
    minTop: 100,
    minMain: 200,
    pageProps,
})

const rowHeight = computed(() => {
    return usePage().props.auth.user.compact_mode ? 32 : 48 // px
})

const userOverviewGridRef = shallowRef<InstanceType<typeof Virtual2DGrid> | null>(null)
const userOverviewEl = shallowRef<HTMLElement | null>(null)
const shiftGridRef = shallowRef<InstanceType<typeof Virtual2DGridWithHeader> | null>(null)
const shiftPlanEl = shallowRef<HTMLElement | null>(null)
const daysRef = shallowRef<any[]>(days.value)
const currentDayRef = shallowRef<any>(null)

const {attach, detach} = useSyncedHorizontalScroll(
    shiftPlanEl,
    userOverviewEl,
    daysRef,
    currentDayRef,
    {roomNameOffsetPx: 200},
)

watchEffect(() => {
    shiftPlanEl.value =
        shiftGridRef.value?.getViewportEl?.() ??
        shiftGridRef.value?.viewportEl?.value ??
        null
})

const openAddShiftByPresetOrGroup = (day, roomId) => {
    roomForPreset.value = roomId
    dayForPreset.value = day


    showAddShiftByPresetOrGroupModal.value = true
}

const shiftPlanArrayRef = computed<any[]>(() => normalizeShiftPlan(newShiftPlanData.value))

const shiftRows = computed(() => {
    const arr = shiftPlanArrayRef.value
    return arr.map((room: any, idx: number) => ({
        key: room.roomId ?? room.room_id ?? room.id ?? `room_${idx}`,
        data: room,
    }))
})

const shiftCols = computed(() =>
    (days.value ?? [])?.map((d: any) => ({
        key: d.isExtraRow ? `extra_${d.weekNumber}` : d.fullDay,
        data: d,
    }))
)

const shiftHeaderHeight = 32 // wie dein h-8
const shiftColWidth = 202
const shiftLeftWidth = 191.5

const shiftRowHeight = computed(() =>
    usePage().props.auth.user.calendar_settings.expand_days ? 360 : 112
)


watchEffect(() => {
    userOverviewEl.value =
        userOverviewGridRef.value?.getViewportEl?.() ??
        userOverviewGridRef.value?.viewportEl?.value ??
        null
})

watch(
    [() => shiftPlanEl.value, () => userOverviewEl.value],
    ([a, b]) => {
        if (a && b) attach()
    },
    { immediate: true },
)

type ShiftGroup = {
    project: any | null
    projectId: number | null
    shifts: any[]
}

function groupShiftsByProject(shifts: any[] = [], dayLabel: string): ShiftGroup[] {
    const groupsMap = new Map<string, ShiftGroup>()
    const PROJECTLESS_KEY = 'no_project'

    function toIsoDateFromDayLabel(label: string): string {
        // "02.11.2025" -> "2025-11-02"
        const [dd, mm, yyyy] = label.split('.')
        return `${yyyy}-${mm}-${dd}`
    }

    const getShiftStartMs = (shift: any): number => {
        // Datum: aus startDate (YYYY-MM-DD ...) oder fallback aus dayLabel
        const baseDateStr =
            typeof shift.startDate === 'string'
                ? shift.startDate.slice(0, 10) // "2025-11-02"
                : toIsoDateFromDayLabel(dayLabel)

        const timeStr = (shift.start ?? '00:00').toString().slice(0, 5) // "14:00"
        const iso = `${baseDateStr}T${timeStr}:00`
        const ms = new Date(iso).getTime()
        return Number.isFinite(ms) ? ms : Number.MAX_SAFE_INTEGER
    }

    const getShiftEndMs = (shift: any): number => {
        const baseDateStr =
            typeof shift.endDate === 'string'
                ? shift.endDate.slice(0, 10)
                : (typeof shift.startDate === 'string'
                    ? shift.startDate.slice(0, 10)
                    : toIsoDateFromDayLabel(dayLabel))

        const timeStr = (shift.end ?? '00:00').toString().slice(0, 5)
        const iso = `${baseDateStr}T${timeStr}:00`
        const ms = new Date(iso).getTime()
        return Number.isFinite(ms) ? ms : Number.MAX_SAFE_INTEGER
    }

    const getGroupEarliestStartMs = (g: ShiftGroup): number => {
        if (!g.shifts?.length) return Number.MAX_SAFE_INTEGER
        // min() über alle shifts in der Gruppe
        let min = Number.MAX_SAFE_INTEGER
        for (const s of g.shifts) {
            const t = getShiftStartMs(s)
            if (t < min) min = t
        }
        return min
    }

    // 1) Gruppieren
    for (const shift of shifts) {
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

    // 2) Schichten innerhalb der Gruppe zeitlich sortieren
    for (const g of groups) {
        g.shifts.sort((a: any, b: any) => {
            const aStart = getShiftStartMs(a)
            const bStart = getShiftStartMs(b)
            if (aStart !== bStart) return aStart - bStart

            const aEnd = getShiftEndMs(a)
            const bEnd = getShiftEndMs(b)
            if (aEnd !== bEnd) return aEnd - bEnd

            return (a.id ?? 0) - (b.id ?? 0)
        })
    }

    // 3) Gruppen nach frühester Startzeit sortieren (nicht A-Z)
    return groups.sort((a, b) => {
        // "ohne Projekt" ans Ende
        if (a.project && !b.project) return -1
        if (!a.project && b.project) return 1

        const aMin = getGroupEarliestStartMs(a)
        const bMin = getGroupEarliestStartMs(b)
        if (aMin !== bMin) return aMin - bMin

        // Tie-Breaker (stabil): projectId / dann Name
        const aId = a.projectId ?? Number.MAX_SAFE_INTEGER
        const bId = b.projectId ?? Number.MAX_SAFE_INTEGER
        if (aId !== bId) return aId - bId

        return (a.project?.name || '').localeCompare(b.project?.name || '')
    })
}


const monthObserver = ref<IntersectionObserver | null>(null)


function setCurrentDayRef(day: any) {
    currentDayRef.value = day
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

const {getSortEnumTranslation} = useSortEnumTranslation()


function normalizeShiftPlan(sp: any[] | Record<string, any> | null | undefined): any[] {
    if (!sp) return []
    return Array.isArray(sp) ? sp : Object.values(sp)
}



function pickInitialCurrentDay(daysList: any[]) {
    return daysList.find((d: any) => !d.isExtraRow) ?? null
}

async function initializeShiftPlan() {
    const hasInitialDays = Array.isArray(props.days) && props.days.length > 0

    const hasInitialShiftPlan =
        !!props.shiftPlan &&
        (
            Array.isArray(props.shiftPlan)
                ? props.shiftPlan.length > 0
                : Object.keys(props.shiftPlan).length > 0
        )

    if (!hasInitialDays || !hasInitialShiftPlan) {
        const { data } = await axios.get(route('shift.plan.all'), {
            params: {
                start_date: props.dateValue?.[0],
                end_date: props.dateValue?.[1],
                projectId: props.projectId,
                isInProjectView: !!props.projectId, // oder ein echtes UI-Flag
            },
        })

        const loadedDays = data.days ?? []
        const loadedShiftPlan = data.shiftPlan ?? []

        days.value = loadedDays
        daysRef.value = loadedDays
        rooms.value = normalizeShiftPlan(loadedShiftPlan)
        newShiftPlanData.value = loadedShiftPlan
    } else {
        const initialDays = props.days ?? []
        const initialShiftPlan = props.shiftPlan ?? []

        days.value = initialDays
        daysRef.value = initialDays

        rooms.value = normalizeShiftPlan(initialShiftPlan)
        newShiftPlanData.value = initialShiftPlan
    }

    const initialCurrentDay = pickInitialCurrentDay(days.value)
    currentDayOnView.value = initialCurrentDay
    currentDayRef.value = initialCurrentDay
}


onMounted(async () => {
    await initializeShiftPlan()

    // currentDayRef folgt immer currentDayOnView
    watch(
        () => currentDayOnView.value,
        (v) => {
            currentDayRef.value = v
        },
    )

    // Tage aus Props aktualisieren (nur Referenz, kein deep-Watch – schneller)
    watch(
        () => props.days,
        (v) => {
            const newDays = (v ?? []) as any[]
            days.value = newDays
            daysRef.value = newDays

            // falls sich Tage komplett ändern, ggf. currentDay neu setzen
            currentDayOnView.value = pickInitialCurrentDay(newDays)
        },
    )

    attach()

    const ShiftCalendarListener = useShiftCalendarListener(shiftPlanArrayRef)
    ShiftCalendarListener.init()

    setupInertiaNavigationGuard()

    // wenn nur ein kurzer Hinweis: direkt leeren, Timeout möglichst klein halten
    setTimeout(() => {
        showCalendarWarning.value = ''
    }, 3000)
})


onBeforeUnmount(() => {
    detach()
    monthObserver.value?.disconnect()
    monthObserver.value = null

    if (originalVisit.value) {
        router.visit = originalVisit.value
    }
})

function workerMapKey(row: any) {
    return `${row.worker.element.id}_${row.worker.type}`
}

function isHighlightedRow(row: any) {
    if (!hasActiveHighlightSelection.value) return false

    if (highlightSelectionKind.value === 'user') {
        return !!idToHighlight.value &&
            idToHighlight.value === row.worker.element.id &&
            row.worker.type === typeToHighlight.value
    }

    if (highlightSelectionKind.value === 'shift') {
        return isRowInIds(row, shiftUsersToHighlight.value)
    }

    return false
}

function isActiveMultiEditRow(row: any) {
    const u = userForMultiEdit.value
    return !!u &&
        u.id === row.worker.element.id &&
        u.type === row.worker.type &&
        u.craftId === row.craft.id
}

function isSelectedMultiEditCell(row: any, day: any) {
    const entry = multiEditCellByDayAndUser.value?.[workerMapKey(row)]
    if (!entry) return false
    return entry.type === row.worker.type && entry.days?.includes(day.withoutFormat)
}

function cellWrapperClass(row: any, day: any) {
    const classes: string[] = []

    if (highlightMode.value) {
        classes.push(isHighlightedRow(row) ? '' : 'opacity-30')
    }

    if (multiEditMode.value) {
        classes.push(isActiveMultiEditRow(row) ? '' : 'opacity-30')
    }

    if (multiEditMode.value && isSelectedMultiEditCell(row, day)) {
        classes.push('opacity-100! overflow-hidden!')
    }

    if (highlightMode.value && hasActiveHighlightSelection.value) {
        classes.push(isHighlightedRow(row) ? '' : 'opacity-30')
    }

    if (hoverShiftUsersToHighlight.value && isRowInIds(row, hoverShiftUsersToHighlight.value)) {
        classes.push('!opacity-100')
    }

    return classes
}

function shiftPlanCellInnerClass(row: any, day: any) {
    return (multiEditMode.value && isSelectedMultiEditCell(row, day)) ? '!opacity-20' : ''
}

function getDayServicesForCell(worker: any, day: any) {
    const key = day.withoutFormat ?? day.fullDay
    if (!key) return null
    return worker?.dayServices?.[key] ?? null
}



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

function openEditShiftModal(shift: any, mode = 'normal') {
    if(mode === 'normal'){
        shiftToEdit.value = shift
        showAddShiftModal.value = true
    } else {
        shiftClickedInHighlightMode.value = shift.id;
    }
}

function openAddShiftForRoomAndDay(day: any, roomId: number) {
    shiftToEdit.value = null
    roomForShiftAdd.value = roomId
    dayForShiftAdd.value = day
    showAddShiftModal.value = true
}

function closeAddShiftModal(success = false, shift = null) {
    if (success && shift) {
        // Find and update the shift in newShiftPlanData to ensure immediate UI update
        for (const room of newShiftPlanData.value) {
            for (const day in room.content) {
                const dayData = room.content[day];

                // Update in room shifts
                const shiftIndex = dayData.shifts.findIndex(s => s.id === shift.id);
                if (shiftIndex !== -1) {
                    dayData.shifts[shiftIndex] = shift;
                }

                // Update in events
                for (const event of dayData.events) {
                    const eventShiftIndex = event.shifts.findIndex(s => s.id === shift.id);
                    if (eventShiftIndex !== -1) {
                        event.shifts[eventShiftIndex] = shift;
                    }
                }
            }
        }
    }
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

/**
 * Sort-Helfer: einheitlich nach Name sortieren
 */
function getWorkerSortName(worker: any, useFirstNameForSort: boolean): string {
    if (isWorkerServiceProvider(worker)) {
        return worker.element.provider_name ?? ''
    }

    if (!useFirstNameForSort) {
        return worker.element.first_name ?? ''
    }

    return worker.element.last_name ?? ''
}

function sortWorkersByName(workers: any[], useFirstNameForSort: boolean, direction: 1 | -1) {
    return [...workers].sort((a, b) => {
        const aName = getWorkerSortName(a, useFirstNameForSort)
        const bName = getWorkerSortName(b, useFirstNameForSort)

        if (aName < bName) return -1 * direction
        if (aName > bName) return 1 * direction
        return 0
    })
}

function filterNonManagingWorkersByShiftQualificationFilter(workers: any[], filters: number[] | null | undefined) {
    const activeFilters = filters ?? []
    if (activeFilters.length === 0) return workers

    return workers.filter((w) => {
        const quals = w.element.shift_qualifications ?? []
        if (quals.length === 0) return false
        return quals.some((sq: any) => activeFilters.includes(sq.id))
    })
}

// -----------------------------------------------------
//  PRECOMPUTED: Drop-Worker & Craft-Zuordnungen
// -----------------------------------------------------

const page = usePage()

const shiftPlanUserSortById = computed<string | null>(() => page.props.auth.user.shift_plan_user_sort_by_id)


type GridRow =
    | { key: string; kind: 'craft'; craft: any }
    | { key: string; kind: 'worker'; craft: any; worker: any }

const gridRows = computed<GridRow[]>(() => {
    const rows: GridRow[] = []

    for (const craft of craftsToDisplay.value) {
        rows.push({ key: `craft_${craft.id}`, kind: 'craft', craft })

        if (closedCrafts.value.includes(craft.id)) continue

        for (const w of craft.users) {
            rows.push({
                key: `worker_${craft.id}_${w.key}`, // w.key ist schon stabil
                kind: 'worker',
                craft,
                worker: w,
            })
        }
    }

    return rows
})

const gridCols = computed(() => days.value) // dein day-array (inkl. extraRow) bleibt “Columns”

/**
 * Alle DropWorker nur EINMAL aus Props bauen
 */
const dropWorkers = computed<any[]>(() => {
    const users: any[] = []

    ;(props.usersForShifts ?? []).forEach((user: any) => {
        if (!showFreelancers.value && user.user.is_freelancer) return

        users.push({
            element: user.user,
            type: 0,
            workTimeBalance: user.workTimeBalance,
            vacations: user.vacations,
            assigned_craft_ids: user.user.assigned_craft_ids ?? [],
            availabilities: user.availabilities,
            weeklyWorkingHours: user.weeklyWorkingHours,
            dayServices: user.dayServices,
            individual_times: user.individual_times,
            shift_comments: user.shift_comments,
            key: `u_${user.user.id}_0`,
        })
    })

    if (showFreelancers.value) {
        ;(props.freelancersForShifts ?? []).forEach((freelancer: any) => {
            users.push({
                element: freelancer.freelancer,
                type: 1,
                vacations: freelancer.vacations,
                assigned_craft_ids: freelancer.freelancer.assigned_craft_ids ?? [],
                availabilities: freelancer.availabilities,
                dayServices: freelancer.dayServices,
                individual_times: freelancer.individual_times,
                shift_comments: freelancer.shift_comments,
                weeklyWorkingHours: freelancer.weeklyWorkingHours,
                key: `u_${freelancer.freelancer.id}_1`,
            })
        })
    }

    ;(props.serviceProvidersForShifts ?? []).forEach((sp: any) => {
        users.push({
            element: sp.service_provider,
            type: 2,
            assigned_craft_ids: sp.service_provider.assigned_craft_ids ?? [],
            dayServices: sp.dayServices,
            individual_times: sp.individual_times,
            shift_comments: sp.shift_comments,
            weeklyWorkingHours: sp.weeklyWorkingHours,
            key: `u_${sp.service_provider.id}_2`,
        })
    })

    return users
})

/**
 * Map: craftId -> Worker[]
 * statt für jeden Craft erneut über ALLE Worker zu loopen
 */
const craftWorkersMap = computed<Map<number, any[]>>(() => {
    const map = new Map<number, any[]>()

    for (const worker of dropWorkers.value) {
        const craftIds: number[] = worker.assigned_craft_ids ?? []
        for (const craftId of craftIds) {
            if (!map.has(craftId)) {
                map.set(craftId, [])
            }
            map.get(craftId)!.push(worker)
        }
    }

    return map
})

/**
 * Managing-Sets für einen Craft vorbereiten (O(n) → später O(1)-Lookups)
 */
function buildManagingSets(craft: any) {
    return {
        userIds: new Set<number>((craft.managing_users ?? []).map((m: any) => m.id)),
        freelancerIds: new Set<number>((craft.managing_freelancers ?? []).map((m: any) => m.id)),
        serviceProviderIds: new Set<number>((craft.managing_service_providers ?? []).map((m: any) => m.id)),
    }
}

function isManagingWorkerForCraft(worker: any, managingSets: ReturnType<typeof buildManagingSets>) {
    const id = worker.element?.id
    if (id == null) return false

    if (isWorkerUser(worker)) return managingSets.userIds.has(id)
    if (isWorkerFreelancer(worker)) return managingSets.freelancerIds.has(id)
    if (isWorkerServiceProvider(worker)) return managingSets.serviceProviderIds.has(id)

    return false
}

/**
 * Filter + Sortierung für einen Craft (arbeitet jetzt auf precomputed Maps)
 */
function filterAndSortWorkersOfCraft(craft: any) {
    const allWorkersOfCraft = craftWorkersMap.value.get(craft.id) ?? []

    const managingSets = buildManagingSets(craft)

    const assignedManagingWorkers: any[] = []
    const assignedNonManagingWorkers: any[] = []

    // Nur EIN Durchlauf zum Split in managing / non-managing
    for (const w of allWorkersOfCraft) {
        if (isManagingWorkerForCraft(w, managingSets)) {
            assignedManagingWorkers.push(w)
        } else {
            assignedNonManagingWorkers.push(w)
        }
    }

    const assignedNonManagingWorkersFiltered = filterNonManagingWorkersByShiftQualificationFilter(
        assignedNonManagingWorkers,
        props.userShiftPlanShiftQualificationFilters
    )

    const sortBy = shiftPlanUserSortById.value
    const useFirstName = !!props.useFirstNameForSort

    if (sortBy === null) {
        // Keine Sortierung → nur Managing zuerst
        return assignedManagingWorkers.concat(assignedNonManagingWorkersFiltered)
    }

    // Helper für intern/extern-Split nur 1x
    const intern = assignedNonManagingWorkersFiltered.filter(
        (w) => w.type === 0 && w.element?.is_freelancer !== true
    )

    const extern = assignedNonManagingWorkersFiltered.filter(
        (w) => w.element?.is_freelancer === true || w.type === 1 || w.type === 2
    )

    switch (sortBy) {
        case 'ALPHABETICALLY_NAME_ASCENDING':
            return sortWorkersByName(assignedManagingWorkers, useFirstName, 1)
                .concat(sortWorkersByName(assignedNonManagingWorkersFiltered, useFirstName, 1))

        case 'ALPHABETICALLY_NAME_DESCENDING':
            return sortWorkersByName(assignedManagingWorkers, useFirstName, -1)
                .concat(sortWorkersByName(assignedNonManagingWorkersFiltered, useFirstName, -1))

        case 'INTERN_EXTERN_ASCENDING':
            return sortWorkersByName(assignedManagingWorkers, useFirstName, 1)
                .concat(sortWorkersByName(intern, useFirstName, 1))
                .concat(sortWorkersByName(extern, useFirstName, 1))

        case 'INTERN_EXTERN_DESCENDING':
            return sortWorkersByName(assignedManagingWorkers, useFirstName, -1)
                .concat(sortWorkersByName(extern, useFirstName, -1))
                .concat(sortWorkersByName(intern, useFirstName, -1))

        default:
            return assignedManagingWorkers.concat(assignedNonManagingWorkersFiltered)
    }
}

// -----------------------------------------------------
//  craftsToDisplay & Sort-Konfig
// -----------------------------------------------------

const craftsToDisplay = computed(() => {
    const allCrafts = (props.crafts ?? []).map((craft: any) => ({
        id: craft.id,
        name: craft.name,
        abbreviation: craft.abbreviation,
        users: filterAndSortWorkersOfCraft(craft),
        color: craft?.color,
        universally_applicable: craft.universally_applicable,
    }))

    if (!props.user_filters?.craft_ids || props.user_filters.craft_ids.length === 0) {
        return allCrafts
    }

    const filterIds = props.user_filters.craft_ids
    return allCrafts.filter((c: any) => filterIds.includes(c.id))
})

const computedShiftPlanWorkerSortEnums = computed(() => {
    const nameSortEnums = ['ALPHABETICALLY_NAME_ASCENDING', 'ALPHABETICALLY_NAME_DESCENDING']
    const sortConfig: string[] = []
    const sortBy = shiftPlanUserSortById.value

    if (sortBy && nameSortEnums.includes(sortBy)) {
        // Name → als nächstes Intern/Extern + inverse Namenssortierung
        sortConfig.push('INTERN_EXTERN_ASCENDING')
        sortConfig.push(
            sortBy === 'ALPHABETICALLY_NAME_ASCENDING'
                ? 'ALPHABETICALLY_NAME_DESCENDING'
                : 'ALPHABETICALLY_NAME_ASCENDING'
        )
        return sortConfig
    }

    // Aktuell Intern/Extern → als nächstes andere Richtung + Name ASC
    if (sortBy === 'INTERN_EXTERN_ASCENDING') {
        sortConfig.push('INTERN_EXTERN_DESCENDING')
    } else {
        sortConfig.push('INTERN_EXTERN_ASCENDING')
    }

    sortConfig.push('ALPHABETICALLY_NAME_ASCENDING')

    return sortConfig
})


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
    if(can('can plan shifts') || is('artwork admin')){
        openShowUserShiftModal(user, day)
    }

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
}

function nextTimeRange() {
    const dateDifference = calculateDateDifference()
    props.dateValue[0] = dayjs(props.dateValue[0]).add(dateDifference + 1, 'day').format('YYYY-MM-DD')
    props.dateValue[1] = dayjs(props.dateValue[1]).add(dateDifference + 1, 'day').format('YYYY-MM-DD')
    updateTimes()
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

    currentDayOnView.value = days.value[scrollOffset]

    const colWidth = 202
    const leftWidth = 191.5
    const roomNameOffsetPx = 200

    const targetIndex = scrollOffset // das ist dein Index im days-array
    const x = targetIndex * colWidth



    const roomNameElement = document.getElementById('roomNameContainer_0')
    const containerRect = container.getBoundingClientRect()
    const roomNameLeft = roomNameElement ? roomNameElement.getBoundingClientRect().left : 0

    container.scrollLeft = Math.max(0, x - (roomNameOffsetPx - leftWidth))
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
    clearHighlightSelection()
}

function toggleMultiEditMode() {
    highlightMode.value = false
    dayServiceMode.value = false
    if (!multiEditMode.value) {
        userForMultiEdit.value = null
        multiEditCellByDayAndUser.value = {}
        clearHighlightSelection()
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

const hasActiveHighlightSelection = computed(() => {
    if (!highlightMode.value) return false

    if (highlightSelectionKind.value === 'user') {
        return idToHighlight.value != null && typeToHighlight.value != null
    }

    if (highlightSelectionKind.value === 'shift') {
        const ids = shiftUsersToHighlight.value
        if (!ids) return false
        return (ids.userIds?.length || 0) + (ids.freelancerIds?.length || 0) + (ids.providerIds?.length || 0) > 0
    }

    return false
})

function extractShiftUserIds(shift: any) {
    const ids = {
            userIds: [] as Array<number | string>,
            freelancerIds: [] as Array<number | string>,
            providerIds: [] as Array<number | string>,
        }

    ;(Array.isArray(shift?.users) ? shift.users : []).forEach((u: any) => ids.userIds.push(u.id))
    ;(Array.isArray(shift?.freelancer) ? shift.freelancer : (Array.isArray(shift?.freelancers) ? shift.freelancers : [])).forEach((f: any) => ids.freelancerIds.push(f.id))
    ;(Array.isArray(shift?.serviceProviders) ? shift.serviceProviders : (Array.isArray(shift?.service_providers) ? shift.service_providers : [])).forEach((p: any) => ids.providerIds.push(p.id))

    return ids
}

function highlightUsersOfShift(shift: any) {
    if (!highlightMode.value) return

    highlightSelectionKind.value = 'shift'


    idToHighlight.value = null
    typeToHighlight.value = null


    shiftUsersToHighlight.value = extractShiftUserIds(shift)
    highlightedShiftId.value = shift?.id ?? null
}

function setHoverUsersOfShift(shift: any | null) {
    if (!shift) {
        hoverShiftUsersToHighlight.value = null
        return
    }

    // Hover-Preview nur wenn MultiEdit oder Highlight aktiv ist (sonst zu noisy)
    if (!multiEditMode.value && !highlightMode.value) return

    hoverShiftUsersToHighlight.value = extractShiftUserIds(shift)
}

function isRowInIds(row: any, ids: any) {
    if (!ids || !row?.worker) return false
    const id = row.worker.element?.id
    const t = row.worker.type
    if (t === 0) return (ids.userIds || []).includes(id)
    if (t === 1) return (ids.freelancerIds || []).includes(id)
    if (t === 2) return (ids.providerIds || []).includes(id)
    return false
}

function highlightShiftsOfUser(id: number | string, type: number) {
    highlightSelectionKind.value = 'user'
    idToHighlight.value = id
    typeToHighlight.value = type


    shiftUsersToHighlight.value = null
    highlightedShiftId.value = null
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

    router.patch(
        route('user.update.open.crafts', {user: usePage().props.auth.user.id}),
        {opened_crafts: craftsToDisplay.value.filter(c => !closedCrafts.value.includes(c.id)).map(c => c.id)},
        {preserveState: true, preserveScroll: true},
    )
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
            const needsQuali = requiredQualificationIdsForShift(shift).length > 0
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
            })
            .finally(() => {
                savingShiftIds.value.delete(shift.id)
            })
    }
}

async function resolveQualificationFor(desiredShift: any) {
    const userQualis = userForMultiEdit.value?.shift_qualifications ?? []
    const requiredIds = new Set(requiredQualificationIdsForShift(desiredShift))
    if (requiredIds.size === 0) return null

    const shiftCraftId =
        desiredShift.craft_id ??
        desiredShift.craftId ??
        desiredShift.craft?.id ??
        null

    const isUniversal = isUniversallyApplicablePerson(userForMultiEdit.value)

    const available = userQualis.filter((uq: any) => {
        const uqId = Number(uq?.id)
        if (!requiredIds.has(uqId)) return false

        // 🔥 KEY FIX:
        // Universal -> craft-unabhängig matchen (pivot.craft_id egal)
        if (isUniversal) return true

        // Nicht-universal: wenn shiftCraftId unbekannt -> ok, sonst pivot prüfen
        if (!shiftCraftId) return true

        const uqCraftId = uq.pivot?.craft_id ?? null
        if (uqCraftId === null) return true
        return Number(uqCraftId) === Number(shiftCraftId)
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

function isUniversallyApplicablePerson(u: any): boolean {
    if (!u) return false

    // beide Naming-Varianten + nested craft abdecken
    return (
        u.craft_universally_applicable === true ||
        u.craft_universally_applicable === 1 ||
        u.craft_are_universally_applicable === true ||
        u.craft_are_universally_applicable === 1 ||
        u.craft?.universally_applicable === true ||
        u.craft?.universally_applicable === 1
    )
}

function requiredQualificationIdsForShift(shift: any): number[] {
    const req = Array.isArray(shift?.shifts_qualifications)
        ? shift.shifts_qualifications
        : Object.values(shift?.shifts_qualifications || {})

    // nur Qualis die wirklich Slots haben
    return req
        .filter((r: any) => (r?.value ?? 0) > 0)
        .map((r: any) => Number(r.shift_qualification_id))
        .filter((n: any) => Number.isFinite(n))
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

function clearHighlightSelection() {
    idToHighlight.value = null
    typeToHighlight.value = null
    highlightSelectionKind.value = null
    shiftUsersToHighlight.value = null
    hoverShiftUsersToHighlight.value = null
    highlightedShiftId.value = null
}


</script>


<style scoped>
.cell {
    overflow: auto;
}

.stickyHeader {
    position: sticky;
    align-self: flex-start;
    display: block;
    top: 0px;
}

.stickyYAxis {
    position: sticky;
    align-self: flex-start;
    left: 60px;
    z-index: 12;
}

.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    left: 0;
    z-index: 12;
}
</style>

