<template>
    <ShiftHeader>
        <div class="w-full bg-white">
            <ShiftPlanListViewFunctionBar
                ref="functionBarRef"
                :date-value="dateValue"
                :filter-options="filterOptions"
                :personal-filters="personalFilters"
                :user_filters="user_filters"
                :crafts="crafts"
                :filter-type="filterType"
                @openHistoryModal="showHistoryModal = true"
            >
                <template #moreButtons>
                    <SwitchIconTooltip
                        v-model="multiEditMode"
                        :tooltip-text="$t('Multi-Edit')"
                        size="md"
                        icon="IconPencil"
                        @change="onMultiEditToggle"
                    />
                </template>
            </ShiftPlanListViewFunctionBar>

            <!-- Multi-edit action bar -->
            <div v-if="multiEditMode" ref="multiEditBarRef" class="sticky z-40 bg-white border-b border-zinc-200 px-5 py-2 flex items-center gap-x-4" :style="{ top: functionBarHeight + 'px' }">
                <span class="text-sm text-gray-600">{{ selectedShiftIds.length }} {{ $t('selected') }}</span>
                <button
                    type="button"
                    :disabled="selectedShiftIds.length === 0 || duplicateInFlight"
                    @click="duplicateSelectedShifts"
                    :class="[(selectedShiftIds.length === 0 || duplicateInFlight) ? 'bg-gray-300 cursor-not-allowed' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 cursor-pointer', 'rounded-md px-4 py-1.5 text-sm font-semibold text-white shadow-sm']"
                >
                    {{ $t('Duplicate') }}
                </button>
                <button
                    type="button"
                    :disabled="selectedShiftIds.length === 0"
                    @click="showDeleteConfirm = true"
                    :class="[selectedShiftIds.length === 0 ? 'bg-gray-300 cursor-not-allowed' : 'bg-artwork-error hover:bg-artwork-error/90 cursor-pointer', 'rounded-md px-4 py-1.5 text-sm font-semibold text-white shadow-sm']"
                >
                    {{ $t('Delete') }}
                </button>
            </div>

            <div v-if="calendarWarningText" class="px-5 py-2 bg-yellow-50 text-yellow-800 text-sm">
                {{ calendarWarningText }}
            </div>

            <div class="pb-10">
                <template v-if="hasContent">
                    <div v-for="dayData in localGroupedShifts" :key="dayData.day" :ref="el => setDayRef(dayData.day, el)">
                        <!-- Day header — black sticky bar (always rendered) -->
                        <div
                            class="sticky z-30 bg-artwork-navigation-background text-white px-4 py-2.5 font-semibold text-base rounded-r-lg flex items-center gap-2"
                            :style="{ top: dayHeaderStickyTop + 'px' }"
                        >
                            {{ formatDayHeader(dayData.day) }}
                            <div v-if="dayData.holidays?.length" class="shrink-0">
                                <HolidayToolTip icon-color-class="text-white">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-full
                                           bg-white/10 hover:bg-white/15
                                           border border-white/10
                                           px-2 py-0.5 text-[10px] font-medium"
                                    >
                                        <PropertyIcon name="IconSparkles" class="h-3 w-3 opacity-90" />
                                        <span class="tabular-nums">{{ dayData.holidays.length }}</span>
                                    </button>

                                    <div class="space-y-1 divide-y divide-dashed divide-white/20">
                                        <div
                                            v-for="holiday in dayData.holidays"
                                            :key="holiday.date || holiday.name"
                                            class="pt-1 text-[11px]"
                                        >
                                            <div :style="{ color: holiday.color }" class="font-semibold">
                                                {{ holiday.name }}
                                            </div>
                                            <div class="text-white/80 text-[10px]" v-if="holiday.subdivisions?.length">
                                                {{ holiday.subdivisions.map((p) => p).join(', ') }}
                                            </div>
                                            <div class="text-white/70 text-[10px]" v-else>
                                                {{ $t('Germany-wide') }}
                                            </div>
                                        </div>
                                    </div>
                                </HolidayToolTip>
                            </div>
                        </div>

                        <!-- L2: Only render rooms/shifts when day is visible (or nearby via rootMargin) -->
                        <template v-if="visibleDays.has(dayData.day)">
                        <div v-for="roomData in dayData.rooms" :key="roomData.room_id" class="mb-1">
                            <!-- ============ Mode B/D: vertical room bar layout (show_appointments active) ============ -->
                            <template v-if="showAppointments">
                                <div class="flex border border-gray-200 rounded-r-lg overflow-hidden">
                                    <!-- Vertical room bar -->
                                    <div class="flex-shrink-0 w-32 bg-gray-50 px-3 py-2 flex items-start font-semibold text-xs uppercase tracking-wide text-gray-600 border-r border-gray-200">
                                        {{ roomData.room ? roomData.room.name : $t('No room') }}
                                    </div>

                                    <!-- Appointments column -->
                                    <div class="flex-1 min-w-0 px-2 py-1.5 border-r border-gray-200">
                                        <div
                                            v-for="event in roomData.events || []"
                                            :key="`evt-${event.id}`"
                                            class="border-b border-gray-100 py-1 px-1.5 border-l-4"
                                            :style="{ backgroundColor: hexToRgba(event.event_type?.hex_code || event.event_type?.color, 0.12), borderLeftColor: event.event_type?.hex_code || event.event_type?.color || '#d1d5db' }"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-xs" :style="{ color: event.event_type?.hex_code || event.event_type?.color }">
                                                    {{ event.event_type?.abbreviation }}
                                                </span>
                                                <span class="text-xs text-gray-700 truncate flex-1 min-w-0">
                                                    {{ event.eventName || event.name || event.event_type?.name }}
                                                </span>
                                                <span class="text-[11px] text-gray-500 tabular-nums shrink-0">
                                                    <template v-if="event.allDay">{{ $t('All day') }}</template>
                                                    <template v-else>{{ formatEventTime(event.start_time) }} – {{ formatEventTime(event.end_time) }}</template>
                                                </span>
                                                <Link
                                                    v-if="event.project"
                                                    :href="route('projects.tab', { project: event.project.id, projectTab: firstProjectShiftTabId })"
                                                    class="inline-flex items-center rounded-full border border-gray-300 bg-white px-2 py-0.5 text-[10px] font-medium text-black underline hover:bg-gray-50 transition shrink-0"
                                                >
                                                    {{ event.project.name }}
                                                </Link>
                                            </div>
                                            <!-- Terminbeschreibung (Anzeigeeinstellung "Notizen einblenden") -->
                                            <div v-if="listViewSettings.shift_notes && event.description" class="text-xs text-gray-400 mt-0.5 pl-0.5 truncate">
                                                {{ event.description }}
                                            </div>
                                        </div>
                                        <button
                                            v-if="(can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                            type="button"
                                            class="mt-1 inline-flex items-center gap-1 px-2 py-1 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded transition"
                                            @click="openAddEventForRoomAndDay(dayData.day, roomData.room?.id ?? null)"
                                        >
                                            <PropertyIcon name="IconCirclePlus" class="size-3" />
                                            {{ $t('Add appointment') }}
                                        </button>
                                    </div>

                                    <!-- Shifts column -->
                                    <div class="flex-[2] min-w-0 px-2 py-1.5">
                                        <template v-for="(section, sIdx) in renderShiftSections(roomData, dayData.day)" :key="sIdx">
                                            <!-- Shift group bar -->
                                            <div
                                                v-if="section.type === 'group_bar'"
                                                class="bg-zinc-100 border-l-4 border-zinc-300 px-2 py-1 mt-1 first:mt-0 text-xs font-semibold flex items-center justify-between rounded-r"
                                            >
                                                <span>{{ section.name }}</span>
                                                <ToolTipComponent
                                                    v-if="(can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                                    direction="left"
                                                    :tooltip-text="$t('Add Shift')"
                                                    icon="IconCirclePlus"
                                                    icon-size="size-3.5"
                                                    @click="openAddShiftForRoomAndDay(dayData.day, roomData.room?.id ?? null, section.shiftGroupId)"
                                                    classes-button="border border-zinc-200 inline-flex items-center justify-center cursor-pointer rounded-md size-5 text-sm font-medium bg-white hover:bg-gray-50 transition duration-200 ease-in-out"
                                                />
                                            </div>
                                            <!-- Shift row -->
                                            <template v-else-if="section.type === 'shift'">
                                                <div
                                                    v-if="!hideShiftRow"
                                                    class="flex items-center gap-3 border-b border-gray-100 py-1.5 px-2 border-l-4"
                                                    :style="{ backgroundColor: hexToRgba(section.shift.craft?.color, 0.12), borderLeftColor: section.shift.craft?.color || '#d1d5db' }"
                                                >
                                                    <div v-if="multiEditMode" class="shrink-0">
                                                        <input
                                                            type="checkbox"
                                                            :checked="selectedShiftIds.includes(section.shift.id)"
                                                            @change="toggleShiftSelection(section.shift.id)"
                                                            class="input-checklist"
                                                        />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-x-2 flex-wrap">
                                                            <div class="flex items-center gap-x-1.5 text-sm">
                                                                <ToolTipComponent v-if="section.shift.is_committed" icon="IconLock" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Committed')" direction="top" black-icon />
                                                        <ToolTipComponent v-if="section.shift.in_workflow && !section.shift.is_committed" icon="IconGitPullRequest" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Requested')" direction="top" black-icon />
                                                                <span v-if="getShiftGroup(section.shift) && listViewSettings.show_shift_group_tag" class="text-[10px] text-gray-400">({{ getShiftGroup(section.shift).name }})</span>
                                                                <span class="font-medium" :style="{ color: section.shift.craft?.color }">{{ section.shift.craft?.abbreviation }}</span>
                                                                <span>{{ section.shift.start }} - {{ section.shift.end }}</span>
                                                            </div>
                                                            <div class="flex items-center text-xs text-gray-500">
                                                                ({{ getUsedWorkerCount(section.shift) }}/{{ getMaxWorkerCount(section.shift) }})
                                                                <svg v-if="getUnavailableWorkers(section.shift).length"
                                                                     class="h-3.5 w-3.5 ml-1 shrink-0 text-amber-500"
                                                                     fill="currentColor" viewBox="0 0 20 20">
                                                                    <title>{{ getUnavailableTooltip(section.shift) }}</title>
                                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                                </svg>
                                                                <span v-else class="inline-block w-2 h-2 rounded-full ml-1"
                                                                      :class="{
                                                                        'bg-red-500': getUsedWorkerCount(section.shift) === 0 && getMaxWorkerCount(section.shift) !== 0,
                                                                        'bg-yellow-500': getUsedWorkerCount(section.shift) > 0 && getUsedWorkerCount(section.shift) < getMaxWorkerCount(section.shift),
                                                                        'bg-green-500': getUsedWorkerCount(section.shift) >= getMaxWorkerCount(section.shift)
                                                                      }">
                                                                </span>
                                                            </div>
                                                            <div class="flex flex-row flex-wrap gap-x-2 text-[11px] text-gray-400">
                                                                <div
                                                                    v-for="row in getQualificationRows(section.shift)"
                                                                    :key="row.shift_qualification_id"
                                                                    class="flex items-center"
                                                                >
                                                                    {{ row.workerCount }}/{{ row.maxWorkerCount }}
                                                                    <PropertyIcon
                                                                        stroke-width="1"
                                                                        class="text-black size-3 ml-0.5"
                                                                        :name="getShiftQualificationIcon(row.shift_qualification_id)"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div v-if="listViewSettings.shift_notes && section.shift.description" class="text-xs text-gray-400 mt-0.5 pl-0.5">
                                                            {{ section.shift.description }}
                                                        </div>
                                                    </div>
                                                    <div v-if="getProject(section.shift)" class="shrink-0">
                                                        <Link
                                                            :href="getProjectShiftTabUrl(section.shift)"
                                                            class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-black underline hover:bg-gray-50 transition"
                                                        >
                                                            {{ getProject(section.shift).name }}
                                                        </Link>
                                                    </div>
                                                    <div class="shrink-0">
                                                        <BaseMenu white-menu-background>
                                                            <BaseMenuItem icon="IconEdit" :title="$t('Edit shift')" white-menu-background @click="openEditShift(section.shift)" />
                                                            <BaseMenuItem icon="IconCalendarEvent" :title="$t('Show in shift plan')" white-menu-background @click="navigateToShiftPlan(section.shift, dayData.day)" />
                                                        </BaseMenu>
                                                    </div>
                                                </div>
                                                <div v-if="listViewSettings.detailed_shift_overview" :class="hideShiftRow ? 'mb-1' : 'ml-2 mb-1'">
                                                    <SingleShiftInDailyShiftView
                                                        :shift="normalizeShift(section.shift)"
                                                        :shift-qualifications="shiftQualifications"
                                                        :crafts="crafts"
                                                        :first_project_calendar_tab_id="firstProjectShiftTabId"
                                                        :has-collision="false"
                                                        :prepend-craft-abbreviation="hideShiftRow"
                                                        details-only
                                                    />
                                                </div>
                                            </template>
                                            <!-- Add shift button at bottom of shifts column (only when not grouped) -->
                                            <button
                                                v-else-if="section.type === 'add_button' && (can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                                type="button"
                                                class="mt-1 inline-flex items-center gap-1 px-2 py-1 text-xs text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded transition"
                                                @click="openAddShiftForRoomAndDay(dayData.day, roomData.room?.id ?? null)"
                                            >
                                                <PropertyIcon name="IconCirclePlus" class="size-3" />
                                                {{ $t('Add Shift') }}
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- ============ Mode C: only group_by_shift_groups active (horizontal layout, group bars) ============ -->
                            <template v-else-if="groupByShiftGroups">
                                <div class="flex items-center justify-between px-4 py-2.5 text-xs border-1 shadow-sm font-semibold text-gray-600 uppercase tracking-wide border-gray-200 bg-gray-50 rounded-r-lg">
                                    <span>{{ roomData.room ? roomData.room.name : $t('No room') }}</span>
                                </div>
                                <template v-for="(section, sIdx) in renderShiftSections(roomData, dayData.day)" :key="sIdx">
                                    <div
                                        v-if="section.type === 'group_bar'"
                                        class="bg-zinc-100 border-l-4 border-zinc-300 px-3 py-1 text-xs font-semibold flex items-center justify-between"
                                    >
                                        <span>{{ section.name }}</span>
                                        <ToolTipComponent
                                            v-if="(can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                            direction="left"
                                            :tooltip-text="$t('Add Shift')"
                                            icon="IconCirclePlus"
                                            icon-size="size-4"
                                            @click="openAddShiftForRoomAndDay(dayData.day, roomData.room?.id ?? null, section.shiftGroupId)"
                                            classes-button="border border-zinc-200 inline-flex items-center justify-center cursor-pointer rounded-md size-6 text-sm font-medium bg-white hover:bg-gray-50 transition duration-200 ease-in-out"
                                        />
                                    </div>
                                    <template v-else-if="section.type === 'shift'">
                                        <div
                                            v-if="!hideShiftRow"
                                            class="flex items-center gap-3 border-b border-gray-100 py-1.5 px-2 border-l-4"
                                            :style="{ backgroundColor: hexToRgba(section.shift.craft?.color, 0.12), borderLeftColor: section.shift.craft?.color || '#d1d5db' }"
                                        >
                                            <div v-if="multiEditMode" class="shrink-0">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedShiftIds.includes(section.shift.id)"
                                                    @change="toggleShiftSelection(section.shift.id)"
                                                    class="input-checklist"
                                                />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-x-2">
                                                    <div class="flex items-center gap-x-1.5 text-sm">
                                                        <ToolTipComponent v-if="section.shift.is_committed" icon="IconLock" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Committed')" direction="top" black-icon />
                                                        <ToolTipComponent v-if="section.shift.in_workflow && !section.shift.is_committed" icon="IconGitPullRequest" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Requested')" direction="top" black-icon />
                                                        <span v-if="getShiftGroup(section.shift) && listViewSettings.show_shift_group_tag" class="text-[10px] text-gray-400">({{ getShiftGroup(section.shift).name }})</span>
                                                        <span class="font-medium" :style="{ color: section.shift.craft?.color }">{{ section.shift.craft?.abbreviation }}</span>
                                                        <span>{{ section.shift.start }} - {{ section.shift.end }}</span>
                                                    </div>
                                                    <div class="flex items-center text-xs text-gray-500">
                                                        ({{ getUsedWorkerCount(section.shift) }}/{{ getMaxWorkerCount(section.shift) }})
                                                        <svg v-if="getUnavailableWorkers(section.shift).length"
                                                             class="h-3.5 w-3.5 ml-1 shrink-0 text-amber-500"
                                                             fill="currentColor" viewBox="0 0 20 20">
                                                            <title>{{ getUnavailableTooltip(section.shift) }}</title>
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span v-else class="inline-block w-2 h-2 rounded-full ml-1"
                                                              :class="{
                                                                'bg-red-500': getUsedWorkerCount(section.shift) === 0 && getMaxWorkerCount(section.shift) !== 0,
                                                                'bg-yellow-500': getUsedWorkerCount(section.shift) > 0 && getUsedWorkerCount(section.shift) < getMaxWorkerCount(section.shift),
                                                                'bg-green-500': getUsedWorkerCount(section.shift) >= getMaxWorkerCount(section.shift)
                                                              }">
                                                        </span>
                                                    </div>
                                                    <div class="flex flex-row flex-wrap gap-x-2 text-[11px] text-gray-400">
                                                        <div
                                                            v-for="row in getQualificationRows(section.shift)"
                                                            :key="row.shift_qualification_id"
                                                            class="flex items-center"
                                                        >
                                                            {{ row.workerCount }}/{{ row.maxWorkerCount }}
                                                            <PropertyIcon
                                                                stroke-width="1"
                                                                class="text-black size-3 ml-0.5"
                                                                :name="getShiftQualificationIcon(row.shift_qualification_id)"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="listViewSettings.shift_notes && section.shift.description" class="text-xs text-gray-400 mt-0.5 pl-0.5">
                                                    {{ section.shift.description }}
                                                </div>
                                            </div>
                                            <div v-if="getProject(section.shift)" class="shrink-0">
                                                <Link
                                                    :href="getProjectShiftTabUrl(section.shift)"
                                                    class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-black underline hover:bg-gray-50 transition"
                                                >
                                                    {{ getProject(section.shift).name }}
                                                </Link>
                                            </div>
                                            <div class="shrink-0">
                                                <BaseMenu white-menu-background>
                                                    <BaseMenuItem icon="IconEdit" :title="$t('Edit shift')" white-menu-background @click="openEditShift(section.shift)" />
                                                    <BaseMenuItem icon="IconCalendarEvent" :title="$t('Show in shift plan')" white-menu-background @click="navigateToShiftPlan(section.shift, dayData.day)" />
                                                </BaseMenu>
                                            </div>
                                        </div>
                                        <div v-if="listViewSettings.detailed_shift_overview" :class="hideShiftRow ? 'mb-1' : 'ml-2 mb-1'">
                                            <SingleShiftInDailyShiftView
                                                :shift="normalizeShift(section.shift)"
                                                :shift-qualifications="shiftQualifications"
                                                :crafts="crafts"
                                                :first_project_calendar_tab_id="firstProjectShiftTabId"
                                                :has-collision="false"
                                                :prepend-craft-abbreviation="hideShiftRow"
                                                details-only
                                            />
                                        </div>
                                    </template>
                                </template>
                            </template>

                            <!-- ============ Mode A: default layout (no toggles) ============ -->
                            <template v-else>
                                <div class="flex items-center justify-between px-4 py-2.5 text-xs border-1 shadow-sm font-semibold text-gray-600 uppercase tracking-wide border-gray-200 bg-gray-50 rounded-r-lg">
                                    <span>{{ roomData.room ? roomData.room.name : $t('No room') }}</span>
                                    <ToolTipComponent
                                        v-if="(can('can plan shifts') || hasAdminRole()) && !multiEditMode"
                                        direction="left"
                                        :tooltip-text="$t('Add Shift')"
                                        icon="IconCirclePlus"
                                        icon-size="size-4"
                                        @click="openAddShiftForRoomAndDay(dayData.day, roomData.room?.id ?? null)"
                                        classes-button="border border-zinc-200 inline-flex items-center justify-center cursor-pointer rounded-md size-6 text-sm font-medium bg-white hover:bg-gray-50 transition duration-200 ease-in-out mr-2"
                                    />
                                </div>
                                <template v-for="shift in roomData.shifts" :key="shift.id">
                                    <div v-if="!hideShiftRow"
                                         class="flex items-center gap-3 border-b border-gray-100 py-1.5 px-2 border-l-4"
                                         :style="{ backgroundColor: hexToRgba(shift.craft?.color, 0.12), borderLeftColor: shift.craft?.color || '#d1d5db' }">
                                        <div v-if="multiEditMode" class="shrink-0">
                                            <input
                                                type="checkbox"
                                                :checked="selectedShiftIds.includes(shift.id)"
                                                @change="toggleShiftSelection(shift.id)"
                                                class="input-checklist"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-x-2">
                                                <div class="flex items-center gap-x-1.5 text-sm">
                                                    <ToolTipComponent v-if="shift.is_committed" icon="IconLock" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Committed')" direction="top" black-icon />
                                                    <ToolTipComponent v-if="shift.in_workflow && !shift.is_committed" icon="IconGitPullRequest" icon-size="h-3 w-3 text-black" :stroke="2" :tooltip-text="$t('Requested')" direction="top" black-icon />
                                                    <span v-if="getShiftGroup(shift) && listViewSettings.show_shift_group_tag" class="text-[10px] text-gray-400">({{ getShiftGroup(shift).name }})</span>
                                                    <span class="font-medium" :style="{ color: shift.craft?.color }">{{ shift.craft?.abbreviation }}</span>
                                                    <span>{{ shift.start }} - {{ shift.end }}</span>
                                                </div>
                                                <div class="flex items-center text-xs text-gray-500">
                                                    ({{ getUsedWorkerCount(shift) }}/{{ getMaxWorkerCount(shift) }})
                                                    <svg v-if="getUnavailableWorkers(shift).length"
                                                         class="h-3.5 w-3.5 ml-1 shrink-0 text-amber-500"
                                                         fill="currentColor" viewBox="0 0 20 20">
                                                        <title>{{ getUnavailableTooltip(shift) }}</title>
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span v-else class="inline-block w-2 h-2 rounded-full ml-1"
                                                          :class="{
                                                            'bg-red-500': getUsedWorkerCount(shift) === 0 && getMaxWorkerCount(shift) !== 0,
                                                            'bg-yellow-500': getUsedWorkerCount(shift) > 0 && getUsedWorkerCount(shift) < getMaxWorkerCount(shift),
                                                            'bg-green-500': getUsedWorkerCount(shift) >= getMaxWorkerCount(shift)
                                                          }">
                                                    </span>
                                                </div>
                                                <div class="flex flex-row flex-wrap gap-x-2 text-[11px] text-gray-400">
                                                    <div
                                                        v-for="row in getQualificationRows(shift)"
                                                        :key="row.shift_qualification_id"
                                                        class="flex items-center"
                                                    >
                                                        {{ row.workerCount }}/{{ row.maxWorkerCount }}
                                                        <PropertyIcon
                                                            stroke-width="1"
                                                            class="text-black size-3 ml-0.5"
                                                            :name="getShiftQualificationIcon(row.shift_qualification_id)"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="listViewSettings.shift_notes && shift.description" class="text-xs text-gray-400 mt-0.5 pl-0.5">
                                                {{ shift.description }}
                                            </div>
                                        </div>
                                        <div v-if="getProject(shift)" class="shrink-0">
                                            <Link
                                                :href="getProjectShiftTabUrl(shift)"
                                                class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-0.5 text-xs font-medium text-black underline hover:bg-gray-50 transition"
                                            >
                                                {{ getProject(shift).name }}
                                            </Link>
                                        </div>
                                        <div class="shrink-0">
                                            <BaseMenu white-menu-background>
                                                <BaseMenuItem icon="IconEdit" :title="$t('Edit shift')" white-menu-background @click="openEditShift(shift)" />
                                                <BaseMenuItem icon="IconCalendarEvent" :title="$t('Show in shift plan')" white-menu-background @click="navigateToShiftPlan(shift, dayData.day)" />
                                            </BaseMenu>
                                        </div>
                                    </div>
                                    <div v-if="listViewSettings.detailed_shift_overview" :class="hideShiftRow ? 'mb-1' : 'ml-2 mb-1'">
                                        <SingleShiftInDailyShiftView
                                            :shift="normalizeShift(shift)"
                                            :shift-qualifications="shiftQualifications"
                                            :crafts="crafts"
                                            :first_project_calendar_tab_id="firstProjectShiftTabId"
                                            :has-collision="false"
                                            :prepend-craft-abbreviation="hideShiftRow"
                                            details-only
                                        />
                                    </div>
                                </template>
                            </template>
                        </div>
                        </template>
                        <div v-else style="min-height: 100px" />
                    </div>
                </template>

                <div v-else class="flex items-center justify-center py-20 text-gray-400 text-sm">
                    {{ $t('No shifts found for the selected time range.') }}
                </div>
            </div>
        </div>

        <!-- Add/Edit Shift Modal -->
        <AddShiftModal
            v-if="showAddShiftModal"
            :crafts="crafts"
            :event="null"
            :shift="shiftToEdit"
            :current-user-crafts="currentUserCrafts"
            :buffer="null"
            :shift-qualifications="shiftQualifications"
            :shift-groups="shiftGroups"
            :global-qualifications="globalQualifications"
            @closed="closeAddShiftModal"
            :shift-time-presets="shiftTimePresets"
            :rooms="rooms"
            :room="roomForShiftAdd"
            :day="dayForShiftAdd"
            :default-shift-group-id="shiftGroupForShiftAdd"
            :shift-plan-modal="true"
            :edit="shiftToEdit !== null"
        />

        <!-- Add Event Modal -->
        <EventComponent
            v-if="showAddEventModal"
            @closed="closeAddEventModal"
            :show-hints="$page.props?.can?.show_hints"
            :event-types="eventTypes"
            :rooms="rooms"
            :event="null"
            :wanted-room-id="roomForEventAdd"
            :wanted-date="dayForEventAdd"
            :is-admin="hasAdminRole()"
            :room-collisions="{}"
            :first_project_calendar_tab_id="firstProjectShiftTabId"
            :event-statuses="eventStatuses || []"
        />

        <!-- History Modal -->
        <ShiftHistoryModal
            v-if="showHistoryModal"
            :crafts="crafts"
            :initialStartDate="dateValue[0]"
            :initialEndDate="dateValue[1]"
            @close="showHistoryModal = false"
        />

        <!-- Delete confirmation -->
        <ConfirmDeleteModal
            v-if="showDeleteConfirm"
            @closed="showDeleteConfirm = false"
            @delete="deleteSelectedShifts"
            :title="$t('Delete shifts')"
            :description="$t('Are you sure you want to delete the selected shifts?')"
            :button="$t('Delete')"
        />
    </ShiftHeader>
