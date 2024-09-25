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
                <div ref="shiftPlan" id="shiftPlan" class="bg-white flex-grow"
                     :class="[isFullscreen ? 'overflow-y-auto' : '', showUserOverview ? ' max-h-[var(--dynamic-height)] overflow-y-scroll' : '',' max-h-[var(--dynamic-height)] overflow-y-scroll overflow-x-scroll']">
                    <Table>
                        <template #head>
                            <div class="stickyHeader">
                                <TableHead id="stickyTableHead" ref="stickyTableHead">
                                    <th class="z-0" style="width:192px;"></th>
                                    <th  v-for="day in days" :id="day.is_extra_row ? 'extra_row_' + day.week_number : day.full_day" style="max-width: 204px"
                                        class="z-20 h-8 py-2 border-r-2 border-secondaryHover truncate">
                                        <div v-if="day.is_extra_row" style="width:37px">
                                            <span class="text-[9px] font-bold">KW{{day.week_number }}</span>
                                        </div>
                                        <div v-else :style="{width:  '200px'}" class="flex items-center calendarRoomHeaderBold ml-2">
                                            {{ day.day_string }} {{ day.full_day }}
                                        </div>
                                    </th>

                                </TableHead>
                            </div>
                        </template>
                        <template #body>
                            <TableBody class="eventByDaysContainer">
                                <tr v-for="(room,index) in computedShiftPlan" class="w-full flex">
                                    <th :id="'roomNameContainer_' + index" class="xsDark flex items-center h-28 w-48" :class="[index % 2 === 0 ? 'bg-backgroundGray' : 'bg-secondaryHover', isFullscreen || this.showUserOverview ? 'stickyYAxisNoMarginLeft' : 'stickyYAxisNoMarginLeft']">
                                        <div class="flex font-semibold items-center ml-4">
                                            {{ renderRoomName(room) }}
                                            <!--{{ room[days[0].full_day].roomName }}-->
                                        </div>
                                    </th>
                                    <td :class="[day.is_weekend ? 'bg-backgroundGray' : 'bg-white', day.is_sunday ? '' : 'border-dashed' ]"
                                        class="border-r-2 border-gray-400  day-container h-28"
                                        v-for="day in days" :data-day="day.full_day">
                                        <div class="bg-backgroundGray2 max-h-28 h-28" style="width: 37px" v-if="day.is_extra_row">

                                        </div>
                                        <!-- Build in v-if="this.currentDaysInView.has(day.full_day)" when observer fixed -->
                                        <div v-else style="width: 200px" class="max-h-28 h-28 overflow-y-auto cell ">
                                            <div v-for="event in room[day.full_day].events" class="mb-1">
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
                                                />
                                                <SingleEventInShiftPlan v-else
                                                                        :event="event"
                                                                        :day="day"
                                                                        :firstProjectShiftTabId="firstProjectShiftTabId"/>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            </TableBody>
                        </template>
                    </Table>
                </div>
            </div>
            <div id="userOverview" class="w-full fixed bottom-0 z-30">
                <div class="flex justify-center overflow-y-scroll pointer-events-none">
                    <div v-if="this.$can('can plan shifts') || this.$can('can view shift plan') || this.hasAdminRole()" @click="showCloseUserOverview"
                         :class="showUserOverview ? 'rounded-tl-lg' : 'fixed bottom-0 rounded-t-lg'"
                         class="flex h-5 w-8 justify-center items-center cursor-pointer bg-artwork-navigation-background pointer-events-auto">
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
                         class="relative w-[97%] bg-artwork-navigation-background overflow-x-scroll z-30 overflow-y-scroll"
                         :style="showUserOverview ? { height: userOverviewHeight + 'px'} : {height: 20 + 'px'}">
                        <div
                            class="flex items-center justify-between w-full fixed py-3 z-50 bg-artwork-navigation-background px-3"
                            :style="{top: calculateTopPositionOfUserOverView}">
                            <div class="flex items-center justify-end gap-x-3">
                                <Switch @click="toggleMultiEditMode" v-model="multiEditMode"
                                        :class="[multiEditMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        :class="[multiEditMode ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span
                                          :class="[multiEditMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                         <IconPencil stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                      <span
                                          :class="[multiEditMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <IconPencil stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                </span>
                                </Switch>
                                <div class="flex items-center gap-x-2" v-if="dayServices && selectedDayService">
                                    <Switch @click="toggleDayServiceMode" v-model="dayServiceMode"
                                            :class="[dayServiceMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                        <span class="sr-only">Use setting</span>
                                        <span
                                            :class="[dayServiceMode ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                        <span
                                            :class="[dayServiceMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                            aria-hidden="true">
                                            <component :is="selectedDayService?.icon" class="w-4 h-4"
                                                       :style="{color: selectedDayService?.hex_color}"/>
                                        </span>
                                        <span
                                            :class="[dayServiceMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                            aria-hidden="true">
                                            <component :is="selectedDayService?.icon" class="w-4 h-4"
                                                       :style="{color: selectedDayService?.hex_color}"/>
                                        </span>
                                    </span>
                                    </Switch>
                                    <DayServiceFilter :current-selected-day-service="selectedDayService"
                                                      :day-services="dayServices"
                                                      @update:current-selected-day-service="updateSelectedDayService"/>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-x-3 pr-20">
                                <Switch @click="toggleHighlightMode" v-model="highlightMode"
                                        :class="[highlightMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        :class="[highlightMode ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span
                                          :class="[highlightMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                         <IconBulb stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                      <span
                                          :class="[highlightMode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <IconBulb stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                </span>
                                </Switch>
                                <Switch @click="toggleCompactMode" v-model="$page.props.user.compact_mode"
                                        :class="[$page.props.user.compact_mode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                                    <span class="sr-only">Use setting</span>
                                    <span
                                        :class="[$page.props.user.compact_mode ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                      <span
                                          :class="[$page.props.user.compact_mode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                         <IconList stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                      <span
                                          :class="[$page.props.user.compact_mode ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                                          aria-hidden="true">
                                          <IconList stroke-width="1.5" class="w-4 h-4"/>
                                      </span>
                                </span>
                                </Switch>
                                <BaseFilter :whiteIcon="true" onlyIcon="true">
                                    <div class="mx-auto w-full max-w-md rounded-2xl border-none mt-2">
                                        <CraftFilter :crafts="crafts" is_tiny/>
                                    </div>
                                </BaseFilter>
                                <BaseMenu :white-icon="true" show-sort-icon dots-size="h-7 w-7" menu-width="w-80">
                                    <div class="flex items-center justify-end py-1">
                                    <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="this.resetSort()">
                                        {{ $t('Reset') }}
                                    </span>
                                    </div>
                                    <MenuItem v-for="shiftPlanWorkerSortEnumName in shiftPlanWorkerSortEnums"
                                              v-slot="{ active }">
                                        <div @click="this.applySort(shiftPlanWorkerSortEnumName)"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                            {{ getSortEnumTranslation(shiftPlanWorkerSortEnumName) }}
                                            <IconCheck v-if="this.$page.props.user.shift_plan_user_sort_by === shiftPlanWorkerSortEnumName" class="w-5 h-5"/>
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </div>
                        <div class="pt-14">
                            <table class="w-full text-white overflow-y-scroll">
                                <!-- Outer Div is needed for Safari to apply Stickyness to Header -->
                                <div>
                                    <tbody class="w-full pt-3" v-for="craft in reComputedCraftsToDisplay">
                                    <tr class="stickyYAxisNoMarginLeft pl-2 cursor-pointer w-48 xsLight flex justify-between pb-1"
                                        @click="changeCraftVisibility(craft.id)">
                                        {{ craft.name }}
                                        <ChevronDownIcon
                                            :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                            class="h-4 w-4 mt-0.5"
                                        />
                                    </tr>
                                    <tr v-if="!closedCrafts.includes(craft.id)" v-for="(user,index) in craft.users"
                                        class="w-full flex">
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
                                                               :craft-id="craft.id"
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
                                            <div v-if="!day.is_extra_row"
                                                :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']"
                                                class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer truncate relative overflow-hidden"
                                                :style="{width: '202px'}"
                                                @click="handleCellClick(user, day)">
                                                <span v-for="shift in user.element?.shifts"
                                                      v-if="!user.vacations?.includes(day.without_format)">
                                                    <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                        {{ shift.start }} - {{ shift.end }} {{ shift.roomName }},
                                                    </span>
                                                </span>
                                                <span v-else
                                                      class="h-full flex justify-center items-center text-artwork-messages-error">
                                                    {{ $t('not available') }}
                                                </span>
                                                <span v-if="user.availabilities">
                                                    <span v-for="availability in user.availabilities[day.full_day]">
                                                        <span class="text-green-500">
                                                            <span v-if="availability.comment">&bdquo;{{
                                                                    availability.comment
                                                                }}&rdquo; </span>
                                                        </span>
                                                    </span>
                                                </span>
                                            </div>
                                            <div v-else
                                                 class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden"
                                                 style="width: 39px"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']">
                                                <span v-if="user.type === 0">
                                                    {{ user?.weeklyWorkingHours[day.week_number]?.toFixed(2) }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.without_format"
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
                                        v-for="(user,index) in usersWithNoCrafts" class="w-full flex">
                                        <th class="stickyYAxisNoMarginLeft bg-artwork-navigation-background flex items-center text-right"
                                            :class="[multiEditMode ? '' : 'w-48', index % 2 === 0 ? '' : '']">
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
                                                               :craft-id="0"
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
                                            <div v-if="!day.is_extra_row"
                                                :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']"
                                                class="p-2 bg-gray-50/10 text-white text-xs rounded-lg shiftCell cursor-pointer"
                                                @click="handleCellClick(user, day)"
                                                :style="{width: '202px'}"
                                            >
                                            <span v-for="shift in user.element?.shifts"
                                                  v-if="!user.vacations?.includes(day.without_format)">
                                                <span v-if="shift.days_of_shift?.includes(day.full_day)">
                                                    {{ shift.start }} - {{ shift.end }} {{ shift.event.room?.name }},
                                                </span>
                                            </span>
                                                <span v-else
                                                      class="h-full flex justify-center items-center text-artwork-messages-error">
                                                {{ $t('not available') }}
                                            </span>
                                                <span v-if="user.availabilities">
                                                <span v-for="availability in user.availabilities[day.full_day]">
                                                    <span class="text-green-500">
                                                        <span v-if="availability.comment">&bdquo;{{
                                                                availability.comment
                                                            }}&rdquo; </span>
                                                    </span>
                                                </span>
                                                </span>
                                            </div>
                                            <div v-else
                                                 class="p-2 bg-gray-50/10 flex items-center justify-center text-white text-[8.25px] rounded-lg shiftCell cursor-default overflow-hidden"
                                                 style="width: 39px"
                                                 :class="[highlightMode ? idToHighlight ? idToHighlight === user.element.id && user.type === this.typeToHighlight ? '' : 'opacity-30' : 'opacity-30' : '', $page.props.user.compact_mode ? 'h-8' : 'h-12']">
                                                <span v-if="user.type === 0">
                                                    {{ user?.weeklyWorkingHours[day.week_number]?.toFixed(2) }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="user.dayServices"
                                                v-for="(userDayServices, index) in user.dayServices"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 flex">
                                                <div v-if="index === day.without_format"
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
            <show-user-shifts-modal v-if="showUserShifts"
                                    @closed="showUserShifts = false"
                                    :user="userToShow"
                                    :day="dayToShow"
                                    @desires-reload="userShiftModalDesiresReload"/>
            <ShiftHistoryModal v-if="showHistoryModal"
                               :history="history"
                               @closed="showHistoryModal = false"/>
        </ShiftHeader>
        <div class="fixed bottom-1 w-full z-40" v-if="multiEditMode">
            <div class="flex items-center justify-center gap-4">
                <div>
                    <button type="button"
                            @click="resetMultiEditMode()"
                            class="rounded-full bg-gray-100 px-14 py-3 text-sm font-semibold text-gray-500 shadow-sm hover:bg-gray-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
                        {{ $t('Cancel') }}
                    </button>
                </div>
                <div>
                    <button type="button"
                            @click="initializeMultiEditSave"
                            :disabled="this.userForMultiEdit === null"
                            :class="[
                                this.userForMultiEdit === null ?
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
import {Link, router} from "@inertiajs/vue3";
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
import {IconChevronDown, IconFileText, IconPencil, IconX} from "@tabler/icons-vue";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";
import SingleEventInShiftPlan from "@/Pages/Shifts/Components/SingleEventInShiftPlan.vue";
import IconLib from "@/Mixins/IconLib.vue";
import DayServiceFilter from "@/Components/Filter/DayServiceFilter.vue";
import {useEvent} from "@/Composeables/Event.js";
import {ref} from "vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {useSortEnumTranslation} from "@/Composeables/SortEnumTranslation.js";
import dayjs from "dayjs";

const {getSortEnumTranslation} = useSortEnumTranslation();

const {getDaysOfEvent, formatEventDateByDayJs, useShiftPlanReload} = useEvent(),
    {
        hasReceivedNewShiftPlanData,
        hasReceivedNewShiftPlanWorkerData,
        receivedRoomData,
        receivedWorkerData,
        handleReload
    } = useShiftPlanReload();

export default {
    name: "ShiftPlan",
    mixins: [Permissions, IconLib],
    components: {
        MenuItem,
        BaseMenu,
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
        'history',
        'usersForShifts',
        'freelancersForShifts',
        'serviceProvidersForShifts',
        'user_filters',
        'crafts',
        'shiftQualifications',
        'dayServices',
        'firstProjectShiftTabId',
        'shiftPlanWorkerSortEnums'
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
            userOverviewHeight: 570,
            startY: 0,
            startHeight: 0,
            windowHeight: window.innerHeight,
            shiftsToHandleOnMultiEdit: {
                assignToShift: [],
                removeFromShift: []
            },
            showShiftsQualificationsAssignmentModal: false,
            showShiftsQualificationsAssignmentModalShifts: [],
            // firstDayPosition without the extra row
            firstDayPosition: this.days ? this.days[this.days.findIndex((day) => !day.is_extra_row)] : null,
            currentDayOnView:  this.days ? this.days[this.days.findIndex((day) => !day.is_extra_row)] : null,
            currentDaysInView: new Set(),
            shiftPlanRef: ref(JSON.parse(JSON.stringify(this.shiftPlan))),
            screenHeight: screen.height
        }
    },
    mounted() {
        // Listen for scroll events on both sections
        this.$refs.shiftPlan.addEventListener('scroll', this.syncScrollShiftPlan);
        this.$refs.userOverview.addEventListener('scroll', this.syncScrollUserOverview);
        window.addEventListener('resize', this.updateHeight);
        this.updateHeight();

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
    computed: {
        reComputedCraftsToDisplay() {
            if (!hasReceivedNewShiftPlanWorkerData.value) {
                return this.craftsToDisplay;
            }

            receivedWorkerData.value.forEach((workerData) => {
                if (workerData.type === 'user') {
                    this.usersForShifts[this.usersForShifts.findIndex(
                        (userWithPlannedWorkingHours) =>
                            userWithPlannedWorkingHours.user.id === workerData.user.id
                    )] = workerData;

                    if (this.userForMultiEdit && this.userForMultiEdit.id === workerData.user.id) {
                        this.addUserToMultiEdit(
                            {
                                id: workerData.user.id,
                                type: this.userForMultiEdit.type,
                                craftId: this.userForMultiEdit.craftId,
                                display_name: workerData.user.first_name + ' ' + workerData.user.last_name,
                                profile_photo_url: workerData.user.profile_photo_url,
                                assigned_craft_ids: workerData.user.assigned_craft_ids,
                                shift_ids: workerData.user.shift_ids,
                                shift_qualifications: workerData.user.shift_qualifications
                            }
                        );
                    }
                }

                if (workerData.type === 'freelancer') {
                    this.freelancersForShifts[this.freelancersForShifts.findIndex(
                        (freelancerWithPlannedWorkingHours) =>
                            freelancerWithPlannedWorkingHours.freelancer.id === workerData.freelancer.id
                    )] = workerData;

                    if (this.userForMultiEdit && this.userForMultiEdit.id === workerData.freelancer.id) {
                        this.addUserToMultiEdit(
                            {
                                id: workerData.freelancer.id,
                                type: this.userForMultiEdit.type,
                                craftId: this.userForMultiEdit.craftId,
                                display_name: workerData.freelancer.first_name + ' ' + workerData.freelancer.last_name,
                                profile_photo_url: workerData.freelancer.profile_photo_url,
                                assigned_craft_ids: workerData.freelancer.assigned_craft_ids,
                                shift_ids: workerData.freelancer.shift_ids,
                                shift_qualifications: workerData.freelancer.shift_qualifications
                            }
                        );
                    }
                }

                if (workerData.type === 'service_provider') {
                    this.serviceProvidersForShifts[this.serviceProvidersForShifts.findIndex(
                        (serviceProviderWithPlannedWorkingHours) =>
                            serviceProviderWithPlannedWorkingHours.service_provider.id === workerData.service_provider.id
                    )] = workerData;

                    if (this.userForMultiEdit && this.userForMultiEdit.id === workerData.service_provider.id) {
                        this.addUserToMultiEdit(
                            {
                                id: workerData.service_provider.id,
                                type: this.userForMultiEdit.type,
                                craftId: this.userForMultiEdit.craftId,
                                display_name: workerData.service_provider.provider_name,
                                profile_photo_url: workerData.service_provider.profile_photo_url,
                                assigned_craft_ids: workerData.service_provider.assigned_craft_ids,
                                shift_ids: workerData.service_provider.shift_ids,
                                shift_qualifications: workerData.service_provider.shift_qualifications
                            }
                        );
                    }
                }
            });

            hasReceivedNewShiftPlanWorkerData.value = false;

            return this.craftsToDisplay;
        },
        computedShiftPlan() {
            if (!hasReceivedNewShiftPlanData.value) {
                return this.shiftPlanRef;
            }

            for (const [day, rooms] of Object.entries(receivedRoomData.value)) {
                for (const [roomId, events] of Object.entries(rooms)) {
                    this.shiftPlanRef.forEach(
                        (roomWithEvents) => {
                            if (roomWithEvents[day]?.roomId === Number(roomId)) {
                                roomWithEvents[day].events = JSON.parse(JSON.stringify(events));
                            }
                        }
                    );
                }
            }

            hasReceivedNewShiftPlanData.value = false;

            return this.shiftPlanRef;
        },
        craftsToDisplay() {
            const users = this.getDropUsers(),
                crafts = this.crafts
                    .map(
                        (craft) => {
                            return {
                                id: craft.id,
                                name: craft.name,
                                users: users.filter(user => user.assigned_craft_ids.includes(craft.id)),
                                color: craft?.color
                            };
                        }
                    );

                crafts.forEach((craft) => {
                    crafts.users = this.sortCraftUsers(craft.users);
                });

            if (this.$page.props.user.show_crafts?.length === 0 || this.$page.props.user.show_crafts === null) {
                return crafts;
            } else {
                return crafts.filter((craft) => this.$page.props.user.show_crafts.includes(craft.id));
            }
        },
        usersWithNoCrafts() {
            let usersWithNoCrafts = this.getDropUsers().filter(user =>
                !user.assigned_craft_ids || user.assigned_craft_ids?.length === 0
            );

            usersWithNoCrafts = this.sortCraftUsers(usersWithNoCrafts);

            return usersWithNoCrafts;
        },
    },
    methods: {
        renderRoomName(room){
            const firstDayWhereAreNotExtraRows = this.days.find(day => !day.is_extra_row);
            const firstDayIndex = this.days.indexOf(firstDayWhereAreNotExtraRows);
            const firstDay = this.days[firstDayIndex].full_day;

            return room[firstDay].roomName;
        },
        getSortEnumTranslation,
        userShiftModalDesiresReload(shiftId, userId, userType, desiredDay) {
            let desiredRoomIds = new Set();

            if (shiftId) {
                //find shift room ids
                this.shiftPlanRef.forEach(room => {
                    this.days.forEach(day => {
                        if (day.full_day !== desiredDay) {
                            return;
                        }
                        room[day.full_day].events.forEach(event => {
                            event.shifts.forEach(shift => {
                                if (shift.id === shiftId) {
                                    desiredRoomIds.add(room[day.full_day].roomId);
                                }
                            });
                        });
                    });
                });

                handleReload(
                    Array.from(desiredRoomIds),
                    [desiredDay],
                    [
                        {
                            id: userId,
                            type: userType
                        }
                    ]
                );

                return;
            }

            desiredRoomIds = this.rooms.map((room) => room.id);

            handleReload(
                Array.from(desiredRoomIds),
                [desiredDay],
                [
                    {
                        id: userId,
                        type: userType
                    }
                ]
            );
        },
        eventDesiresReload(userId, userType, event, seriesShiftData) {
            let desiredDates = seriesShiftData && seriesShiftData.onlyThisDay === false ?
                getDaysOfEvent(
                    formatEventDateByDayJs(seriesShiftData.start),
                    formatEventDateByDayJs(seriesShiftData.end)
                ) :
                getDaysOfEvent(
                    formatEventDateByDayJs(event.start),
                    formatEventDateByDayJs(event.end)
                );

            handleReload(
                [event.roomId],
                desiredDates,
                [
                    {
                        id: userId,
                        type: userType,
                    }
                ]
            );
        },
        getDropUsers() {
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
                });
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
                });
            })
            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    plannedWorkingHours: service_provider.plannedWorkingHours,
                    assigned_craft_ids: service_provider.service_provider.assigned_craft_ids,
                    dayServices: service_provider.dayServices
                });
            })
            return users;
        },
        handleCellClick(user, day) {
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
                const hasDayService = user.dayServices?.[day.without_format]?.some(dayService => dayService.id === this.selectedDayService.id)
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
                            date: day.without_format,
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
                            date: day.without_format,
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
            if (this.$page.props.user?.show_crafts?.length === 0 || this.$page.props.user?.show_crafts === null || this.$page.props.user?.show_crafts === undefined) {
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

                // Get the scrollable container and current scroll position
                const scrollableContainer = this.$refs.shiftPlan;
                const scrollPosition = scrollableContainer.scrollLeft;

                // Find the fixed position of the room name relative to the container
                const roomNameFixedPosition = scrollableContainer.getBoundingClientRect().left + 200; // Adjust this offset to match the actual position of the room name

                // Iterate over all days to find the day closest to the room name position
                let closestDayIndex = null;
                let closestDayDistance = Infinity;

                for (let i = 0; i < this.days.length; i++) {
                    const day = this.days[i];

                    // Find the element representing the current day
                    const dayElement = document.getElementById(day.full_day || `extra_row_${day.week_number}`);
                    if (!dayElement) continue; // Skip if the element is not found

                    // Get the left position of the element relative to the viewport
                    const elementLeft = dayElement.getBoundingClientRect().left;
                    const elementRight = elementLeft + dayElement.offsetWidth;
                    const elementCenter = elementLeft + (dayElement.offsetWidth / 2);

                    // Check if the element is visible and near the room name position
                    const distanceToRoomName = Math.abs(roomNameFixedPosition - elementCenter);

                    // Update if this element is closer to the room name position
                    if (distanceToRoomName < closestDayDistance) {
                        closestDayDistance = distanceToRoomName;
                        closestDayIndex = i;
                    }
                }

                // Check if we found a day close to the room name position
                if (closestDayIndex !== null) {
                    const selectedDay = this.days[closestDayIndex];

                    // Check if the selected day is an extra row
                    if (selectedDay.is_extra_row) {
                        // Find the next Monday after the extra row
                        for (let j = closestDayIndex + 1; j < this.days.length; j++) {
                            if (this.days[j].is_monday) {
                                this.currentDayOnView = this.days[j];
                                return; // Exit after finding and setting the correct Monday
                            }
                        }
                    } else {
                        // Set the currentDayOnView to the day closest to the room name
                        this.currentDayOnView = selectedDay;
                    }
                }
            }
        },
        selectGoToMode(direction) {
            const gotoMode = this.$page.props.user.goto_mode;
            this.scrollToPeriod(gotoMode, direction);
        },
        scrollToPeriod(period, direction) {
            let indexModifier = direction === 'next' ? 1 : -1;
            let periodKey, periodValue, scrollOffset;

            if (period === 'day') {
                // Get the current index of the currentDayOnView
                let currentIndex = this.days.indexOf(this.currentDayOnView);
                let targetIndex = currentIndex + indexModifier;

                // Ensure the targetIndex is within bounds
                while (targetIndex >= 0 && targetIndex < this.days.length) {
                    const targetDay = this.days[targetIndex];

                    // Skip extra rows and find the next/previous valid day
                    if (!targetDay.is_extra_row) {
                        scrollOffset = targetIndex;
                        break;
                    }
                    targetIndex += indexModifier;
                }

            } else if (period === 'week') {
                periodKey = 'week_number';
                periodValue = this.currentDayOnView.week_number;
                scrollOffset = this.getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, day => day.is_monday);
            } else if (period === 'month') {
                periodKey = 'month_number';
                periodValue = this.currentDayOnView.month_number;
                scrollOffset = this.getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, day => day.is_first_day_of_month);
            }

            // Scroll to the target element if a valid scrollOffset was determined
            if (scrollOffset !== undefined && scrollOffset !== null) {
                const targetDay = this.days[scrollOffset];

                // Set the currentDayOnView to the target day
                this.currentDayOnView = targetDay;

                // Find the corresponding DOM element
                const targetElement = document.getElementById(targetDay.full_day);
                if (targetElement) {
                    // Calculate the offset for scrolling based on the roomNameContainer_0
                    const roomNameElement = document.getElementById('roomNameContainer_0');
                    const scrollableContainer = this.$refs.shiftPlan;

                    if (roomNameElement) {
                        // Find the absolute left positions of the target and room name element
                        const roomNameLeft = roomNameElement.getBoundingClientRect().left;
                        const containerLeft = scrollableContainer.getBoundingClientRect().left;

                        // Calculate how much to scroll so that the left side of the target day aligns with the left side of the room name container
                        const scrollLeftPosition = targetElement.offsetLeft - (roomNameLeft + containerLeft + 65);

                        // Set the scroll position directly
                        scrollableContainer.scrollLeft = scrollLeftPosition;
                    }
                }
            }
        },
        getIndexForWeekOrMonth(period, periodKey, periodValue, indexModifier, conditionCallback) {
            let targetIndex = this.days.findIndex(day => day[periodKey] === periodValue && conditionCallback(day));

            // Iterate in the direction specified by indexModifier to find the next valid day
            while (true) {
                targetIndex += indexModifier;
                if (targetIndex < 0 || targetIndex >= this.days.length) {
                    return null; // Out of bounds, return null
                }

                const day = this.days[targetIndex];

                // Skip extra rows and find the valid target
                if (!day.is_extra_row && conditionCallback(day)) {
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
            this.idToHighlight = null;
            this.typeToHighlight = null;
            this.multiEditMode = false;
            this.dayServiceMode = false;
            this.highlightMode = !this.highlightMode;
        },
        toggleMultiEditMode() {
            this.highlightMode = false;
            this.dayServiceMode = false;
            this.multiEditMode = !this.multiEditMode;

            if (!this.multiEditMode) {
                this.userForMultiEdit = null;
            }
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
            this.userToMultiEditCheckedShiftsAndEvents = [];
            this.userForMultiEdit = item;
            if (item !== null) {
                this.userToMultiEditCurrentShifts = this.userForMultiEdit.shift_ids;
            }
        },
        initializeMultiEditSave() {
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
                })
            }

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

            let desiredRoomIds = new Set(),
                desiredDays = new Set(),
                desiredUser = {
                    id: this.userForMultiEdit.id,
                    type: this.userForMultiEdit.type
                };

            this.userToMultiEditCheckedShiftsAndEvents.forEach((shiftAndEvent) => {
                let desiredShift = shiftAndEvent.shift;
                let desiredEvent = shiftAndEvent.event;

                this.shiftsToHandleOnMultiEdit.assignToShift.forEach((shiftToAssign) => {
                    if (desiredShift.id === shiftToAssign.shiftId) {
                        desiredRoomIds.add(desiredEvent.roomId);
                        getDaysOfEvent(
                            formatEventDateByDayJs(desiredEvent.start),
                            formatEventDateByDayJs(desiredEvent.end)
                        ).forEach((desiredDay) => desiredDays.add(desiredDay));
                    }
                });

                this.shiftsToHandleOnMultiEdit.removeFromShift.forEach((shiftIdToRemove) => {
                    if (desiredShift.id === shiftIdToRemove) {
                        desiredRoomIds.add(desiredEvent.roomId);

                        getDaysOfEvent(
                            formatEventDateByDayJs(desiredEvent.start),
                            formatEventDateByDayJs(desiredEvent.end)
                        ).forEach((desiredDay) => desiredDays.add(desiredDay));
                    }
                });
            });

            axios.post(route('shift.multi.edit.save'), {
                userType: this.userForMultiEdit.type,
                userTypeId: this.userForMultiEdit.id,
                shiftsToHandle: this.shiftsToHandleOnMultiEdit
            }).then(() => {
                handleReload(
                    Array.from(desiredRoomIds),
                    Array.from(desiredDays),
                    [desiredUser]
                );
                this.resetMultiEditMode(false);
            });
        },
        resetMultiEditMode(closeMultiEdit = true) {
            this.shiftsToHandleOnMultiEdit = {
                assignToShift: [],
                removeFromShift: []
            };
            this.userToMultiEditCheckedShiftsAndEvents = [];

            if (closeMultiEdit) {
                this.multiEditMode = false;
            }
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

            if ((window.innerHeight - 100) - (this.startHeight + diff) < 100) {
                this.userOverviewHeight = (window.innerHeight - 100) - 200;
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
            if (!this.showUserOverview) {
                this.windowHeight = (window.innerHeight - 100);
            } else {
                this.windowHeight = (window.innerHeight - 100) - this.userOverviewHeight;
            }

            if (window.innerHeight - 100 < 400) {
                this.userOverviewHeight = window.innerHeight - 200;
            }

            // check if userOverviewHeight is not smaller than 100
            if (this.userOverviewHeight < 100) {
                this.userOverviewHeight = 100;
            }
        },
        applySort(shiftPlanWorkerSortEnumName) {
            this.$page.props.user.shift_plan_user_sort_by = shiftPlanWorkerSortEnumName;
            axios.patch(
                route('user.update.shiftPlanUserSortBy', {user: this.$page.props.user.id}),
                {
                    sortBy: shiftPlanWorkerSortEnumName
                }
            );
        },
        resetSort() {
            this.$page.props.user.shift_plan_user_sort_by = null;
            axios.patch(
                route('user.update.shiftPlanUserSortBy', {user: this.$page.props.user.id}),
                {
                    sortBy: null
                }
            );
        },
        sortCraftUsers(users) {
            if (this.$page.props.user.shift_plan_user_sort_by === null) {
                return users.sort((a, b) => a.element.id > b.element.id ? 1 : a.element.id < b.element.id ? -1 : 0);
            }

            return users.sort((workerA, workerB) => {
                let shiftPlanUserSortBy = this.$page.props.user.shift_plan_user_sort_by;

                let getCompareName = (worker) => {
                    let workerIsUserOrFreelancer = worker.type === 0 || worker.type === 1;
                    let sortByFirstName = shiftPlanUserSortBy === 'ALPHABETICALLY_ASCENDING_FIRST_NAME' ||
                        shiftPlanUserSortBy === 'ALPHABETICALLY_DESCENDING_FIRST_NAME';
                    return sortByFirstName ?
                        (
                            workerIsUserOrFreelancer ?
                                worker.element.first_name :
                                worker.element.provider_name
                        ) :
                        (
                            workerIsUserOrFreelancer ?
                                worker.element.last_name :
                                worker.element.provider_name
                        );
                };

                if (
                    shiftPlanUserSortBy === 'ALPHABETICALLY_DESCENDING_FIRST_NAME' ||
                    shiftPlanUserSortBy === 'ALPHABETICALLY_DESCENDING_LAST_NAME'
                ) {
                    return getCompareName(workerB).localeCompare(getCompareName(workerA));
                }

                return getCompareName(workerA).localeCompare(getCompareName(workerB));
            });
        },
        handleShiftAndEventForMultiEdit(checked, shift, event) {
            if (checked && this.userToMultiEditCurrentShifts.includes(shift.id)) {
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
