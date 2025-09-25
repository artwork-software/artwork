<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        title="Calendar Settings"
        description="Configure your calendar settings here."
        modal-size="max-w-4xl"
    >
        <div class="p-5 space-y-6">

            <!-- Abschnitt: Darstellung & Barrierefreiheit -->
            <div>
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
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Abschnitt: Sichtbarkeit & Filter -->
            <div>
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
                            <label for="work_shifts" class="font-medium text-gray-900">{{ $t('Shifts') }}</label>
                            <p id="work_shifts-description" class="text-gray-500 text-xs">
                                {{ $t('Shows entered work or deployment shifts in the calendar, useful for clear personnel and deployment planning.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            <!-- Abschnitt: Inhalte in Events -->
            <div>
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
                    <div class="flex gap-3" v-if="!inShiftPlan">
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
                    <div class="flex gap-3" v-if="!inShiftPlan">
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

                    <!-- Artists -->
                    <div class="flex gap-3" v-if="!inShiftPlan">
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
                    <div class="flex gap-3" v-if="inShiftPlan">
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
                    <div class="flex gap-3" v-if="inShiftPlan">
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

                    <!-- Show planned events (außerhalb Planning, nicht Schichtplan, mit Permission) -->
                    <div
                        class="flex gap-3"
                        v-if="!isPlanning && !inShiftPlan && (can('can see planning calendar | can edit planning calendar') || is('artwork admin'))"
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
            <ArtworkBaseModalButton @click="saveUserCalendarSettings">
                {{ $t('Save') }}
            </ArtworkBaseModalButton>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
// Button/Input nicht genutzt – bewusst entfernt, um Bundle clean zu halten
import { can, is } from "laravel-permission-to-vuejs";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    isPlanning: { type: Boolean, default: false },
    inShiftPlan: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const userCalendarSettings = useForm({
    project_status: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_status : false,
    project_artists: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_artists : false,
    options: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.options : false,
    project_management: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_management : false,
    repeating_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.repeating_events : false,
    work_shifts: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.work_shifts : false,
    description: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.description : false,
    event_name: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.event_name : false,
    high_contrast: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.high_contrast : false,
    expand_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.expand_days : false,
    use_event_status_color: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.use_event_status_color : false,
    hide_unoccupied_rooms: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.hide_unoccupied_rooms : false,
    hide_unoccupied_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.hide_unoccupied_days : false,
    display_project_groups: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.display_project_groups : false,
    show_unplanned_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_unplanned_events : false,
    show_planned_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_planned_events : false,
    show_qualifications: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_qualifications : false,
    shift_notes: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.shift_notes : false,
});

const saveUserCalendarSettings = () => {
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