</template>

<script setup>
import {ref, shallowRef, computed, watch, onMounted, onBeforeUnmount, onUnmounted, nextTick, defineAsyncComponent} from 'vue';
import {Link, router, usePage} from '@inertiajs/vue3';
import {useI18n} from 'vue-i18n';
import {usePermission} from '@/Composeables/Permission.js';
import axios from 'axios';
import ShiftHeader from '@/Pages/Shifts/ShiftHeader.vue';
import ShiftPlanListViewFunctionBar from '@/Layouts/Components/ShiftPlanComponents/ShiftPlanListViewFunctionBar.vue';
import BaseMenu from '@/Components/Menu/BaseMenu.vue';
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue';
import SwitchIconTooltip from '@/Artwork/Toggles/SwitchIconTooltip.vue';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';
import HolidayToolTip from '@/Components/ToolTips/HolidayToolTip.vue';

const AddShiftModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/AddShiftModal.vue'),
    delay: 200,
    timeout: 5000,
});

const EventComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/EventComponent.vue'),
    delay: 200,
    timeout: 5000,
});

const SingleShiftInDailyShiftView = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue'),
    delay: 200,
});

const ShiftHistoryModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftHistoryModal.vue'),
    delay: 200,
    timeout: 3000,
});

const {t: $t, locale} = useI18n();
const {can, hasAdminRole} = usePermission(usePage().props);

