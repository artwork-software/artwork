<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        title="Calendar Settings"
        description="Configure your calendar settings here."
        modal-size="max-w-4xl"
    >
        <div class="p-5 space-y-6">

            <!-- Abschnitt: Navigation (in allen Ansichten sichtbar) -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Navigation') }}
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Zeitraum in allen Ansichten teilen (users-Spalte, ansichtsübergreifend) -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.share_calendar_date"
                                    id="share_calendar_date"
                                    aria-describedby="share_calendar_date-description"
                                    name="share_calendar_date"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="share_calendar_date" class="font-medium text-gray-900">{{ $t('Share time period across views') }}</label>
                            <p id="share_calendar_date-description" class="text-gray-500 text-xs">
                                {{ $t('Calendar, planning calendar, shift plan and list view always show the same time period. Changing the date in one view applies it everywhere.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- List-View: nur die zwei relevanten Einstellungen -->
            <template v-if="isListView">
                <div>
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Detailed function overview per shift -->
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.detailed_shift_overview"
                                        @change="onDetailedShiftOverviewChange"
                                        id="lv_detailed_shift_overview"
                                        aria-describedby="lv_detailed_shift_overview-description"
                                        name="lv_detailed_shift_overview"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_detailed_shift_overview" class="font-medium text-gray-900">{{ $t('Detailed function overview per shift') }}</label>
                                <p id="lv_detailed_shift_overview-description" class="text-gray-500 text-xs">
                                    {{ $t('Shows assigned persons and unoccupied slots directly under each shift in the list view.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Hide shift row (only available when detailed_shift_overview is on) -->
                        <div class="flex gap-3 pl-6" :class="{ 'opacity-50': !userCalendarSettings.detailed_shift_overview }">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.hide_shift_row"
                                        :disabled="!userCalendarSettings.detailed_shift_overview"
                                        id="lv_hide_shift_row"
                                        aria-describedby="lv_hide_shift_row-description"
                                        name="lv_hide_shift_row"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_hide_shift_row" class="font-medium text-gray-900">{{ $t('Hide shift row') }}</label>
                                <p id="lv_hide_shift_row-description" class="text-gray-500 text-xs">
                                    {{ $t('Hides the shift header row and shows the craft abbreviation in front of each user row time.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Show fully staffed shifts -->
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.show_fully_staffed_shifts"
                                        id="lv_show_fully_staffed_shifts"
                                        aria-describedby="lv_show_fully_staffed_shifts-description"
                                        name="lv_show_fully_staffed_shifts"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_show_fully_staffed_shifts" class="font-medium text-gray-900">{{ $t('Show fully staffed shifts') }}</label>
                                <p id="lv_show_fully_staffed_shifts-description" class="text-gray-500 text-xs">
                                    {{ $t('Also displays shifts that are already fully staffed in the list view.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Show appointments -->
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.show_appointments"
                                        id="lv_show_appointments"
                                        aria-describedby="lv_show_appointments-description"
                                        name="lv_show_appointments"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_show_appointments" class="font-medium text-gray-900">{{ $t('Show appointments') }}</label>
                                <p id="lv_show_appointments-description" class="text-gray-500 text-xs">
                                    {{ $t('Shows appointments alongside shifts in a separate column.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Show notes -->
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.shift_notes"
                                        id="lv_shift_notes"
                                        aria-describedby="lv_shift_notes-description"
                                        name="lv_shift_notes"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_shift_notes" class="font-medium text-gray-900">{{ $t('Show notes') }}</label>
                                <p id="lv_shift_notes-description" class="text-gray-500 text-xs">
                                    {{ $t('Shows stored notes of shifts and appointments on a separate line in the list view.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Group by shift groups -->
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input
                                        v-model="userCalendarSettings.group_by_shift_groups"
                                        id="lv_group_by_shift_groups"
                                        aria-describedby="lv_group_by_shift_groups-description"
                                        name="lv_group_by_shift_groups"
                                        type="checkbox"
                                        class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="lv_group_by_shift_groups" class="font-medium text-gray-900">{{ $t('Sort by shift groups') }}</label>
                                <p id="lv_group_by_shift_groups-description" class="text-gray-500 text-xs">
                                    {{ $t('Groups shifts by their shift group within each room.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Abschnitt: Darstellung & Barrierefreiheit -->
            <div v-if="!isListView">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Appearance & Accessibility') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- High contrast -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.high_contrast"
                                    id="high_contrast"
                                    aria-describedby="high_contrast-description"
                                    name="high_contrast"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="high_contrast" class="font-medium text-gray-900">{{ $t('High contrast') }}</label>
                            <p id="high_contrast-description" class="text-gray-500 text-xs">
                                {{ $t('Increases the color intensity in the calendar to make texts and elements more clearly visible, ideal for better readability and accessibility.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Expand days -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.expand_days"
                                    id="expand_days"
                                    aria-describedby="expand_days-description"
                                    name="expand_days"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="expand_days" class="font-medium text-gray-900">{{ $t('Expand days') }}</label>
                            <p id="expand_days-description" class="text-gray-500 text-xs">
                                {{ $t('Expands all days in the calendar automatically, so you can see all events at a glance without having to scroll.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Künstler:innen statt Termintitel (Kompaktansicht) -->
                    <div class="flex gap-3" v-if="!inShiftPlan && !isDailyView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_artist_names_as_title"
                                    id="show_artist_names_as_title"
                                    aria-describedby="show_artist_names_as_title-description"
                                    name="show_artist_names_as_title"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_artist_names_as_title" class="font-medium text-gray-900">{{ $t('Artist names instead of event title') }}</label>
                            <p id="show_artist_names_as_title-description" class="text-gray-500 text-xs">
                                {{ $t('In the compact view (zoom below 80%), the artist names of the project are shown instead of the event title.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Tagesbemerkungen (nur bei aktivem Instanz-Setting & Sichtrecht; bei Pflicht disabled mit Tooltip) -->
                    <div class="flex gap-3"
                         v-if="!inShiftPlan && !isDailyView && dayRemarksState.enabled && dayRemarksState.can_view"
                         :class="{ 'opacity-50': dayRemarksState.mandatory }"
                         :title="dayRemarksState.mandatory ? $t('In your artwork the day remarks column is set as mandatory.') : null">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_day_remarks"
                                    :disabled="dayRemarksState.mandatory"
                                    id="show_day_remarks"
                                    aria-describedby="show_day_remarks-description"
                                    name="show_day_remarks"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_day_remarks" class="font-medium text-gray-900">{{ $t('Day remarks') }}</label>
                            <p id="show_day_remarks-description" class="text-gray-500 text-xs">
                                {{ dayRemarksState.mandatory
                                    ? $t('In your artwork the day remarks column is set as mandatory.')
                                    : $t('Shows the day remarks column next to the date column in the calendar.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Use event status colour (falls Modul aktiv & nicht Schichtplan) -->
                    <div class="flex gap-3" v-if="usePage().props.event_status_module && !inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.use_event_status_color"
                                    id="use_event_status_color"
                                    aria-describedby="use_event_status_color-description"
                                    name="use_event_status_color"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    @change="onStatusColorChange"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="use_event_status_color" class="font-medium text-gray-900">{{ $t('Use event status colour') }}</label>
                            <p id="use_event_status_color-description" class="text-gray-500 text-xs">
                                {{ $t('Colors calendar entries according to their status, making it easier to quickly identify scheduled, confirmed or completed events, for example.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Use main category colour (nicht Schichtplan) -->
                    <div class="flex gap-3" v-if="!inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.use_main_category_color"
                                    id="use_main_category_color"
                                    aria-describedby="use_main_category_color-description"
                                    name="use_main_category_color"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                    @change="onMainCategoryColorChange"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="use_main_category_color" class="font-medium text-gray-900">{{ $t('Event colour by main category') }}</label>
                            <p id="use_main_category_color-description" class="text-gray-500 text-xs">
                                {{ $t('Colors calendar entries according to the main category of the project. Events without a main category are shown in anthracite, events without a project in grey.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Raumspaltenbreite (nur Standard-Kalender; vom Zoom entkoppelt) —
                     bewusst am Ende der Rubrik, damit das Dropdown das Checkbox-Raster nicht aufbricht -->
                <div v-if="!inShiftPlan && !isDailyView" class="mt-4 md:max-w-sm">
                    <ArtworkBaseListbox
                        :model-value="selectedColumnWidthOption"
                        @update:model-value="onColumnWidthChange"
                        :items="columnWidthOptions"
                        by="id"
                        option-label="name"
                        :label="$t('Room column width')"
                        :enable-search="false"
                    />
                    <p class="text-gray-500 text-xs mt-1">
                        {{ $t('The room column width stays the same at every zoom level. The zoom only controls the row height.') }}
                    </p>
                </div>
            </div>

            <hr v-if="!isListView" class="border-gray-200">

            <!-- Abschnitt: Sichtbarkeit & Filter -->
            <div v-if="!isListView">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Visibility & Filters') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Hide unoccupied rooms -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.hide_unoccupied_rooms"
                                    id="hide_unoccupied_rooms"
                                    aria-describedby="hide_unoccupied_rooms-description"
                                    name="hide_unoccupied_rooms"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="hide_unoccupied_rooms" class="font-medium text-gray-900">{{ $t('Hide unoccupied rooms') }}</label>
                            <p id="hide_unoccupied_rooms-description" class="text-gray-500 text-xs">
                                {{ $t('Hides rooms in the calendar in which no events are entered, for a clearer display of active areas.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Show planned events (nicht Schichtplan, nicht Planungsansicht, mit Permission) -->
                    <div
                        class="flex gap-3"
                        v-if="!isPlanning && !inShiftPlan && (can('can see planning calendar') || can('can edit planning calendar') || is('artwork admin'))"
                    >
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_planned_events"
                                    id="show_planned_events"
                                    aria-describedby="show_planned_events-description"
                                    name="show_planned_events"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_planned_events" class="font-medium text-gray-900">{{ $t('Show planned events') }}</label>
                            <p id="show_planned_events-description" class="text-gray-500 text-xs">
                                {{ $t('Shows provisionally entered, not yet confirmed dates, helpful for orientation in further planning.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Show only not fully staffed shifts (nur Schichtplan) -->
                    <div class="flex gap-3" v-if="inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_only_not_fully_staffed_shifts"
                                    id="show_only_not_fully_staffed_shifts"
                                    aria-describedby="show_only_not_fully_staffed_shifts-description"
                                    name="show_only_not_fully_staffed_shifts"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_only_not_fully_staffed_shifts" class="font-medium text-gray-900">{{ $t('Only show shifts that are not fully staffed') }}</label>
                            <p id="show_only_not_fully_staffed_shifts-description" class="text-gray-500 text-xs">
                                {{ $t('Only displays shifts where at least one position still has capacity for additional staff.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Projektzuordnungen (nur Schichtplan-Tagesansicht / Projekt-Schichten-Tab) -->
                    <div class="flex gap-3" v-if="inShiftPlan && isDailyView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_project_assignments"
                                    id="show_project_assignments"
                                    aria-describedby="show_project_assignments-description"
                                    name="show_project_assignments"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_project_assignments" class="font-medium text-gray-900">{{ $t('Show project assignments') }}</label>
                            <p id="show_project_assignments-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the assigned persons overview and the avatars in the day bars of the project shift tab.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Show fully staffed shifts (nur Listenansicht) -->
                    <div class="flex gap-3" v-if="isListView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_fully_staffed_shifts"
                                    id="show_fully_staffed_shifts_filter"
                                    aria-describedby="show_fully_staffed_shifts_filter-description"
                                    name="show_fully_staffed_shifts_filter"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_fully_staffed_shifts_filter" class="font-medium text-gray-900">{{ $t('Show fully staffed shifts') }}</label>
                            <p id="show_fully_staffed_shifts_filter-description" class="text-gray-500 text-xs">
                                {{ $t('Also displays shifts that are already fully staffed in the list view.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Hide unoccupied days (nur Schichtplan) -->
                    <div class="flex gap-3" v-if="inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.hide_unoccupied_days"
                                    id="hide_unoccupied_days"
                                    aria-describedby="hide_unoccupied_days-description"
                                    name="hide_unoccupied_days"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="hide_unoccupied_days" class="font-medium text-gray-900">{{ $t('Hide unoccupied days') }}</label>
                            <p id="hide_unoccupied_days-description" class="text-gray-500 text-xs">
                                {{ $t('Hides days in the calendar on which no events are scheduled, for a more focused view of active days.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Wiederholende Events -->
                    <div class="flex gap-3" v-if="!inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.repeating_events"
                                    id="repeating_events"
                                    aria-describedby="repeating_events-description"
                                    name="repeating_events"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="repeating_events" class="font-medium text-gray-900">{{ $t('Repeat event') }}</label>
                            <p id="repeating_events-description" class="text-gray-500 text-xs">
                                {{ $t('Indicates events that take place regularly, ideal for planning recurring meetings or rehearsals.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Projektgruppe anzeigen -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.display_project_groups"
                                    id="display_project_groups"
                                    aria-describedby="display_project_groups-description"
                                    name="display_project_groups"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="display_project_groups" class="font-medium text-gray-900">{{ $t('Show project group') }}</label>
                            <p id="display_project_groups-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the associated project group of an event in the calendar, helpful for assignment and overview with several groups.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Schichten (Permission oder außerhalb Schichtplan) -->
                    <div class="flex gap-3" v-if="can('can manage workers|can plan shifts') || is('artwork admin') || !inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.work_shifts"
                                    id="work_shifts"
                                    aria-describedby="work_shifts-description"
                                    name="work_shifts"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="work_shifts" class="font-medium text-gray-900">{{ $t('Show shifts') }}</label>
                            <p id="work_shifts-description" class="text-gray-500 text-xs">
                                {{ $t('Shows standalone shifts as separate cards in the matching room-day cells of the calendar, mixed with the events and sorted by start time.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_timeline"
                                    id="show_timeline"
                                    aria-describedby="show_timeline-description"
                                    name="show_timeline"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_timeline" class="font-medium text-gray-900">{{ $t('Timeline') }}</label>
                            <p id="show_timeline-description" class="text-gray-500 text-xs">
                                {{ $t('Zeige Icon an um vorhandene Timelines von Terminen anzuzeigen und Timelines zu ergänzen') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <hr v-if="!isListView" class="border-gray-200">

            <!-- Abschnitt: Inhalte in Events -->
            <div v-if="!isListView">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Event Content') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Event name -->
                    <div class="flex gap-3" v-if="!inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.event_name"
                                    id="event_name"
                                    aria-describedby="event_name-description"
                                    name="event_name"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="event_name" class="font-medium text-gray-900">{{ $t('Event name') }}</label>
                            <p id="event_name-description" class="text-gray-500 text-xs">
                                {{ $t('The title or name of the calendar entry provides information about the content or purpose of the event at a glance.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="flex gap-3" v-if="!inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.description"
                                    id="description"
                                    aria-describedby="description-description"
                                    name="description"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="description" class="font-medium text-gray-900">{{ $t('Description') }}</label>
                            <p id="description-description" class="text-gray-500 text-xs">
                                {{ $t('Contains additional information or notes about the event, perfect for recording details or special features.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Project status -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.project_status"
                                    id="project_status"
                                    aria-describedby="project_status-description"
                                    name="project_status"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="project_status" class="font-medium text-gray-900">{{ $t('Project Status') }}</label>
                            <p id="project_status-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the current status of the project in the calendar, from planning to implementation, to keep an eye on progress at all times.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Project managers -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.project_management"
                                    id="project_management"
                                    aria-describedby="project_management-description"
                                    name="project_management"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="project_management" class="font-medium text-gray-900">{{ $t('Project managers') }}</label>
                            <p id="project_management-description" class="text-gray-500 text-xs">
                                {{ $t('List the responsible project managers in the calendar and ensure clarity as to who is responsible for coordination and management.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Event creator -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_event_creator"
                                    id="show_event_creator"
                                    aria-describedby="show_event_creator-description"
                                    name="show_event_creator"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_event_creator" class="font-medium text-gray-900">{{ $t('Event creator') }}</label>
                            <p id="show_event_creator-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the profile picture of the person who created the event, helpful to know who to contact with questions.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Artists -->
                    <div class="flex gap-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.project_artists"
                                    id="project_artists"
                                    aria-describedby="project_artists-description"
                                    name="project_artists"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="project_artists" class="font-medium text-gray-900">{{ $t('Artists') }}</label>
                            <p id="project_artists-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the artists involved in the project in the calendar, helpful for an overview of who is involved and when.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Qualifications (nur Schichtplan) -->
                    <div class="flex gap-3" v-if="inShiftPlan || isListView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_qualifications"
                                    id="show_qualifications"
                                    aria-describedby="show_qualifications-description"
                                    name="show_qualifications"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_qualifications" class="font-medium text-gray-900">{{ $t('Show qualifications') }}</label>
                            <p id="show_qualifications-description" class="text-gray-500 text-xs">
                                {{ $t('Shows the required or existing qualifications of a shift in the calendar, helpful for precise shift planning.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Shift notes (nur Schichtplan) -->
                    <div class="flex gap-3" v-if="inShiftPlan || isListView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.shift_notes"
                                    id="shift_notes"
                                    aria-describedby="shift_notes-description"
                                    name="shift_notes"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="shift_notes" class="font-medium text-gray-900">{{ $t('Show notes') }}</label>
                            <p id="shift_notes-description" class="text-gray-500 text-xs">
                                {{ $t('Shows stored notes on appointments in the calendar, handy for having additional information directly in view.') }}
                            </p>
                        </div>
                    </div>

                    <!-- show_shift_group_tag (nur Schichtplan) -->
                    <div class="flex gap-3" v-if="inShiftPlan || isListView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_shift_group_tag"
                                    id="show_shift_group_tag"
                                    aria-describedby="show_shift_group_tag-description"
                                    name="show_shift_group_tag"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_shift_group_tag" class="font-medium text-gray-900">{{ $t('Show Shift group tag') }}</label>
                            <p id="shift_notes-description" class="text-gray-500 text-xs">
                                {{ $t('Displays the assigned shift group tags in the calendar, useful for quickly identifying and categorizing shifts.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Detailed function overview per shift (nur Listenansicht) -->
                    <div class="flex gap-3" v-if="isListView">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.detailed_shift_overview"
                                    id="detailed_shift_overview"
                                    aria-describedby="detailed_shift_overview-description"
                                    name="detailed_shift_overview"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="detailed_shift_overview" class="font-medium text-gray-900">{{ $t('Detailed function overview per shift') }}</label>
                            <p id="detailed_shift_overview-description" class="text-gray-500 text-xs">
                                {{ $t('Shows assigned persons and unoccupied slots directly under each shift in the list view.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Abschnitt: Planungsmodus -->
            <div v-if="isPlanning && !inShiftPlan">
                <hr class="border-gray-200 mb-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('Planning Mode') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Show fixed events (im Planning & nicht Schichtplan) -->
                    <div class="flex gap-3" v-if="isPlanning && !inShiftPlan">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input
                                    v-model="userCalendarSettings.show_unplanned_events"
                                    id="show_unplanned_events"
                                    aria-describedby="show_unplanned_events-description"
                                    name="show_unplanned_events"
                                    type="checkbox"
                                    class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto"
                                />
                                <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="show_unplanned_events" class="font-medium text-gray-900">{{ $t('Show fixed events') }}</label>
                            <p id="show_unplanned_events-description" class="text-gray-500 text-xs">
                                {{ $t('Highlights firmly scheduled events in the calendar, ideal for quickly recognizing binding times.') }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Hidden (bewusst) -->
            <div class="hidden items-center py-1">
                <input
                    id="cb-options"
                    v-model="userCalendarSettings.options"
                    type="checkbox"
                    class="input-checklist"
                />
                <label
                    for="cb-options"
                    :class="userCalendarSettings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                    class="ml-4 my-auto text-secondary cursor-pointer"
                >
                    {{ $t('Option prioritization') }}
                </label>
            </div>
        </div>

        <div class="flex justify-end px-5 pb-5">
            <BaseUIButton :label="$t('Save')" is-add-button @click="saveUserCalendarSettings"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
// Button/Input nicht genutzt – bewusst entfernt, um Bundle clean zu halten
import { can, is } from "laravel-permission-to-vuejs";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import { computed } from "vue";

const props = defineProps({
    isPlanning: { type: Boolean, default: false },
    inShiftPlan: { type: Boolean, default: false },
    isDailyView: { type: Boolean, default: false },
    isListView: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const listViewSettings = props.isListView ? usePage().props.listViewSettings : null;

// Tagesbemerkungen: Instanz-Setting + Rechte (global geteilt via HandleInertiaRequests)
const dayRemarksState = usePage().props.day_remarks
    ?? { enabled: false, mandatory: false, can_view: false, can_edit: false };

const activeSettings = props.isListView
    ? listViewSettings
    : props.inShiftPlan
        ? (props.isDailyView
            ? (usePage().props.shift_plan_daily_settings ?? usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings)
            : (usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings))
        : (props.isDailyView
            ? (usePage().props.daily_view_calendar_settings ?? usePage().props.auth.user.calendar_settings)
            : usePage().props.auth.user.calendar_settings);

const userCalendarSettings = props.isListView
    ? useForm({
        // Ansichtsübergreifend (users-Spalte), daher aus auth.user statt den Settings
        share_calendar_date: usePage().props.auth.user.share_calendar_date ?? false,
        show_qualifications: activeSettings ? activeSettings.show_qualifications : false,
        shift_notes: activeSettings ? activeSettings.shift_notes : false,
        show_shift_group_tag: activeSettings ? activeSettings.show_shift_group_tag : false,
        show_fully_staffed_shifts: activeSettings ? activeSettings.show_fully_staffed_shifts : false,
        detailed_shift_overview: activeSettings ? activeSettings.detailed_shift_overview : false,
        show_appointments: activeSettings ? activeSettings.show_appointments : false,
        group_by_shift_groups: activeSettings ? activeSettings.group_by_shift_groups : false,
        hide_shift_row: activeSettings ? activeSettings.hide_shift_row : false,
    })
    : useForm({
        is_daily_view: props.isDailyView,
        is_shift_plan: props.inShiftPlan,
        is_planning: props.isPlanning,
        // Ansichtsübergreifend (users-Spalte), daher aus auth.user statt den Settings
        share_calendar_date: usePage().props.auth.user.share_calendar_date ?? false,
        project_status: activeSettings ? activeSettings.project_status : false,
        project_artists: activeSettings ? activeSettings.project_artists : false,
        options: activeSettings ? activeSettings.options : false,
        project_management: activeSettings ? activeSettings.project_management : false,
        show_event_creator: activeSettings ? activeSettings.show_event_creator : false,
        repeating_events: activeSettings ? activeSettings.repeating_events : false,
        work_shifts: activeSettings ? activeSettings.work_shifts : false,
        description: activeSettings ? activeSettings.description : false,
        event_name: activeSettings ? activeSettings.event_name : false,
        high_contrast: activeSettings ? activeSettings.high_contrast : false,
        expand_days: activeSettings ? activeSettings.expand_days : false,
        use_event_status_color: activeSettings ? activeSettings.use_event_status_color : false,
        use_main_category_color: activeSettings ? activeSettings.use_main_category_color : false,
        hide_unoccupied_rooms: activeSettings ? activeSettings.hide_unoccupied_rooms : false,
        hide_unoccupied_days: activeSettings ? activeSettings.hide_unoccupied_days : false,
        display_project_groups: activeSettings ? activeSettings.display_project_groups : false,
        show_unplanned_events: activeSettings ? activeSettings.show_unplanned_events : false,
        show_planned_events: activeSettings ? activeSettings.show_planned_events : false,
        show_qualifications: activeSettings ? activeSettings.show_qualifications : false,
        shift_notes: activeSettings ? activeSettings.shift_notes : false,
        show_shift_group_tag: activeSettings ? activeSettings.show_shift_group_tag : false,
        show_timeline: activeSettings ? activeSettings.show_timeline : false,
        show_only_not_fully_staffed_shifts: activeSettings ? activeSettings.show_only_not_fully_staffed_shifts : false,
        // Default AN; fehlender Wert (Alt-Settings) zählt als aktiviert
        ...(props.inShiftPlan && props.isDailyView
            ? { show_project_assignments: activeSettings ? (activeSettings.show_project_assignments ?? true) : true }
            : {}),
        // Nur Standard-Kalender: Spalten existieren nur auf user_calendar_settings
        ...(!props.inShiftPlan && !props.isDailyView
            ? {
                calendar_column_width: activeSettings?.calendar_column_width ?? 212,
                show_artist_names_as_title: activeSettings?.show_artist_names_as_title ?? false,
                // Default AN; bei Pflicht-Einstellung immer als aktiviert angezeigt/gespeichert
                show_day_remarks: dayRemarksState.mandatory
                    ? true
                    : (activeSettings?.show_day_remarks ?? true),
            }
            : {}),
    });

// Raumspaltenbreite: feste Presets, gespeichert als px-Wert
const columnWidthOptions = [
    { id: 160, name: 'Schmal (160 px)' },
    { id: 212, name: 'Standard (212 px)' },
    { id: 280, name: 'Breit (280 px)' },
    { id: 320, name: 'Sehr breit (320 px)' },
];

const selectedColumnWidthOption = computed(() =>
    columnWidthOptions.find((option) => option.id === userCalendarSettings.calendar_column_width)
        ?? columnWidthOptions[1]
);

const onColumnWidthChange = (option) => {
    if (option?.id) {
        userCalendarSettings.calendar_column_width = option.id;
    }
};

const onStatusColorChange = () => {
    if (userCalendarSettings.use_event_status_color) {
        userCalendarSettings.use_main_category_color = false;
    }
};

const onDetailedShiftOverviewChange = () => {
    if (!userCalendarSettings.detailed_shift_overview) {
        userCalendarSettings.hide_shift_row = false;
    }
};

const onMainCategoryColorChange = () => {
    if (userCalendarSettings.use_main_category_color) {
        userCalendarSettings.use_event_status_color = false;
    }
};

const saveUserCalendarSettings = () => {
    if (props.isListView) {
        userCalendarSettings.patch(
            route("user.shift_list_view_settings.update", { user: usePage().props.auth.user.id }),
            {
                preserveScroll: true,
                preserveState: false,
            }
        );
        return;
    }

    const valuesToReload = [];
    let preserveState = true;

    if (userCalendarSettings.project_management) valuesToReload.push("leaders");
    if (userCalendarSettings.project_status) valuesToReload.push("status");

    // Räume/Calendar immer neu, wenn sich die Raum-Sichtbarkeit toggelt
    if (userCalendarSettings.hide_unoccupied_rooms || !userCalendarSettings.hide_unoccupied_rooms) {
        valuesToReload.push("rooms", "calendar", "calendarData");
        preserveState = false;
    }

    userCalendarSettings.patch(
        route("user.calendar_settings.update", { user: usePage().props.auth.user.id }),
        {
            preserveScroll: true,
            preserveState,
            onSuccess: () => {
                if (valuesToReload.length > 0) {
                    router.reload({ only: valuesToReload });
                }
            },
        }
    );
};
</script>

<style scoped>
/* bewusst leer – Styling ausschließlich über Utility-Klassen */
</style>
