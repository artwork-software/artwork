<template>
    <UserEditHeader :user_to_edit="userToEdit">

        <!-- Zeiteingabe und Balkenanzeige -->
        <div class="flex items-center justify-between mb-4">
            <div class="">
                <h2 class="text-lg font-semibold mb-2">{{ $t('Work Times') }}</h2>
                <p class="text-sm text-text-muted">{{ $t('Overview of work times for the user') }}</p>
            </div>

            <div>
                <BaseUIButton :label="$t('Book working hours')" is-add-button :icon="IconAlarmPlus" @click="showWorkingTimePostEntryModal = true" />
            </div>
        </div>
        <div class="flex items-center justify-between mb-5">
            <DateRangeControl
                :date-value-array="[dateRange.start, dateRange.end]"
                mode="work-times"
            />

            <WorkTimeTimerComponent :totals="totals" />
        </div>

        <!-- Vergangene Tage, deren Buchung von der aktuellen Rechnung abweicht (nie gebucht oder nachträglich geändert) -->
        <div v-if="totals.rebook_days > 0" class="mb-5 flex flex-col gap-2 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning md:flex-row md:items-center md:justify-between">
            <p class="flex items-start gap-1.5">
                <PropertyIcon name="IconAlertTriangle" class="size-4 shrink-0" />
                {{ $t('{n} past day(s) in this period differ from the time account: never booked (e.g. work time pattern created later) or changed afterwards (e.g. sick note, shift time). Rebooking changes the time account by {diff}.', { n: totals.rebook_days, diff: totals.rebook_difference_signed }) }}
            </p>
            <BaseUIButton v-if="canRebook && totals.rebook_days <= maxRebookDays" :label="$t('Rebook all {n} days', { n: totals.rebook_days })" :use-translation="false" icon="IconRefresh" :disabled="rebooking" @click="askRebook(totals.rebook_dates)" />
            <span v-else-if="canRebook" class="shrink-0">{{ $t('At most {n} days can be rebooked at once – please narrow the period.', { n: maxRebookDays }) }}</span>
        </div>

        <!-- Doppelte Tageszeilen (Altdaten): im Zeitkonto enthalten, „Neu buchen“ korrigiert sie nicht -->
        <p v-if="totals.duplicate_daily_booking_days > 0" class="mb-5 flex items-start gap-1.5 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
            <PropertyIcon name="IconAlertTriangle" class="size-4 shrink-0" />
            {{ $t('{n} past day(s) in this period have more than one daily booking (legacy data). They are included in the time account with {diff} in total; rebooking does not correct this – please have them cleaned up.', { n: totals.duplicate_daily_booking_days, diff: totals.duplicate_daily_booking_signed }) }}
        </p>

        <!-- Monthly breakdown when range > 1 month -->
        <div v-if="isMultiMonth" class="mb-6 p-4 bg-surface-sunken rounded-lg border border-border-subtle">
            <h3 class="text-sm font-semibold text-text-muted mb-3">{{ $t('Monthly Breakdown') }}</h3>
            <div class="space-y-2">
                <div v-for="(month, index) in monthlyBreakdown" :key="index" class="flex items-center justify-between text-sm">
                    <span class="font-medium text-text-muted">{{ month.name }}</span>
                    <span class="text-text">
                        <span class="font-semibold">{{ month.worked }}</span>
                        <span class="text-text-subtle mx-1">-</span>
                        <span class="font-semibold">{{ month.wanted }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-12">


            <!-- Kalenderwochen -->
            <div v-for="(week, weekKey) in workTimes" :key="weekKey">
                <h2 class="text-lg font-semibold text-text mb-3 border-b border-border border-dashed pb-1 font-lexend">
                    {{ weekKey }}
                    <span class="text-sm font-normal text-text-muted ml-3">
                        ({{ $t('Total') }}: {{ weeklySums[weekKey].worked }} - {{ weeklySums[weekKey].wanted }})
                    </span>
                </h2>

                <div class="divide-y divide-border-subtle">
                    <div
                        v-for="entry in Object.values(week)"
                        :key="entry.date"
                        class="py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <!-- Linke Spalte -->
                        <div>
                            <div class="text-sm font-lexend font-medium text-text">{{ entry.formatted_date }}</div>


                            <div class="font-lexend my-3" v-for="comment in entry.comments" :key="comment.id">
                                <div class="flex items-center gap-2 mb-1">
                                    <UserPopoverTooltip :user="comment.user" class="text-xs text-text-subtle" width="9" height="9" />
                                    <div>
                                        <div class="text-xs text-text">{{ comment.text }}</div>
                                        <div class="text-xs text-text-subtle">{{ comment.work_time_change }} Std.</div>
                                        <div class="text-[9px] text-text-subtle">{{ comment.date }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="entry.is_special_day" class="text-xs text-warning bg-warning-surface border border-warning-border px-2 py-0.5 rounded inline-flex items-center gap-1 mt-2">
                                {{ $t('Special Day') }}<template v-if="entry.special_day_name">: {{ entry.special_day_name }}</template>
                            </div>
                            <div v-if="entry.is_sick" class="text-xs text-text-muted bg-surface-sunken border border-border-subtle px-2 py-0.5 rounded inline-block mt-2 ml-1">
                                {{ $t('Sick') }}
                            </div>
                            <div v-if="entry.is_vacation" class="text-xs text-text-muted bg-surface-sunken border border-border-subtle px-2 py-0.5 rounded inline-block mt-2 ml-1">
                                {{ $t('Vacation') }}<template v-if="entry.vacation_factor < 1"> ({{ $t('Half day') }})</template>
                            </div>
                            <div v-if="entry.has_duplicate_daily_booking" class="text-xs text-warning bg-warning-surface border border-warning-border px-2 py-0.5 rounded inline-flex items-center gap-1 mt-2 ml-1">
                                {{ $t('Booked twice') }}
                                <ToolTipComponent
                                    icon="IconInfoCircle"
                                    icon-size="w-3.5 h-3.5"
                                    :tooltip-text="$t('This day has more than one daily booking (legacy data). The additional booking ({diff}) is included in the time account and in the values shown; rebooking does not correct it.', { diff: entry.duplicate_daily_booking_signed })"
                                    direction="top"
                                    classes="text-warning"
                                />
                            </div>
                            <div v-if="entry.needs_rebooking" class="text-xs text-warning bg-warning-surface border border-warning-border px-2 py-0.5 rounded inline-flex items-center gap-1 mt-2 ml-1">
                                {{ entry.rebook_reason === 'not_booked' ? $t('Not in time account') : $t('Differs from time account') }}
                                <ToolTipComponent
                                    icon="IconInfoCircle"
                                    icon-size="w-3.5 h-3.5"
                                    :tooltip-text="rebookTooltip(entry)"
                                    direction="top"
                                    classes="text-warning"
                                />
                                <button v-if="canRebook" type="button" class="ml-1 font-semibold underline disabled:opacity-50" :disabled="rebooking" @click="askRebook([entry.date])">
                                    {{ $t('Rebook') }}
                                </button>
                            </div>
                            <div v-for="(payout, idx) in entry.payouts" :key="'payout-' + idx" class="text-xs text-text-muted bg-surface-sunken border border-border-subtle px-2 py-0.5 rounded inline-block mt-2 ml-1">
                                {{ $t('Overtime paid out') }}: {{ payout.formatted }}<template v-if="payout.comment"> – {{ payout.comment }}</template>
                            </div>
                            <div v-if="entry.is_compensation_day_off" class="text-xs text-special-teal bg-special-teal-surface px-2 py-0.5 rounded inline-block mt-2">
                                <span v-for="(comp, idx) in entry.compensation_day_off_info" :key="idx">
                                    {{ comp.value >= 1 ? $t('Compensation day off') : $t('Half compensation day off') }}
                                    <template v-if="comp.rule_name"> — {{ comp.rule_name }}</template>
                                    <template v-if="comp.granted_by"> — {{ $t('Granted by') }}: {{ comp.granted_by }}</template>
                                    <template v-if="idx < entry.compensation_day_off_info.length - 1">, </template>
                                </span>
                            </div>
                        </div>
                        <!-- Rechte Spalte -->
                        <div class="w-full md:w-2/3">
                            <div class="relative h-4 bg-surface-sunken rounded overflow-hidden mb-1">
                                <!-- Worked hours (blue) - up to daily target -->
                                <div
                                    v-if="entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-accent-600"
                                    :style="{ width: `${entry.worked_hours > Math.abs(entry.daily_target_minutes) ?
                                        (Math.abs(entry.daily_target_minutes) / entry.worked_hours) * 100 :
                                        (entry.worked_hours / Math.abs(entry.daily_target_minutes)) * 100}%` }"
                                ></div>
                                <!-- Planned hours (gray) - up to daily target when no worked hours -->
                                <div
                                    v-if="entry.planned_minutes > 0 && !entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-border"
                                    :style="{
                                        width: `${Math.min((Math.abs(entry.daily_target_minutes) / Math.abs(entry.daily_target_minutes)) * 100, 100)}%`
                                    }"
                                ></div>
                                <!-- Missing hours (red-gray striped) - when planned < required and no worked hours (only for current or future days) -->
                                <div
                                    v-if="entry.planned_minutes < Math.abs(entry.daily_target_minutes) && !entry.worked_hours && !isDateInPast(entry.date)"
                                    class="absolute top-0 left-0 h-full bg-striped-red-gray"
                                    :style="{
                                        left: `${(entry.planned_minutes / Math.abs(entry.daily_target_minutes)) * 100}%`,
                                        width: `${((Math.abs(entry.daily_target_minutes) - entry.planned_minutes) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>
                                <!-- Overplanned hours (green-gray striped) - when planned > required and no worked hours -->
                                <div
                                    v-if="entry.planned_minutes > Math.abs(entry.daily_target_minutes) && !entry.worked_hours"
                                    class="absolute top-0 h-full bg-striped-green-gray"
                                    :style="{
                                        left: `${(Math.abs(entry.daily_target_minutes) / entry.planned_minutes) * 100}%`,
                                        width: `${((entry.planned_minutes - Math.abs(entry.daily_target_minutes)) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>

                                <!-- Overtime (dark green) - when worked > required -->
                                <div
                                    v-if="entry.worked_hours > Math.abs(entry.daily_target_minutes)"
                                    class="absolute top-0 h-full bg-success"
                                    :style="{
                                        left: `${Math.min((Math.abs(entry.daily_target_minutes) / entry.worked_hours) * 100, 100)}%`,
                                        width: `${((entry.worked_hours - Math.abs(entry.daily_target_minutes)) / entry.worked_hours) * 100}%`
                                    }"
                                ></div>

                                <!-- Undertime (red) - when worked < required or when it's a past day with no worked hours -->
                                <div
                                    v-if="(entry.worked_hours && entry.worked_hours < Math.abs(entry.daily_target_minutes)) ||
                                         (isDateInPast(entry.date) && !entry.worked_hours)"
                                    class="absolute top-0 left-0 h-full bg-danger"
                                    :style="{
                                        left: `${(entry.worked_hours ? entry.worked_hours : 0) / Math.abs(entry.daily_target_minutes) * 100}%`,
                                        width: `${((Math.abs(entry.daily_target_minutes) - (entry.worked_hours ? entry.worked_hours : 0)) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs text-text-muted mt-1">
                                <div class="flex items-center gap-1">
                                    <strong>{{ $t('Daily target') }}: </strong>{{ entry.daily_target_hours }}h
                                    <span v-if="entry.is_compensation_day_off" class="text-special-teal text-[10px] ml-1">({{ $t('Compensation day off') }})</span>
                                    <ToolTipComponent
                                        v-if="reductionTooltip(entry)"
                                        icon="IconInfoCircle"
                                        icon-size="w-3.5 h-3.5"
                                        :tooltip-text="reductionTooltip(entry)"
                                        direction="top"
                                        :classes="entry.reduction_reason === 'special_day' ? 'text-warning' : 'text-text-subtle'"
                                    />
                                </div>
                                <div><strong>{{ $t('Planned') }}: </strong>
                                    <span v-if="!entry.worked_hours">{{ entry.planned_hours }}h</span>
                                    <span v-else>{{ entry.worked_hours_formatted }}h</span>
                                </div>
                                <div v-if="entry.worked_hours"><strong>{{ $t('Worked') }}: </strong>{{ entry.worked_hours_formatted }}</div>
                                <div v-if="entry.nightly_working_hours"><strong>{{ $t('Night') }}: </strong>{{ entry.nightly_working_hours_formatted }}</div>
                                <div><strong>{{ $t('Balance') }}: </strong>
                                    <span :class="[ entry.work_time_balance_change > 0 ? 'text-success' : entry.work_time_balance_change < 0 ? 'text-danger' : 'text-text-subtle']">
                                        {{ entry.work_time_balance_change_formatted }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <ConfirmationComponent
            v-if="pendingRebookDates.length"
            :confirm="$t('Rebook')"
            :titel="$t('Rebook days')"
            :description="$t('{n} day(s) will be booked to the time account according to the current calculation (shifts, absences, work time pattern). Existing daily bookings of these days are replaced; manual bookings remain.', { n: pendingRebookDates.length })"
            @closed="afterRebookConfirm"
        />

        <WorkingTimePostEntryModal
            v-if="showWorkingTimePostEntryModal"
            :user="userToEdit"
            @close="showWorkingTimePostEntryModal = false"
        />

    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import WorkingTimePostEntryModal from "@/Pages/Users/Components/WorkingTimePostEntryModal.vue";
import {ref, computed} from "vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import WorkTimeTimerComponent from "@/Pages/Users/Components/WorkTimeTimerComponent.vue";
import {IconAlarmPlus} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import DateRangeControl from "@/Artwork/DateRange/DateRangeControl.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {router} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation();

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTimes: {
        type: Object,
        required: true
    },
    dateRange: {
        type: Object,
        required: true
    },
    totals: {
        type: Object,
        required: true
    },
    canRebook: {
        type: Boolean,
        default: false
    },
    maxRebookDays: {
        type: Number,
        default: 366
    }
})

const showWorkingTimePostEntryModal = ref(false)

// Function to check if a date is in the past
const isDateInPast = (dateString) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Set to beginning of today
    const checkDate = new Date(dateString);
    return checkDate < today;
}

// Check if date range spans more than 1 month
const isMultiMonth = computed(() => {
    const start = new Date(props.dateRange.start);
    const end = new Date(props.dateRange.end);

    // Calculate difference in months
    const monthsDiff = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());

    return monthsDiff > 0;
});

// Calculate monthly breakdown
const monthlyBreakdown = computed(() => {
    if (!isMultiMonth.value) {
        return [];
    }

    const months = {};

    // Iterate through all weeks and days
    Object.values(props.workTimes).forEach(week => {
        Object.values(week).forEach(entry => {
            const date = new Date(entry.date);
            const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            const monthName = date.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' });

            if (!months[monthKey]) {
                months[monthKey] = {
                    name: monthName,
                    worked: 0,
                    wanted: 0,
                    unknown: false
                };
            }

            months[monthKey].worked += entry.worked_hours || 0;
            months[monthKey].wanted += entry.daily_target_minutes || 0;
            months[monthKey].unknown = months[monthKey].unknown || !!entry.target_unknown;
        });
    });

    // Convert to array and format hours
    return Object.values(months).map(month => ({
        name: month.name,
        worked: convertMinutesToHoursAndMinutes(month.worked),
        wanted: month.unknown ? '–' : convertMinutesToHoursAndMinutes(month.wanted)
    }));
});

// Calculate weekly sums for each week
const weeklySums = computed(() => {
    const sums = {};

    Object.entries(props.workTimes).forEach(([weekKey, week]) => {
        let totalWorked = 0;
        let totalWanted = 0;
        let targetUnknown = false;

        Object.values(week).forEach(entry => {
            totalWorked += entry.worked_hours || 0;
            totalWanted += entry.daily_target_minutes || 0;
            targetUnknown = targetUnknown || !!entry.target_unknown;
        });

        // Ein Tag ohne Arbeitszeitmuster -> Wochen-Soll unbekannt (wie Gesamtkachel und Info-Modal)
        sums[weekKey] = {
            worked: convertMinutesToHoursAndMinutes(totalWorked),
            wanted: targetUnknown ? '–' : convertMinutesToHoursAndMinutes(totalWanted)
        };
    });

    return sums;
});

const weekdayName = (value) => {
    const date = new Date(value);
    return isNaN(date.getTime()) ? '' : date.toLocaleDateString('de-DE', { weekday: 'long' });
}

const formatDayMonth = (value) => {
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    return `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.`;
}

/**
 * Tooltip am Tagessoll: Minderungsgrund (Sondertag / Ersatzfreier Tag, ggf. Dreimonatsdurchschnitt),
 * Sondertag ohne Wirkung (Arbeit bzw. Regel inaktiv) sowie Krank/Urlaub (soll-neutral).
 */
const reductionTooltip = (entry) => {
    const parts = [];
    if (entry.is_special_day) {
        parts.push(`${$t('Special Day')}: ${entry.special_day_name ?? ''}`.trim());
        if (!entry.special_day_counts) {
            parts.push($t('Special day rule inactive for this contract – normal daily target'));
        } else if (!entry.reduction_reason) {
            parts.push($t('Work on special day – no target reduction'));
        }
    }
    if (entry.reduction_reason === 'compensation_day') {
        parts.push($t('Substitute day off'));
    }
    if (entry.reduction_reason && entry.target_reduction > 0) {
        let text = `${$t('Target')} −${entry.target_reduction_formatted} h`;
        if (entry.reference_period) {
            text += ` · Ø ${weekdayName(entry.date)} ${formatDayMonth(entry.reference_period.start)}–${formatDayMonth(entry.reference_period.end)}`;
        }
        parts.push(text);
    }
    if (entry.is_sick) {
        parts.push(`${$t('Sick')}: ${$t('actual = target')}`);
    }
    if (entry.is_vacation) {
        parts.push(`${$t('Vacation')}${entry.vacation_factor < 1 ? ` (${$t('Half day')})` : ''}: ${$t('actual = target')}`);
    }
    return parts.join(' · ');
}

// Abweichung zum Zeitkonto: nie gebucht (Anzeige = aktuelle Rechnung) bzw. nachträglich geändert (Anzeige = Gebuchtes)
const rebookTooltip = (entry) => {
    const reason = entry.rebook_reason === 'not_booked'
        ? $t('This day has no daily booking: the values shown are not included in the time account.')
        : $t('The current calculation differs from the booking (e.g. sick note, shift time or work time pattern changed afterwards). The values shown are the booked ones.')
    const manual = entry.manual_change_minutes
        ? ` ${$t('Manual bookings on this day: {diff}. Check whether they already cover the same work before rebooking.', { diff: entry.manual_change_signed })}`
        : ''
    return `${reason} ${$t('Rebooking changes the time account by {diff}.', { diff: entry.rebook_difference_signed })}${manual}`
}

const pendingRebookDates = ref([])
const rebooking = ref(false)

const askRebook = (dates) => {
    pendingRebookDates.value = [...dates]
}

const afterRebookConfirm = (confirmed) => {
    const dates = pendingRebookDates.value
    pendingRebookDates.value = []
    if (!confirmed || !dates.length) {
        return
    }
    rebooking.value = true
    router.post(route('users.worktimes.rebook', { user: props.userToEdit.id }), { dates }, {
        preserveScroll: true,
        onFinish: () => { rebooking.value = false },
    })
}

// Helper function to convert minutes to HH:MM format
const convertMinutesToHoursAndMinutes = (minutes) => {
    const hours = Math.floor(Math.abs(minutes) / 60);
    const mins = Math.abs(minutes) % 60;
    const sign = minutes < 0 ? '-' : '';
    return `${sign}${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}
</script>

<style scoped>
.bg-striped-red-gray {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(239, 68, 68), /* red-500 */
        rgb(239, 68, 68) 10px,
        rgb(209, 213, 219) /* gray-300 */ 10px,
        rgb(209, 213, 219) 20px
    );
}

.bg-striped-green-gray {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(22, 163, 74), /* green-700 */
        rgb(34, 197, 94) 10px,
        rgb(209, 213, 219) /* gray-300 */ 10px,
        rgb(209, 213, 219) 20px
    );
}
</style>