const props = defineProps({
    groupedShifts: Array,
    dateValue: Array,
    listViewSettings: Object,
    user_filters: Object,
    crafts: Array,
    eventTypes: Array,
    eventStatuses: Array,
    filterOptions: Object,
    personalFilters: Array,
    shiftQualifications: Array,
    firstProjectShiftTabId: [String, Number],
    filterType: String,
    calendarWarningText: String,
    rooms: [Array, Object],
    shiftTimePresets: Array,
    shiftGroups: [Array, Object],
    globalQualifications: [Array, Object],
    currentUserCrafts: Array,
});

// --- L3: Local reactive copy of groupedShifts + WebSocket listeners ---
const localGroupedShifts = shallowRef(props.groupedShifts || []);

// Sync when Inertia reloads (date change, filter changes)
watch(() => props.groupedShifts, (newVal) => {
    localGroupedShifts.value = newVal || [];
    shiftsVersion.value++;
});

// --- L4: Version counter for cache invalidation ---
const shiftsVersion = ref(0);

// --- L3b: WebSocket listeners ---
const activeChannels = new Set();

function setupListeners() {
    cleanupListeners();
    const roomIds = new Set();
    for (const dayData of localGroupedShifts.value) {
        for (const roomData of dayData.rooms || []) {
            if (roomData.room_id) roomIds.add(roomData.room_id);
        }
    }
    for (const roomId of roomIds) {
        const shiftChannel = `shift-plan.room.${roomId}`;
        const destroyChannel = `destroy.events.room.${roomId}`;
        activeChannels.add(shiftChannel);
        activeChannels.add(destroyChannel);
        window.Echo.private(shiftChannel)
            .listen('.shift-created', (data) => updateShiftLocally(data))
            .listen('.shift-assign-entity', (data) => updateShiftLocally(data))
            .listen('.shift-remove-entity', (data) => updateShiftLocally(data))
            .listen('.shift-updated', (data) => updateShiftLocally(data))
            .listen('.shift-updated.in.event', (data) => updateShiftLocally(data));
        window.Echo.private(destroyChannel)
            .listen('.shift-destroyed.in.event', (data) => removeShiftLocally(data));
    }
}

