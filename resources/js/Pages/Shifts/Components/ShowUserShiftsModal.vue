<template>
    <ArtworkBaseModal
        :title="`${day.dayString} ${day.fullDay}`"
        description=""
        modal-size="max-w-4xl"
        @close="closeModal"
    >
        <div class="space-y-7 text-sm">
            <!-- Kopfbereich: User-Info -->
            <section class="flex items-center justify-between gap-4 border-b border-border-subtle pb-4">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <img
                            :src="user.element.profile_photo_url"
                            class="object-cover h-16 w-16 rounded-full ring-2 ring-border-subtle"
                            alt=""
                        />

                        <span
                            class="absolute -bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center justify-center rounded-full border px-1.5 py-0.5 text-[10px] font-medium first-letter:capitalize"
                            :class="badgeClassForType"
                        >
                            <span v-if="user.element.type === 'service_provider'">
                                {{ t('Service provider') }}
                            </span>
                            <span v-else-if="user.element.type === 'freelancer'">
                                {{ t('external') }}
                            </span>
                            <span v-else>
                                {{ t('internal') }}
                            </span>
                        </span>
                    </div>


                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-base font-semibold text-text">
                                <span v-if="user.element.type === 'service_provider'">
                                    {{ user.element.provider_name }}
                                </span>
                                <span v-else>
                                    {{ user.element.first_name }} {{ user.element.last_name }}
                                </span>
                            </p>
                        </div>
                        <p class="mt-0.5 text-xs text-text-subtle">
                            {{ t('Overview for this day') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2.5 py-1 text-[11px] text-text-muted hover:border-accent-700 hover:text-accent-700 transition-colors"
                        :title="t('Show shift history for this person and day')"
                        @click="openHistory"
                    >
                        <PropertyIcon name="IconHistory" class="h-3.5 w-3.5" stroke-width="2" />
                        <span>{{ t('Shift history') }}</span>
                    </button>

                    <div class="hidden sm:flex flex-col items-end gap-1 text-xs">
                        <span class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-1 text-text-muted border border-border-subtle">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-success"></span>
                            {{ day.dayString }}
                        </span>
                        <span class="text-[11px] text-text-subtle">
                            {{ day.fullDay }}
                        </span>
                    </div>
                </div>
            </section>

            <div class="">
                <div class="space-y-6">
                    <!-- Schichten an diesem Tag -->
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Assigned shifts') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ t('All shifts assigned to this person on the selected day.') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="shiftsForDay.length" class="rounded-xl border border-border-subtle bg-surface-sunken/70 px-3 py-2">
                            <template v-for="shift in shiftsForDay" :key="shift.id">
                                <div class="flex items-center justify-between group border-b last:border-b-0 border-dashed border-border-subtle py-2" :id="'shift-' + shift.id">
                                    <SingleShiftInShiftOverviewUser
                                        :user="user"
                                        :shift="shift"
                                        @shiftDeleted="handleShiftDeleted"
                                    />
                                </div>
                            </template>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-2 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/60 px-3 py-3 text-xs text-text-subtle"
                        >
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-border"></span>
                            <span>{{ t('No shifts assigned for this day.') }}</span>
                        </div>
                    </section>

                    <!-- Projekte (Zuordnungen + Wünsche an diesem Tag) -->
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Projects') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ t('Binding project assignments and wishes for this day.') }}
                                </p>
                            </div>
                            <button
                                v-if="canPlanShifts || isOwnCell"
                                type="button"
                                class="hidden sm:inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2.5 py-1 text-[11px] text-text-muted hover:border-accent-700 hover:text-accent-700 transition-colors"
                                @click="showProjectAssignmentModal = true"
                            >
                                <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                                <span>{{ canPlanShifts ? t('Assign project') : t('Enter project wish') }}</span>
                            </button>
                        </div>

                        <p
                            v-if="projectAssignmentError"
                            class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-xs text-danger"
                        >
                            {{ projectAssignmentError }}
                        </p>

                        <div v-if="assignmentsForDay.length" class="rounded-xl border border-border-subtle bg-surface-sunken/70 px-3 py-2">
                            <div
                                v-for="assignment in assignmentsForDay"
                                :key="assignment.id"
                                class="flex items-center justify-between gap-2 border-b last:border-b-0 border-dashed border-border-subtle py-2"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorForProjectId(assignment.project_id) }"
                                        ></span>
                                        <span
                                            class="truncate text-xs text-text"
                                            :class="assignment.type === 'wish' ? 'italic' : ''"
                                        >
                                            {{ assignment.project_name }}
                                        </span>
                                        <span
                                            v-if="assignment.type === 'wish'"
                                            class="shrink-0 rounded-full bg-surface-sunken px-2 py-0.5 text-[10px] text-text-subtle italic"
                                        >
                                            {{ t('Wish') }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-text-subtle mt-0.5 pl-4.5">
                                        {{ formatAssignmentDate(assignment.series_start) }} - {{ formatAssignmentDate(assignment.series_end) }}
                                        <template v-if="assignment.is_full_period"> &middot; {{ t('Entire project period') }}</template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button
                                        v-if="assignment.type === 'wish' && canPlanShifts"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-full border border-success-border bg-white px-2 py-0.5 text-[10px] text-success hover:border-success transition-colors"
                                        :disabled="projectAssignmentActionKey !== null"
                                        :class="projectAssignmentActionKey === `accept:${assignment.id}` ? 'cursor-wait opacity-50' : ''"
                                        :title="t('Accept wish as binding assignment')"
                                        @click="acceptAssignmentWish(assignment)"
                                    >
                                        <PropertyIcon name="IconCheck" class="h-3 w-3" stroke-width="2" />
                                        {{ t('Accept') }}
                                    </button>
                                    <button
                                        v-if="canDeleteAssignment(assignment)"
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-md p-1 text-text-subtle hover:text-danger hover:bg-danger-surface transition-colors"
                                        :disabled="projectAssignmentActionKey !== null"
                                        :class="projectAssignmentActionKey === `delete-day:${assignment.id}` ? 'cursor-wait opacity-50' : ''"
                                        :title="t('Remove for this day only')"
                                        @click="deleteAssignment(assignment, false)"
                                    >
                                        <PropertyIcon name="IconTrash" class="h-3.5 w-3.5" stroke-width="1.5" />
                                    </button>
                                    <button
                                        v-if="canDeleteAssignment(assignment) && assignment.series_start !== assignment.series_end"
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-md p-1 text-text-subtle hover:text-danger hover:bg-danger-surface transition-colors"
                                        :disabled="projectAssignmentActionKey !== null"
                                        :class="projectAssignmentActionKey === `delete-group:${assignment.id}` ? 'cursor-wait opacity-50' : ''"
                                        :title="t('Remove entire assignment')"
                                        @click="deleteAssignment(assignment, true)"
                                    >
                                        <PropertyIcon name="IconTrashX" class="h-3.5 w-3.5" stroke-width="1.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-2 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/60 px-3 py-3 text-xs text-text-subtle"
                        >
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-border"></span>
                            <span>{{ t('No project assignments for this day.') }}</span>
                        </div>
                    </section>

                    <!-- Individuelle Zeiten -->
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Individual time') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ t('Define or adjust custom times for this person on this day.') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="hidden sm:inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2.5 py-1 text-[11px] text-text-muted hover:border-accent-700 hover:text-accent-700 transition-colors"
                                @click="addIndividualTime"
                            >
                                <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                                <span>{{ t('Add individual time') }}</span>
                            </button>
                        </div>

                        <div v-if="individualTimesByDate.length > 0">
                            <!-- Kopfzeile -->
                            <div class="hidden md:block text-[11px] text-text-subtle">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 px-1">
                                    <div>
                                        {{ t('Title') }}
                                    </div>
                                    <div class="col-span-2">
                                        {{ t('Period') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Einträge -->
                            <div
                                v-for="(individual_time, index) in individualTimesByDate"
                                :key="individual_time.id ?? index"
                                class="rounded-lg border border-border-subtle bg-white px-3 py-3 mt-2 shadow-sm/10 group"
                            >
                                <!-- Nur Einträge für diesen Tag -->
                                <div
                                    v-if="individual_time?.days_of_individual_time?.includes(day.withoutFormat)"
                                    class="grid grid-cols-1 md:grid-cols-4 gap-2 items-start"
                                >
                                    <!-- FALL 1: Einfache individuelle Zeit (ohne Serie) -->
                                    <template v-if="!individual_time.series_uuid">
                                        <BaseInput
                                            id="title"
                                            v-model="individual_time.title"
                                            :label="t('Title')"
                                            :show-label="false"
                                            no-margin-top
                                        />
                                        <div class="flex items-center justify-center col-span-2 gap-1">
                                            <BaseInput
                                                type="time"
                                                id="start_time"
                                                classes="rounded-r-none"
                                                v-model="individual_time.start_time"
                                                :label="t('Start time')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                            <BaseInput
                                                type="time"
                                                id="end_time"
                                                v-model="individual_time.end_time"
                                                classes="border-l-0 rounded-l-none"
                                                :label="t('End time')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                        </div>
                                        <div
                                            v-if="individual_time.id"
                                            class="flex items-center justify-end md:justify-center"
                                        >

                                            <BaseUIButton
                                                label="Delete"
                                                :use-translation="true"
                                                is-delete-button
                                                @click="deleteIndividualTimeById(individual_time)"
                                                icon="IconTrash"
                                            />
                                        </div>

                                        <!-- Break Minutes -->
                                        <div class="col-span-full mt-3 pt-3 border-t border-border-subtle">
                                            <BaseInput
                                                type="number"
                                                :id="'break_minutes_' + index"
                                                v-model.number="individual_time.break_minutes"
                                                :label="t('Break time (minutes)')"
                                                :show-label="false"
                                                :min="0"
                                                :step="1"
                                                no-margin-top
                                                class="max-w-xs"
                                                @input="markBreakAsManuallyEdited(individual_time)"
                                            />
                                            <p class="text-[11px] text-text-subtle mt-1.5 leading-snug">
                                                {{ t('This time will be deducted from the working hours when calculating the daily working time.') }}
                                            </p>
                                        </div>
                                    </template>

                                    <!-- FALL 3: Zeit gehört zu einer Serie -->
                                    <template v-else>
                                        <div class="space-y-1.5">
                                            <BaseInput
                                                id="title"
                                                v-model="individual_time.title"
                                                :label="t('Title')"
                                                :show-label="false"
                                                no-margin-top
                                            />
                                            <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-[10px] font-medium text-accent-600 border border-accent-100">
                                                • {{ t('Series entry') }}
                                            </span>
                                        </div>
                                        <div class="col-span-2">
                                            <div class="flex items-center justify-center gap-1">
                                                <BaseInput
                                                    type="time"
                                                    id="start_time"
                                                    classes="rounded-r-none"
                                                    v-model="individual_time.start_time"
                                                    :label="t('Start time')"
                                                    :show-label="false"
                                                    no-margin-top
                                                />
                                                <BaseInput
                                                    type="time"
                                                    id="end_time"
                                                    v-model="individual_time.end_time"
                                                    classes="border-l-0 rounded-l-none"
                                                    :label="t('End time')"
                                                    :show-label="false"
                                                    no-margin-top
                                                />
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2 col-span-1">
                                            <BaseUIButton
                                                is-small
                                                label="Edit series"
                                                :use-translation="true"
                                                @click="openSeriesModal(individual_time)"
                                            />
                                            <BaseUIButton
                                                is-small
                                                label="Delete"
                                                :use-translation="true"
                                                is-delete-button
                                                @click="deleteIndividualTimeById(individual_time)"
                                                icon="IconTrash"
                                            />
                                        </div>
                                        <div class="text-[11px] text-text-subtle col-span-full mt-1">
                                            {{ t('This time belongs to a series. If you change it, it will be detached from the series.') }}
                                        </div>

                                        <!-- Break Minutes -->
                                        <div class="col-span-full mt-3 pt-3 border-t border-border-subtle">
                                            <label class="block text-[11px] font-medium text-text-subtle mb-1.5">
                                                {{ t('Break time (minutes)') }}
                                            </label>
                                            <BaseInput
                                                type="number"
                                                :id="'break_minutes_series_' + index"
                                                v-model.number="individual_time.break_minutes"
                                                :label="t('Break time (minutes)')"
                                                :show-label="false"
                                                :min="0"
                                                :step="1"
                                                no-margin-top
                                                class="max-w-xs"
                                                @input="markBreakAsManuallyEdited(individual_time)"
                                            />
                                            <p class="text-[11px] text-text-subtle mt-1.5 leading-snug">
                                                {{ t('This time will be deducted from the working hours when calculating the daily working time.') }}
                                            </p>
                                        </div>
                                    </template>
                                </div>

                                <div
                                    v-if="individual_time.error"
                                    class="text-[11px] text-danger mt-2"
                                >
                                    {{ individual_time.error }}
                                </div>
                            </div>

                            <!-- Weitere Zeit hinzufügen (mobile) -->
                            <div class="mt-3 sm:hidden">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-xs text-sm/5 font-bold text-text-subtle hover:text-accent-700 transition-colors"
                                    @click="addIndividualTime"
                                >
                                    <PropertyIcon name="IconCirclePlus"
                                        class="h-5 w-5"
                                        stroke-width="2"
                                    />
                                    <span>{{ t('Add individual time') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Noch keine Zeiten -->
                        <div
                            v-else
                            class="cursor-pointer"
                            @click="addIndividualTime"
                        >
                            <div class="w-full px-3 py-4 bg-accent-50 hover:bg-accent-100 border border-dashed border-accent-200 rounded-lg mt-1 transition-colors">
                                <AlertComponent
                                    text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen"
                                    show-icon
                                    icon-size="h-4 w-4"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Regelverstöße -->
                <section v-if="(can('can plan shifts') || hasAdminRole()) && user.type === 0" class="space-y-3 mt-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                {{ t('Rule violations') }}
                            </h3>
                            <p class="text-[11px] text-text-subtle mt-0.5">
                                {{ t('Rule violations for this person on this day.') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="hidden sm:inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2.5 py-1 text-[11px] text-text-muted hover:border-accent-700 hover:text-accent-700 transition-colors"
                            @click="showAddViolationModal = true"
                        >
                            <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                            <span>{{ t('Add rule violation') }}</span>
                        </button>
                    </div>

                    <div v-if="violationsForDay.length" class="space-y-2">
                        <div
                            v-for="violation in violationsForDay"
                            :key="violation.id"
                            class="flex items-center justify-between rounded-lg border border-border-subtle bg-white px-3 py-2 cursor-pointer hover:bg-surface-sunken/80 transition-colors"
                            @click="openViolationEditModal(violation)"
                        >
                            <div class="flex items-center gap-2 text-xs text-text-muted">
                                <span
                                    class="inline-block h-2.5 w-2.5 rounded-full"
                                    :style="{ backgroundColor: violation.shift_rule?.warning_color || '#ff0000' }"
                                ></span>
                                <span class="font-medium">{{ violation.shift_rule?.name }}</span>
                                <span
                                    :class="violation.severity === 'error' ? 'bg-danger-surface text-danger' : 'bg-warning-surface text-warning'"
                                    class="inline-flex px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                                >
                                    {{ violation.severity === 'error' ? t('Error') : t('Warning') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span v-if="violation.is_manual" class="text-[10px] text-text-subtle">
                                    {{ t('Manual violation') }}
                                </span>
                                <span v-if="violation.compensation_days" class="text-[10px] text-success">
                                    {{ violation.compensation_days }} {{ t('Days') }}
                                </span>
                                <PropertyIcon name="IconChevronRight" class="h-3.5 w-3.5 text-text-subtle" />
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex items-center gap-2 rounded-xl border border-dashed border-border-subtle bg-surface-sunken/60 px-3 py-3 text-xs text-text-subtle"
                    >
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-border"></span>
                        <span>{{ t('No rule violations for this day.') }}</span>
                    </div>
                </section>

                <!-- Compensation day off info -->
                <section v-if="compensationDayForDate.length && (can('can plan shifts') || hasAdminRole())" class="mt-5">
                    <div
                        v-for="compDay in compensationDayForDate"
                        :key="compDay.id"
                        class="rounded-xl border border-special-teal-border bg-special-teal-surface px-4 py-3 mb-2"
                    >
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block h-2 w-2 rounded-full bg-special-teal"></span>
                            <span class="text-xs font-semibold text-special-teal">
                                {{ compDay.value >= 1.0 ? t('Compensation day off') : t('Half compensation day off') }}
                                <template v-if="compDay.half_day_period === 'morning' || compDay.half_day_period === 'afternoon'">
                                    ({{ compDay.half_day_period === 'morning' ? t('Morning') : t('Afternoon') }})
                                </template>
                            </span>
                        </div>
                        <div class="text-xs text-special-teal">
                            <span class="font-medium">{{ t('Compensation day off for:') }}</span>
                            {{ compDay.violation?.shift_rule?.name || t('Manual') }}
                        </div>
                        <div v-if="compDay.granted_by_user" class="text-xs text-special-teal mt-0.5">
                            <span class="font-medium">{{ t('Assigned by') }}:</span>
                            {{ compDay.granted_by_user.first_name }} {{ compDay.granted_by_user.last_name }}
                        </div>
                        <button
                            type="button"
                            class="mt-1 text-[11px] text-special-teal hover:text-special-teal underline"
                            @click="revokeCompensationDay(compDay.id)"
                        >
                            {{ t('Revoke') }}
                        </button>
                    </div>
                </section>

                <!-- Grant compensation day button -->
                <section
                    v-if="!compensationDayForDate.length && user.type === 0 && (can('can plan shifts') || hasAdminRole())"
                    class="mt-3"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full border border-special-teal-border bg-white px-2.5 py-1 text-[11px] text-special-teal hover:border-special-teal hover:text-special-teal transition-colors"
                        @click="showGrantCompensationModal = true"
                    >
                        <PropertyIcon name="IconCalendarPlus" class="h-3.5 w-3.5" stroke-width="2" />
                        <span>{{ t('Grant compensation day') }}</span>
                    </button>
                </section>

                <!-- Rechte Spalte: Availability + Kommentar -->
                <div class="space-y-6 mt-5">
                    <!-- Info: Availability locked by compensation day -->
                    <section
                        v-if="compensationDayForDate.length && (user.type === 0 || user.type === 1)"
                        class="rounded-xl border border-special-teal-border bg-special-teal-surface/60 px-3.5 py-3 text-xs text-special-teal"
                    >
                        {{ t('Availability cannot be changed — compensation day off granted.') }}
                    </section>

                    <!-- Verfügbarkeits-Typ (nur intern & extern) -->
                    <section
                        v-if="(user.type === 0 || user.type === 1) && !compensationDayForDate.length"
                        class="space-y-3 rounded-xl border border-border-subtle bg-surface-sunken/80 px-3.5 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Availability status for this day') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ canPlanShifts
                                        ? t('Define whether this person is available, off work or not available.')
                                        : t('The availability status is set by shift planning. Enter absences via your availability calendar.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Ohne Planungsrecht (eigene Zelle): Status nur lesend anzeigen -->
                        <div
                            v-if="!canPlanShifts"
                            class="mt-2 flex items-center gap-2 rounded-lg border border-border-subtle bg-white px-3 py-2.5 text-sm text-text-muted"
                        >
                            <span
                                class="inline-flex h-1.5 w-1.5 rounded-full"
                                :class="dotClassForChecked"
                            ></span>
                            <span class="truncate">{{ checked?.name }}</span>
                        </div>

                        <Listbox v-else as="div" v-model="checked" class="relative mt-2 w-full">
                            <ListboxButton class="menu-button flex items-center justify-between">
                                <div class="flex items-center gap-2 truncate">
                                    <span
                                        class="inline-flex h-1.5 w-1.5 rounded-full"
                                        :class="dotClassForChecked"
                                    ></span>
                                    <span class="truncate">
                                        {{ checked?.name }}
                                    </span>
                                </div>
                                <PropertyIcon name="IconChevronDown"
                                    class="h-5 w-5 text-text"
                                    aria-hidden="true"
                                />
                            </ListboxButton>
                            <ListboxOptions
                                class="absolute mt-1 w-full z-10 bg-surface-inverse shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto sm:text-sm"
                            >
                                <ListboxOption
                                    v-for="type in vacationTypes"
                                    :key="type.type"
                                    :value="type"
                                    v-slot="{ selected }"
                                    class="text-text-subtle cursor-pointer rounded-md p-2 mb-0.5 flex justify-between items-center"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <span
                                            class="inline-flex h-1.5 w-1.5 rounded-full"
                                            :class="dotClassForType(type)"
                                        ></span>
                                        <span
                                            :class="[
                                                selected ? 'text-sm/5 font-bold text-white' : 'text-sm/5 font-bold text-text-subtle',
                                                'truncate'
                                            ]"
                                        >
                                            {{ type.name }}
                                        </span>
                                    </div>
                                    <PropertyIcon name="IconCheck"
                                        v-if="selected"
                                        class="h-5 w-5 text-success"
                                        aria-hidden="true"
                                    />
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
                    </section>

                    <!-- DP-18: "Frei" als ganzer oder halber freier Tag (Vormittag/Nachmittag) -->
                    <section v-if="canPlanShifts && checked && checked.type === 'FREE_WORK'"
                             class="space-y-2 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                        <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ t('Free day type') }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="opt in freeDayPartOptions" :key="opt.value"
                                   class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm cursor-pointer"
                                   :class="freeDayPart === opt.value
                                       ? 'border-accent-600 text-accent-600 bg-accent-600/5'
                                       : 'border-border-subtle text-text-muted'">
                                <input type="radio" class="hidden" :value="opt.value" v-model="freeDayPart" />
                                {{ t(opt.label) }}
                            </label>
                        </div>
                    </section>

                    <!-- Kommentar -->
                    <section class="space-y-3 rounded-xl border border-border-subtle bg-white px-3.5 py-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Comment') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ t('Optional note for this day (visible in shift planning).') }}
                                </p>
                            </div>
                        </div>
                        <BaseTextarea
                            id="shift_comment"
                            v-model="shiftPlanComment.comment"
                            :label="t('Comment')"
                            :show-label="false"
                            no-margin-top
                        />
                    </section>

                    <!-- Registrierte Verfügbarkeiten -->
                    <section
                        v-if="user.availabilities"
                        class="space-y-3 rounded-xl border border-border-subtle bg-surface-sunken/70 px-3.5 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                                    {{ t('Registered availabilities') }}
                                </h3>
                                <p class="text-[11px] text-text-subtle mt-0.5">
                                    {{ t('Availabilities that this person has registered in the system.') }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="user.availabilities[day.fullDay]?.length"
                            class="space-y-2"
                        >
                            <div
                                v-for="availability in user.availabilities[day.fullDay]"
                                :key="availability.id ?? availability.date_casted + availability.start_time + availability.end_time"
                                class="rounded-lg border border-border-subtle bg-white px-3 py-2"
                            >
                                <div class="flex items-center justify-between text-xs text-text-muted">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex h-1.5 w-1.5 rounded-full bg-success"></span>
                                        <span>{{ availability.date_casted }}</span>
                                        <span v-if="!availability.full_day">
                                            · {{ availability.start_time }} – {{ availability.end_time }}
                                        </span>
                                        <span v-else class="text-[11px] text-success">
                                            ({{ t('Full day') }})
                                        </span>
                                    </div>
                                </div>
                                <p
                                    v-if="availability.comment"
                                    class="mt-1 text-[11px] text-text-subtle italic"
                                >
                                    &bdquo;{{ availability.comment }}&rdquo;
                                </p>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-[11px] text-text-subtle italic"
                        >
                            {{ t('No availabilities are registered for this day.') }}
                        </div>
                    </section>
                </div>
            </div>

            <!-- Footer: Speichern -->
            <div class="flex justify-between pt-2 border-t border-border-subtle mt-2">
                <BaseUIButton
                    :label="t('Cancel')"
                    is-cancel-button
                    @click="closeModal"
                />
                <BaseUIButton
                    :label="t('Save')"
                    is-add-button
                    @click="checkVacation"
                />
            </div>
        </div>

        <!-- Modale -->
        <ProjectAssignmentModal
            v-if="showProjectAssignmentModal"
            :worker-type="user.type"
            :worker-id="user.element.id"
            :worker-name="workerDisplayName"
            :mode="canPlanShifts ? 'binding' : 'wish'"
            :initial-days="[day.withoutFormat]"
            @close="handleProjectAssignmentModalClose"
        />

        <ConfirmDeleteModal
            v-if="showConfirmDeleteModal"
            :title="t('Delete user from shift')"
            :description="t('Are you sure you want to delete the user from this shift?')"
            @closed="closeConfirmDeleteModal"
            @delete="submitDeleteUserFromShift"
        />

        <RequestWorkTimeChangeModal
            v-if="showRequestWorkTimeChangeModal"
            :user="user.element"
            :shift="selectedShift"
            @close="showRequestWorkTimeChangeModal = false"
        />

        <IndividualTimeSeriesModal
            v-if="showSeriesModal"
            :is-in-shift-plan="true"
            :series-uuid="activeSeriesUuid"
            :initial-subject="activeSeriesSubject || currentSeriesSubject"
            @close="closeSeriesModal"
            @created="handleSeriesUpdated"
            @updated="handleSeriesUpdated"
        />

        <AddManualViolationModal
            v-if="showAddViolationModal"
            :user-id="user.element.id"
            :date="day.withoutFormat"
            :available-rules="availableRulesForUser"
            @close="showAddViolationModal = false"
            @created="handleViolationCreated"
        />

        <ViolationEditModal
            v-if="showViolationEditModal && selectedViolation"
            :violation="selectedViolation"
            :compensation-period="user.compensation_period || 0"
            @close="showViolationEditModal = false"
            @updated="handleViolationUpdated"
        />

        <GrantCompensationDayModal
            v-if="showGrantCompensationModal"
            :user-id="user.element.id"
            :preselected-date="day.withoutFormat"
            :user-name="user.element.first_name + ' ' + user.element.last_name"
            @close="showGrantCompensationModal = false"
            @granted="handleCompensationGranted"
        />

        <!-- Warnung: Person aus startenden Schichten entfernen / individuelle Zeiten löschen -->
        <ArtworkBaseModal
            v-if="showAvailabilityCleanupModal"
            :title="t('Change availability status')"
            :description="t('Should the person also be removed from all shifts on this day and their individual times deleted?')"
            @close="showAvailabilityCleanupModal = false"
        >
            <div class="mt-4 space-y-4">
                <div v-if="cleanupShifts.length">
                    <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase mb-2">
                        {{ t('Assigned shifts') }}
                    </h3>
                    <ul class="space-y-1.5">
                        <li
                            v-for="shift in cleanupShifts"
                            :key="`shift-${shift.pivot_id}`"
                            class="flex items-center gap-2 rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-2 text-xs text-text-muted"
                        >
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-danger"></span>
                            <span class="font-medium">{{ shift.start }} - {{ shift.end }}</span>
                            <span v-if="shift.event_name" class="text-text-subtle truncate">· {{ shift.event_name }}</span>
                            <span v-if="shift.craft_abbreviation" class="text-text-subtle">· {{ shift.craft_abbreviation }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="cleanupIndividualTimes.length">
                    <h3 class="text-xs font-semibold tracking-wide text-text-subtle uppercase mb-2">
                        {{ t('Individual times') }}
                    </h3>
                    <ul class="space-y-1.5">
                        <li
                            v-for="it in cleanupIndividualTimes"
                            :key="`it-${it.id}`"
                            class="flex items-center gap-2 rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-2 text-xs text-text-muted"
                        >
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                            <span v-if="it.title" class="font-medium truncate">{{ it.title }}</span>
                            <span v-if="it.start_time || it.end_time" class="text-text-subtle">
                                · {{ it.start_time }} - {{ it.end_time }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton type="button" variant="secondary" hide-icon @click="keepShiftsAndSetStatus">
                    {{ t('No, only change status') }}
                </BaseUIButton>
                <BaseUIButton
                    type="button"
                    variant="primary"
                    hide-icon
                    :disabled="cleanupProcessing"
                    @click="confirmCleanupAndSetStatus"
                >
                    {{ t('Yes, remove') }}
                </BaseUIButton>
            </div>
        </ArtworkBaseModal>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, defineAsyncComponent, onMounted, watch } from 'vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import AlertComponent from '@/Components/Alerts/AlertComponent.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import RequestWorkTimeChangeModal from '@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue';
import SingleShiftInShiftOverviewUser from '@/Pages/Shifts/Components/SingleShiftInShiftOverviewUser.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import IndividualTimeSeriesModal from '@/Pages/Shifts/Components/IndividualTimeSeriesModal.vue';
import AddManualViolationModal from '@/Pages/Shifts/Components/AddManualViolationModal.vue';
import ViolationEditModal from '@/Pages/Shifts/Components/ViolationEditModal.vue';
import GrantCompensationDayModal from '@/Pages/Shifts/Components/GrantCompensationDayModal.vue';
import { IconCirclePlus, IconTrash } from '@tabler/icons-vue';
import { useLegalBreak } from '@/Composeables/useLegalBreak';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { usePermission } from '@/Composeables/Permission.js';
import { colorForProjectId, formatAssignmentDate } from '@/Composeables/UseProjectDayAssignments.js';

const ProjectAssignmentModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ProjectAssignmentModal.vue'),
});

defineOptions({
    name: 'ShowUserShiftsModal',
});

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    day: {
        type: Object,
        required: true,
    },
    shiftQualifications: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['closed', 'delete', 'desiresReload', 'openHistory']);

const { t } = useI18n();
const page = usePage();
const { can, hasAdminRole } = usePermission(page.props);

// Verfügbarkeits-Typen
const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
    { name: 'Frei', type: 'FREE_WORK' },
]);

// DP-18: Ganzer/Halber freier Tag für den "Frei"-Status
const freeDayPart = ref('full');
const freeDayPartOptions = [
    { value: 'full', label: 'Full free day' },
    { value: 'morning', label: 'Half free day (morning)' },
    { value: 'afternoon', label: 'Half free day (afternoon)' },
];

const checked = ref(null);
const vacationTypeBeforeUpdate = ref(null);

// --- Projekte (Zuordnungen + Wünsche) ---
const showProjectAssignmentModal = ref(false);
const projectAssignmentActionKey = ref(null);
const projectAssignmentError = ref('');
// Lokale Kopie, damit die Liste nach Mutationen ohne Voll-Reload aktuell ist
const localAssignmentsForDay = ref(
    [...(props.user.project_assignments?.[props.day.withoutFormat] ?? [])],
);

const assignmentsForDay = computed(() => localAssignmentsForDay.value);

const canPlanShifts = computed(() => can('can plan shifts') || hasAdminRole());

const isOwnCell = computed(() =>
    props.user.type === 0 && props.user.element?.id === page.props.auth?.user?.id,
);

const workerDisplayName = computed(() => {
    const el = props.user.element ?? {};
    return el.name ?? el.provider_name ?? [el.first_name, el.last_name].filter(Boolean).join(' ');
});

const canDeleteAssignment = (assignment) =>
    canPlanShifts.value || (assignment.type === 'wish' && isOwnCell.value);

async function deleteAssignment(assignment, wholeGroup) {
    if (projectAssignmentActionKey.value !== null) return;
    projectAssignmentActionKey.value = `${wholeGroup ? 'delete-group' : 'delete-day'}:${assignment.id}`;
    projectAssignmentError.value = '';
    try {
        await axios.delete(route('project-day-assignments.destroy', { projectDayAssignment: assignment.id }), {
            params: { whole_group: wholeGroup },
        });

        localAssignmentsForDay.value = wholeGroup
            ? localAssignmentsForDay.value.filter(a => a.group_id !== assignment.group_id)
            : localAssignmentsForDay.value.filter(a => a.id !== assignment.id);

        emit('desiresReload');
    } catch (error) {
        projectAssignmentError.value = error?.response?.data?.message ?? String(error);
        await refreshAssignmentsForDay().catch(() => {});
    } finally {
        projectAssignmentActionKey.value = null;
    }
}

async function acceptAssignmentWish(assignment) {
    if (projectAssignmentActionKey.value !== null) return;
    projectAssignmentActionKey.value = `accept:${assignment.id}`;
    projectAssignmentError.value = '';
    try {
        await axios.patch(route('project-day-assignments.accept-wish', { projectDayAssignment: assignment.id }));

        localAssignmentsForDay.value = localAssignmentsForDay.value.map(a =>
            a.group_id === assignment.group_id ? { ...a, type: 'binding' } : a,
        );

        emit('desiresReload');
    } catch (error) {
        projectAssignmentError.value = error?.response?.data?.message ?? String(error);
        await refreshAssignmentsForDay().catch(() => {});
    } finally {
        projectAssignmentActionKey.value = null;
    }
}

async function refreshAssignmentsForDay() {
    const workerTypeName = props.user.type === 1
        ? 'freelancer'
        : props.user.type === 2 ? 'serviceProvider' : 'user';

    const { data } = await axios.get(route('shifts.worker.single'), {
        params: {
            worker_id: props.user.element.id,
            worker_type: workerTypeName,
            start_date: props.day.withoutFormat,
            end_date: props.day.withoutFormat,
        },
    });

    localAssignmentsForDay.value = [
        ...(data.worker?.project_assignments?.[props.day.withoutFormat] ?? []),
    ];
}

async function handleProjectAssignmentModalClose({ saved } = { saved: false }) {
    showProjectAssignmentModal.value = false;

    if (saved) {
        await refreshAssignmentsForDay();
        emit('desiresReload');
    }
}

// Kopie des ursprünglichen Zustands der individuellen Zeiten (zum Zurücksetzen bei Close)
const originalIndividualTimes = ref(
    props.user.individual_times ? JSON.parse(JSON.stringify(props.user.individual_times)) : [],
);

// Modale & Zustände
const showConfirmDeleteModal = ref(false);
const wantedShiftId = ref(null);
const wantedUserId = ref(null);

// Warnung beim Setzen von "Frei"/"Nicht Verfügbar" trotz aktiver Schichtzuweisung/Zeiten.
// Die Listen werden autoritativ vom Backend befüllt (nicht aus dem evtl. veralteten Frontend-State).
const showAvailabilityCleanupModal = ref(false);
const cleanupProcessing = ref(false);
const cleanupShifts = ref([]);
const cleanupIndividualTimes = ref([]);

const showRequestWorkTimeChangeModal = ref(false);
const selectedShift = ref(null);

const showSeriesModal = ref(false);
const activeSeriesUuid = ref(null);
const activeSeriesSubject = ref(null);

// Edit-Modus-Flag (bisher nicht verwendet, aber Logik bleibt erhalten)
const editMode = ref(false);

// Violations
const showAddViolationModal = ref(false);
const showViolationEditModal = ref(false);
const selectedViolation = ref(null);

// Compensation day offs
const showGrantCompensationModal = ref(false);

const compensationDayForDate = computed(() => {
    const dayOffs = props.user?.compensation_day_offs?.[props.day.withoutFormat];
    if (!dayOffs) return [];
    return Array.isArray(dayOffs) ? dayOffs : Object.values(dayOffs);
});

const violationsForDay = computed(() => {
    const violations = props.user?.violations?.[props.day.withoutFormat];
    if (!violations) return [];
    return Array.isArray(violations) ? violations : Object.values(violations);
});

const availableRulesForUser = ref([]);

function openViolationEditModal(violation) {
    selectedViolation.value = violation;
    showViolationEditModal.value = true;
}

function handleViolationCreated() {
    emit('desiresReload');
}

function handleViolationUpdated() {
    showViolationEditModal.value = false;
    selectedViolation.value = null;
    emit('desiresReload');
}

function handleCompensationGranted() {
    showGrantCompensationModal.value = false;
    emit('desiresReload');
}

// Kommentar
const shiftPlanComment = ref(
    props.user.shift_comments?.[props.day.withoutFormat]?.[0] ?? {
        comment: '',
        date: props.day.withoutFormat,
    },
);

// Track manual edits for break_minutes per individual time
const manualBreakEdits = ref(new Map());

// Function to mark break time as manually edited
function markBreakAsManuallyEdited(individualTime) {
    const timeKey = individualTime.id || `${individualTime.start_date}-${individualTime.start_time}-${individualTime.end_time}`;
    manualBreakEdits.value.set(timeKey, true);
}

// IndividualTimes gefiltert nach Tag
const individualTimesByDate = computed(() => {
    return (props.user.individual_times || []).filter((individual_time) =>
        individual_time.days_of_individual_time?.includes(props.day.withoutFormat),
    );
});

// Watch each individual time for start/end time changes to auto-calculate break
watch(
    () => props.user.individual_times?.map(it => ({
        id: it.id,
        start_time: it.start_time,
        end_time: it.end_time,
        start_date: it.start_date
    })),
    (newTimes, oldTimes) => {
        if (!newTimes || !props.user.individual_times) return;

        newTimes.forEach((newTime, index) => {
            const oldTime = oldTimes?.[index];
            const individualTime = props.user.individual_times[index];

            if (!individualTime) return;

            const timeKey = individualTime.id || `${individualTime.start_date}-${individualTime.start_time}-${individualTime.end_time}`;

            // Check if start_time or end_time actually changed
            const timesChanged = oldTime && (
                newTime.start_time !== oldTime.start_time ||
                newTime.end_time !== oldTime.end_time
            );

            // Only recalculate break_minutes when times actually changed
            if (timesChanged) {
                // Reset manual edit flag when times change
                manualBreakEdits.value.delete(timeKey);

                // Auto-calculate break_minutes if times are set
                if (individualTime.start_time && individualTime.end_time) {
                    const startRef = computed(() => individualTime.start_time);
                    const endRef = computed(() => individualTime.end_time);
                    const { breakMinutes } = useLegalBreak(startRef, endRef);

                    if (breakMinutes.value !== individualTime.break_minutes) {
                        individualTime.break_minutes = breakMinutes.value;
                    }
                }
            }
        });
    },
    { deep: true }
);

// Subject für Serien-Modal (Person)
const currentSeriesSubject = computed(() => {
    const element = props.user.element;
    return {
        id: element.id,
        type: element.type,
        display_name: element.provider_name
            ? element.provider_name
            : `${element.first_name} ${element.last_name}`,
    };
});

// Badge-Klasse für Typ
const badgeClassForType = computed(() => {
    if (props.user.element.type === 'service_provider') {
        return 'bg-special-violet-surface text-special-violet border-special-violet-border';
    }
    if (props.user.element.type === 'freelancer') {
        return 'bg-warning-surface text-warning border-warning-border';
    }
    return 'bg-success-surface text-success border-success-border';
});

// Dot-Farbe für aktuellen Availability-Status
const dotClassForChecked = computed(() => {
    if (!checked.value) return 'bg-border';
    if (checked.value.type === 'AVAILABLE') return 'bg-success';
    if (checked.value.type === 'OFF_WORK') return 'bg-warning';
    if (checked.value.type === 'NOT_AVAILABLE') return 'bg-danger';
    return 'bg-border';
});

function dotClassForType(type) {
    if (type.type === 'AVAILABLE') return 'bg-success';
    if (type.type === 'OFF_WORK') return 'bg-warning';
    if (type.type === 'NOT_AVAILABLE') return 'bg-danger';
    return 'bg-border';
}

// Initialer Vacation-Type
onMounted(async () => {
    const vacation = props.user.vacations?.find(
        (v) => v.date === props.day.withoutFormat,
    );

    if (vacation) {
        const vacationType = vacationTypes.value.find(
            (type) => type.type === vacation.type,
        );
        checked.value = vacationType ?? vacationTypes.value[0];
        vacationTypeBeforeUpdate.value = vacationType ?? vacationTypes.value[0];
        freeDayPart.value = vacation.day_part ?? 'full';
    } else {
        checked.value = vacationTypes.value[0];
        vacationTypeBeforeUpdate.value = vacationTypes.value[0];
        freeDayPart.value = 'full';
    }

    // Load active rules for manual violation creation
    if (can('can plan shifts') || hasAdminRole()) {
        try {
            const response = await axios.get(route('shift-rules.active'));
            availableRulesForUser.value = response.data ?? [];
        } catch {
            // silently ignore
        }
    }
});

// Methoden
function openSeriesModal(individualTime) {
    activeSeriesUuid.value = individualTime.series_uuid;
    activeSeriesSubject.value = currentSeriesSubject.value;
    showSeriesModal.value = true;
}

function closeSeriesModal() {
    showSeriesModal.value = false;
    activeSeriesUuid.value = null;
    activeSeriesSubject.value = null;

    closeModal();
}

function handleSeriesUpdated() {
    emit('desiresReload');
    closeSeriesModal();
}

function removeIndividualTimeFromModal(individualTimeId) {
    props.user.individual_times = (props.user.individual_times || []).filter(
        (individualTime) => individualTime.id !== individualTimeId,
    );
    originalIndividualTimes.value = originalIndividualTimes.value.filter(
        (individualTime) => individualTime.id !== individualTimeId,
    );
}

async function deleteIndividualTimeById(individualTime) {
    const individualTimeId = individualTime?.id;

    if (!individualTimeId) {
        return;
    }

    try {
        await axios.delete(route('delete.individualTimes', { individualTime: individualTimeId }));
        removeIndividualTimeFromModal(individualTimeId);
        emit('desiresReload');
    } catch {
        // Keep the current modal state if the delete request fails.
    }
}

const shiftsForDay = computed(() => {
    return (props.user.element.shifts || []).filter((shift) =>
        shift.days_of_shift?.includes(props.day.fullDay),
    );
});

// "Frei"/"Nicht Verfügbar" sind die Status, die eine Bereinigung erfordern können.
function isBlockingAvailabilityStatus() {
    return checked.value?.type === 'FREE_WORK' || checked.value?.type === 'NOT_AVAILABLE';
}

function addIndividualTime() {
    if (!props.user.individual_times) {
        props.user.individual_times = [];
    }

    props.user.individual_times.push({
        id: null,
        title: '',
        start_time: '',
        end_time: '',
        start_date: props.day.withoutFormat,
        break_minutes: 0,
        days_of_individual_time: [props.day.withoutFormat],
    });
}

function closeModal(bool) {
    // Zustand auf ursprüngliche IndividualTimes zurücksetzen
    props.user.individual_times = JSON.parse(JSON.stringify(originalIndividualTimes.value));
    emit('closed', bool);
}

// Shortcut: Schichtverlauf mit dieser Person und diesem Tag vorausgewählt öffnen.
// Das Verlauf-Modal wird vom Parent über dem Tagesmodal geöffnet.
function openHistory() {
    const el = props.user.element;
    const name = el.type === 'service_provider'
        ? el.provider_name
        : (el.full_name ?? `${el.first_name ?? ''} ${el.last_name ?? ''}`.trim());
    emit('openHistory', { search: name ?? '', date: props.day.withoutFormat });
}

function removeUserFromShift(shiftId, usersPivotId) {
    router.delete(
        route('shift.removeUserByType', {
            usersPivotId,
            userType: props.user.type,
        }),
        {
            data: {
                removeFromSingleShift: true,
            },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                document.getElementById('shift-' + shiftId)?.remove();
            },
            onFinish: () => {
                document.getElementById('shift-' + shiftId)?.remove();
            },
        },
    );
}

function submitDeleteUserFromShift() {
    removeUserFromShift(wantedShiftId.value, wantedUserId.value);
    closeConfirmDeleteModal();
}

function openConfirmDeleteModal(shiftId, usersPivotId) {
    wantedShiftId.value = shiftId;
    wantedUserId.value = usersPivotId;
    showConfirmDeleteModal.value = true;
}

function closeConfirmDeleteModal() {
    showConfirmDeleteModal.value = false;
}

function handleShiftDeleted(deletedShiftId) {
    // Lokale Shifts des Users aktualisieren, um stale Pivots zu vermeiden
    props.user.element.shifts = (props.user.element.shifts || []).filter(
        (shift) => shift.id !== deletedShiftId,
    );
}

function sendIndividualTimes() {

    // nur zeiten die wirklich verändert wurden senden
    const individualTimesWhereAreEdited = (props.user.individual_times || []).filter(
        (individualTime) => {
            const original = originalIndividualTimes.value.find(
                (orig) => orig.id === individualTime.id,
            );
            return JSON.stringify(original) !== JSON.stringify(individualTime);
        },
    );


    axios
        .post(route('add.update.individualTimesAndShiftPlanComment'), {
            modelId: props.user.element.id,
            modelType: props.user.type,
            individualTimes: individualTimesWhereAreEdited,
            shift_comment: shiftPlanComment.value,
        })
        .then((response) => {
            if (response.data.individual_times) {
                originalIndividualTimes.value = response.data.individual_times;
                props.user.individual_times = response.data.individual_times;
            }

            if (response.data.shift_comment) {
                shiftPlanComment.value = response.data.shift_comment;
            }

            sendCheckVacation();
        })
        .catch(() => {
            // Fehler-Fall ggf. später behandeln
        });
}

function revokeCompensationDay(id) {
    router.post(route('compensation-day-offs.revoke', { compensationDayOff: id }), {}, {
        preserveScroll: true,
        onSuccess: () => emit('desiresReload'),
    });
}

function sendCheckVacation() {
    // Ohne Planungsrecht (eigene Zelle) ist der Status nur lesend — nie mitsenden,
    // sonst würde schon ein Kommentar-Speichern am Backend-Gate (403) scheitern.
    if (!canPlanShifts.value) {
        closeModal(true);
        return;
    }

    if (compensationDayForDate.value.length > 0) {
        closeModal(true);
        return;
    }

    if (props.user.type === 0) {
        router.patch(
            route('user.check.vacation', { user: props.user.element.id }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
                dayPart: checked.value?.type === 'FREE_WORK' ? freeDayPart.value : null,
                // Schicht-Entfernung wird ausschließlich über die Rückfrage gesteuert,
                // daher hier nie automatisch detachen.
                remove_from_shifts: false,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Worker-Zeile (vacations) und damit die "Eingeplant, aber nicht
                    // verfügbar"-Hervorhebung sofort aktualisieren
                    emit('desiresReload');
                    closeModal(true);
                },
            },
        );
    } else if (props.user.type === 2) {
        router.patch(
            route('service_provider.check.vacation', {
                service_provider: props.user.element.id,
            }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
                dayPart: checked.value?.type === 'FREE_WORK' ? freeDayPart.value : null,
                // Schicht-Entfernung wird ausschließlich über die Rückfrage gesteuert,
                // daher hier nie automatisch detachen.
                remove_from_shifts: false,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Worker-Zeile (vacations) und damit die "Eingeplant, aber nicht
                    // verfügbar"-Hervorhebung sofort aktualisieren
                    emit('desiresReload');
                    closeModal(true);
                },
            },
        );
    } else {
        router.patch(
            route('freelancer.check.vacation', {
                freelancer: props.user.element.id,
            }),
            {
                checked: checked.value,
                day: props.day.fullDay,
                vacationTypeBeforeUpdate: vacationTypeBeforeUpdate.value,
                dayPart: checked.value?.type === 'FREE_WORK' ? freeDayPart.value : null,
                // Schicht-Entfernung wird ausschließlich über die Rückfrage gesteuert,
                // daher hier nie automatisch detachen.
                remove_from_shifts: false,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Worker-Zeile (vacations) und damit die "Eingeplant, aber nicht
                    // verfügbar"-Hervorhebung sofort aktualisieren
                    emit('desiresReload');
                    closeModal(true);
                },
            },
        );
    }
}

function checkVacation() {
    // Bisherige Errors leeren
    for (const individualTime of props.user.individual_times || []) {
        delete individualTime.error;
    }

    // Serien-Detach nur bei EditMode (aktuell false, Verhaltens-Änderung vermeiden)
    for (const individualTime of props.user.individual_times || []) {
        if (individualTime.series_uuid && editMode.value) {
            individualTime.series_uuid = null;
        }
        delete individualTime.error;
    }

    // Validierung
    for (const individualTime of props.user.individual_times || []) {
        if (individualTime.start_time && !individualTime.end_time) {
            individualTime.error = t('Please also enter an end time here.');
            return;
        }
        if (!individualTime.start_time && individualTime.end_time) {
            individualTime.error = t('Please also enter a start time here.');
            return;
        }
    }

    // Bei "Frei"/"Nicht Verfügbar" autoritativ im Backend prüfen, ob an dem Tag aktive
    // Schichtzuweisungen oder individuelle Zeiten existieren. Falls ja -> Rückfrage.
    // Ohne Planungsrecht ist der Status lesend — die Rückfrage (inkl. Schicht-Entfernung)
    // darf dann nie erscheinen.
    if (canPlanShifts.value && isBlockingAvailabilityStatus()) {
        cleanupProcessing.value = true;
        axios
            .get(route('shift.dayAssignments'), {
                params: {
                    model_type: props.user.type,
                    model_id: props.user.element.id,
                    date: props.day.withoutFormat,
                },
            })
            .then(({ data }) => {
                cleanupProcessing.value = false;
                cleanupShifts.value = data.shifts ?? [];
                cleanupIndividualTimes.value = data.individual_times ?? [];

                if (cleanupShifts.value.length || cleanupIndividualTimes.value.length) {
                    showAvailabilityCleanupModal.value = true;
                } else {
                    sendIndividualTimes();
                }
            })
            .catch(() => {
                // Prüfung fehlgeschlagen: Status trotzdem setzen statt zu blockieren.
                cleanupProcessing.value = false;
                sendIndividualTimes();
            });
        return;
    }

    sendIndividualTimes();
}

// "Nein, nur Status ändern": Schichten/Zeiten bleiben erhalten, regulärer Speicherablauf.
function keepShiftsAndSetStatus() {
    showAvailabilityCleanupModal.value = false;
    sendIndividualTimes();
}

// "Ja, entfernen": Person aus den startenden Schichten entfernen und die startenden
// individuellen Zeiten löschen, danach den Status setzen.
function confirmCleanupAndSetStatus() {
    cleanupProcessing.value = true;

    const shiftPivotIds = cleanupShifts.value
        .map((shift) => shift.pivot_id)
        .filter((id) => id != null);
    const individualTimeIds = cleanupIndividualTimes.value
        .map((it) => it.id)
        .filter((id) => id != null);

    axios
        .post(route('shift.removeWorkerFromDay'), {
            model_type: props.user.type,
            model_id: props.user.element.id,
            shift_pivot_ids: shiftPivotIds,
            individual_time_ids: individualTimeIds,
        })
        .then(() => {
            // Lokalen Zustand bereinigen, damit das Zurücksetzen beim Schließen die gelöschten
            // Zeiten nicht wiederherstellt.
            const deletedIds = new Set(individualTimeIds);
            props.user.individual_times = (props.user.individual_times || []).filter(
                (it) => !deletedIds.has(it.id),
            );
            originalIndividualTimes.value = JSON.parse(
                JSON.stringify(props.user.individual_times),
            );
            props.user.element.shifts = (props.user.element.shifts || []).filter(
                (shift) => !shiftPivotIds.includes(shift.pivotId),
            );

            showAvailabilityCleanupModal.value = false;
            cleanupProcessing.value = false;
            sendCheckVacation();
        })
        .catch(() => {
            cleanupProcessing.value = false;
        });
}

function openRequestWorkTimeChangeModal(shift) {
    selectedShift.value = shift;
    showRequestWorkTimeChangeModal.value = true;
}
</script>