function cleanupListeners() {
    for (const ch of activeChannels) window.Echo.leave(ch);
    activeChannels.clear();
}

function updateShiftLocally(data) {
    if (!data?.shift) return;
    const shiftData = data.shift;
    const targetRoomId = data.roomId ?? shiftData.roomId;

    for (const dayData of localGroupedShifts.value) {
        for (const roomData of dayData.rooms || []) {
            const idx = (roomData.shifts || []).findIndex(s => s.id === shiftData.id);
            if (idx !== -1) {
                const existing = roomData.shifts[idx];
                existing.workers = shiftData.workers ?? existing.workers;
                existing.shifts_qualifications = shiftData.shifts_qualifications ?? existing.shifts_qualifications;
                existing.globalQualifications = shiftData.globalQualifications ?? existing.globalQualifications;
                existing.description = shiftData.description ?? existing.description;
                existing.start = shiftData.start ?? existing.start;
                existing.end = shiftData.end ?? existing.end;
                existing.is_committed = shiftData.isCommitted ?? shiftData.is_committed ?? existing.is_committed;
                existing.break_minutes = shiftData.break_minutes ?? existing.break_minutes;

                // Update craft data so craft changes are reflected in real-time
                if (shiftData.craftId != null) {
                    existing.craft_id = shiftData.craftId;
                    existing.craftId = shiftData.craftId;
                }
                if (shiftData.craft) {
                    existing.craft = shiftData.craft;
                } else if (shiftData.craftId != null && shiftData.craftId !== (existing.craft?.id ?? existing.craft_id)) {
                    // Craft changed but no craft object in broadcast — look up from props
                    const craftFromProps = props.crafts?.find(c => c.id === shiftData.craftId);
                    if (craftFromProps) {
                        existing.craft = {
                            id: craftFromProps.id,
                            name: craftFromProps.name,
                            abbreviation: craftFromProps.abbreviation,
                            color: craftFromProps.color,
                        };
                    }
                }

                if (shiftData.shiftGroupId !== undefined) {
                    existing.shift_group_id = shiftData.shiftGroupId;
                    existing.shiftGroupId = shiftData.shiftGroupId;
                }

                localGroupedShifts.value = [...localGroupedShifts.value];
                shiftsVersion.value++;
                return;
            }
        }
    }

    // Shift not found — it might be newly created, add it to the correct day/room
    if (shiftData.startDate && targetRoomId) {
        const shiftDay = shiftData.startDate;
        for (const dayData of localGroupedShifts.value) {
            if (dayData.day === shiftDay) {
                for (const roomData of dayData.rooms || []) {
                    if (roomData.room_id === targetRoomId) {
                        roomData.shifts = roomData.shifts || [];
                        roomData.shifts.push({
                            id: shiftData.id,
                            start: shiftData.start,
                            end: shiftData.end,
                            start_date: shiftData.startDate,
                            end_date: shiftData.endDate,
                            break_minutes: shiftData.break_minutes,
                            description: shiftData.description,
                            craft_id: shiftData.craftId,
                            room_id: targetRoomId,
                            event_id: shiftData.eventId,
                            is_committed: shiftData.isCommitted ?? false,
                            shift_group_id: shiftData.shiftGroupId,
                            craft: shiftData.craft,
                            shift_group: shiftData.shift_group,
                            project: shiftData.project,
                            event: shiftData.event,
                            workers: shiftData.workers || [],
                            shifts_qualifications: shiftData.shifts_qualifications || [],
                            globalQualifications: shiftData.globalQualifications || [],
                        });
                        localGroupedShifts.value = [...localGroupedShifts.value];
                        shiftsVersion.value++;
                        return;
                    }
                }
            }
        }
    }
}

function removeShiftLocally(data) {
    if (!data?.shift) return;
    const shiftId = data.shift.id;

    for (const dayData of localGroupedShifts.value) {
        for (const roomData of dayData.rooms || []) {
            const idx = (roomData.shifts || []).findIndex(s => s.id === shiftId);
            if (idx !== -1) {
                roomData.shifts.splice(idx, 1);
                localGroupedShifts.value = [...localGroupedShifts.value];
                shiftsVersion.value++;
                return;
            }
        }
    }
}

// --- L2: Lazy rendering via IntersectionObserver ---
const visibleDays = ref(new Set());
let dayObserver = null;

function initDayObserver() {
    dayObserver = new IntersectionObserver((entries) => {
        let changed = false;
        for (const entry of entries) {
            const key = entry.target.__dayKey;
            if (!key) continue;
            if (entry.isIntersecting) {
                if (!visibleDays.value.has(key)) { visibleDays.value.add(key); changed = true; }
            } else {
                if (visibleDays.value.has(key)) { visibleDays.value.delete(key); changed = true; }
            }
        }
        if (changed) visibleDays.value = new Set(visibleDays.value);
    }, { rootMargin: '400px 0px' });
}

const setDayRef = (dayKey, el) => {
    if (el && dayObserver) {
        el.__dayKey = dayKey;
        dayObserver.observe(el);
    }
};

// Sticky offset measurement
const functionBarRef = ref(null);
const multiEditBarRef = ref(null);
const functionBarHeight = ref(0);
let resizeObserver = null;

const dayHeaderStickyTop = computed(() => {
    let top = functionBarHeight.value;
    if (multiEditMode.value && multiEditBarRef.value) {
        top += multiEditBarRef.value.offsetHeight;
    }
    return top;
});

onMounted(() => {
    initDayObserver();
    setupListeners();
    nextTick(() => {
        const el = functionBarRef.value?.$el || functionBarRef.value;
        if (el) {
            functionBarHeight.value = el.offsetHeight;
            resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    functionBarHeight.value = entry.target.offsetHeight;
                }
            });
            resizeObserver.observe(el);
        }
    });
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});

onUnmounted(() => {
    cleanupListeners();
    dayObserver?.disconnect();
});

// Multi-edit state
const multiEditMode = ref(false);
const selectedShiftIds = ref([]);

// Shift add/edit state
const showAddShiftModal = ref(false);
const shiftToEdit = ref(null);
const roomForShiftAdd = ref(null);
const dayForShiftAdd = ref(null);
const shiftGroupForShiftAdd = ref(null);

// Event add state
const showAddEventModal = ref(false);
const roomForEventAdd = ref(null);
const dayForEventAdd = ref(null);

// History modal
const showHistoryModal = ref(false);

// Delete confirm
const showDeleteConfirm = ref(false);

// Display option toggles
const showAppointments = computed(() => !!props.listViewSettings?.show_appointments);
const groupByShiftGroups = computed(() => !!props.listViewSettings?.group_by_shift_groups);
const hideShiftRow = computed(() =>
    !!props.listViewSettings?.hide_shift_row && !!props.listViewSettings?.detailed_shift_overview
);

// Normalize shift data for downstream components
const normalizeShift = (shift) => shift;

const hasContent = computed(() => {
    return localGroupedShifts.value && localGroupedShifts.value.length > 0;
});

const formatDayHeader = (dateString) => {
    const date = new Date(dateString);
    const options = {weekday: 'long', year: 'numeric', month: '2-digit', day: '2-digit'};
    return date.toLocaleDateString(locale.value === 'de' ? 'de-DE' : 'en-US', options);
};

const formatEventTime = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '';
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    return `${hh}:${mm}`;
};

const getProject = (shift) => {
    return shift.project ?? shift.event?.project ?? null;
};

const getProjectShiftTabUrl = (shift) => {
    const project = getProject(shift);
    if (!project) return '#';
    return route('projects.tab', {project: project.id, projectTab: props.firstProjectShiftTabId});
};

// Craft color helper — same pattern as DailyRoomSplitTimeline
const hexToRgba = (hex, alpha = 0.12) => {
    if (!hex) return 'transparent';
    try {
        hex = hex.replace(/^#/, '');
        if (hex.length === 3) {
            hex = hex.split('').map(c => c + c).join('');
        }
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    } catch {
        return 'transparent';
    }
};

// Per-shift display data is pre-computed once and cached by shift.id, so
// templates don't recompute worker counts / qualification rows / colors on
// every render pass — important when many shifts are visible.
const shiftDisplayCache = computed(() => {
    shiftsVersion.value; // dependency tracking for reactive invalidation
    const cache = new Map();
    if (!localGroupedShifts.value) return cache;

    for (const dayData of localGroupedShifts.value) {
        for (const roomData of dayData.rooms || []) {
            for (const shift of roomData.shifts || []) {
                const workers = shift.workers || [];
                let used = workers.length;
                // Eingeplant, aber am Schichttag nicht verfügbar (z.B. krank) —
                // Schicht zählt dann nicht mehr als voll besetzt
                const unavailable = workers.filter((w) => w?.is_unavailable);

                let max = 0;
                const rows = [];
                shift.shifts_qualifications?.forEach((sq) => {
                    max += sq.value ?? 0;
                    if (!sq.value || sq.value === 0) return;
                    let assigned = workers.filter(
                        (w) => w.pivot?.shift_qualification_id === sq.shift_qualification_id
                    ).length;
                    rows.push({
                        shift_qualification_id: sq.shift_qualification_id,
                        maxWorkerCount: sq.value,
                        workerCount: assigned,
                    });
                });

                const craftColor = shift.craft?.color;
                cache.set(shift.id, {
                    used,
                    max,
                    rows,
                    unavailable,
                    bgColor: hexToRgba(craftColor, 0.12),
                    borderColor: craftColor || '#d1d5db',
                    project: shift.project ?? shift.event?.project ?? null,
                });
            }
        }
    }
    return cache;
});

const getShiftDisplay = (shift) => shiftDisplayCache.value.get(shift.id) || {
    used: 0, max: 0, rows: [], unavailable: [], bgColor: 'transparent', borderColor: '#d1d5db', project: null,
};

// Backwards-compatible thin helpers (used in places we don't want to touch).
const getMaxWorkerCount = (shift) => getShiftDisplay(shift).max;
const getUsedWorkerCount = (shift) => getShiftDisplay(shift).used;
const getQualificationRows = (shift) => getShiftDisplay(shift).rows;
const getUnavailableWorkers = (shift) => getShiftDisplay(shift).unavailable ?? [];
const getUnavailableTooltip = (shift) => {
    const names = getUnavailableWorkers(shift).map((w) => w.name).filter(Boolean);
    const label = $t('Assigned but not available');
    return names.length ? `${label}: ${names.join(', ')}` : label;
};

// Shift-qualification icon lookup is hot in the template, so we build a Map once.
const shiftQualificationIconMap = computed(() => {
    const m = new Map();
    (props.shiftQualifications || []).forEach((q) => m.set(q.id, q.icon ?? null));
    return m;
});

const getShiftQualificationIcon = (id) => shiftQualificationIconMap.value.get(id) ?? null;

// Sections per (day,room) are pre-computed once per groupedShifts change.
// Templates read from the Map instead of calling renderShiftSections() inline,
// which would re-run on every render tick.
const sectionsByRoomKey = computed(() => {
    shiftsVersion.value; // dependency tracking for reactive invalidation
    const map = new Map();
    if (!localGroupedShifts.value) return map;

    for (const dayData of localGroupedShifts.value) {
        for (const roomData of dayData.rooms || []) {
            const shifts = roomData.shifts || [];
            const sections = [];

            if (groupByShiftGroups.value) {
                const groups = groupShiftsByShiftGroup(shifts);
                groups.forEach((g) => {
                    sections.push({ type: 'group_bar', name: g.name, shiftGroupId: g.id });
                    g.shifts.forEach((shift) => sections.push({ type: 'shift', shift }));
                });
                if (groups.length === 0) {
                    sections.push({ type: 'add_button' });
                }
            } else {
                shifts.forEach((shift) => sections.push({ type: 'shift', shift }));
                sections.push({ type: 'add_button' });
            }

            map.set(`${dayData.day}|${roomData.room_id}`, sections);
        }
    }
    return map;
});

const renderShiftSections = (roomData, day) =>
    sectionsByRoomKey.value.get(`${day}|${roomData.room_id}`) || [];

// Resolve shift group across Laravel serialization forms (snake_cased relation `shift_group`,
// camelCased relation `shiftGroup`, or via column `shift_group_id`).
const getShiftGroup = (s) => s?.shift_group ?? s?.shiftGroup ?? null;
const getShiftGroupId = (s) => s?.shift_group_id ?? s?.shiftGroupId ?? getShiftGroup(s)?.id ?? null;

const groupShiftsByShiftGroup = (shifts) => {
    const map = new Map();
    shifts.forEach((s) => {
        const key = getShiftGroupId(s);
        if (!map.has(key)) {
            map.set(key, {
                id: key,
                name: getShiftGroup(s)?.name ?? $t('No shift group'),
                firstStart: s.start || '99:99',
                shifts: [],
            });
        }
        const g = map.get(key);
        g.shifts.push(s);
        if ((s.start || '99:99') < g.firstStart) g.firstStart = s.start;
    });
    return Array.from(map.values()).sort((a, b) => {
        if (a.id === null || a.id === undefined) return 1;
        if (b.id === null || b.id === undefined) return -1;
        return (a.firstStart || '').localeCompare(b.firstStart || '');
    });
};

// Navigation to shift plan (always weekly view) with highlight + scroll
const navigateToShiftPlan = async (shift, dayString) => {
    const shiftDate = new Date(dayString);
    const dayOfWeek = shiftDate.getDay();
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    const daysToSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek;

    const monday = new Date(shiftDate);
    monday.setDate(shiftDate.getDate() - daysToMonday);
    const sunday = new Date(shiftDate);
    sunday.setDate(shiftDate.getDate() + daysToSunday);

    const userId = usePage().props.auth.user.id;

    // Ensure weekly (non-daily) view is active
    await axios.patch(route('user.update.daily_view', userId), { daily_view: false, context: 'shift_plan' });

    router.patch(route('update.user.shift.calendar.filter.dates', userId), {
        start_date: monday.toISOString().slice(0, 10),
        end_date: sunday.toISOString().slice(0, 10),
        isDailyView: false,
    }, {
        preserveState: false,
        preserveScroll: false,
        onSuccess: () => {
            router.visit(route('shifts.plan') + '?highlightShift=' + shift.id);
        }
    });
};

// Shift edit
const openEditShift = (shift) => {
    shiftToEdit.value = shift;
    shiftGroupForShiftAdd.value = null;
    showAddShiftModal.value = true;
};

// Add shift for room/day (optionally with shift group)
const openAddShiftForRoomAndDay = (day, roomId, shiftGroupId = null) => {
    shiftToEdit.value = null;
    roomForShiftAdd.value = roomId;
    dayForShiftAdd.value = day;
    shiftGroupForShiftAdd.value = shiftGroupId;
    showAddShiftModal.value = true;
};

const closeAddShiftModal = () => {
    showAddShiftModal.value = false;
    shiftToEdit.value = null;
    roomForShiftAdd.value = null;
    dayForShiftAdd.value = null;
    shiftGroupForShiftAdd.value = null;
    router.reload({ only: ['groupedShifts'], preserveScroll: true });
};

// Add event for room/day
const openAddEventForRoomAndDay = (day, roomId) => {
    roomForEventAdd.value = roomId;
    dayForEventAdd.value = day;
    showAddEventModal.value = true;
};

const closeAddEventModal = () => {
    showAddEventModal.value = false;
    roomForEventAdd.value = null;
    dayForEventAdd.value = null;
    router.reload({ only: ['groupedShifts'], preserveScroll: true });
};

// Multi-edit
const onMultiEditToggle = () => {
    if (!multiEditMode.value) {
        selectedShiftIds.value = [];
    }
};

const toggleShiftSelection = (shiftId) => {
    const idx = selectedShiftIds.value.indexOf(shiftId);
    if (idx >= 0) {
        selectedShiftIds.value.splice(idx, 1);
    } else {
        selectedShiftIds.value.push(shiftId);
    }
};

const deleteSelectedShifts = () => {
    axios.post(route('shifts.multi.delete'), {
        shift_ids: selectedShiftIds.value,
    }).then(() => {
        selectedShiftIds.value = [];
        showDeleteConfirm.value = false;
        router.reload({ only: ['groupedShifts'], preserveScroll: true });
    });
};

// In-Flight-Guard: Doppelklick auf "Duplizieren" erzeugte jede Schicht zweimal
const duplicateInFlight = ref(false);
const duplicateSelectedShifts = () => {
    if (duplicateInFlight.value) {
        return;
    }
    duplicateInFlight.value = true;
    axios.post(route('shifts.multi.duplicate'), {
        shift_ids: selectedShiftIds.value,
    }).then(() => {
        selectedShiftIds.value = [];
        router.reload({ only: ['groupedShifts'], preserveScroll: true });
    }).finally(() => {
        duplicateInFlight.value = false;
    });
};
</script>
