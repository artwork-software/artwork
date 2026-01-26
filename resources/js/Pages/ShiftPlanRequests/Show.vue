<template>
    <AppLayout :title="$t('Shift plan request')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center gap-3">
                <Link
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50"
                    :href="!isMyRequest ? route('shifts.approvals.review') : route('shifts.approvals.requests')"
                >
                    <IconArrowLeft class="h-4 w-4"/>
                    <span>{{ $t('Back to shift plan requests') }}</span>
                </Link>
            </div>

            <!-- Header Card -->
            <ShiftPlanRequestHeader
                :request="request"
                :is-my-request="isMyRequest"
                @accept="acceptRequest"
                @start-reject="startReject"
            />

            <!-- Day Header -->
            <WeekOverview :days="daysComputed" :grid-style="gridStyle"/>
            <!-- Rows: User / Freelancer / ServiceProvider / Unassigned -->
            <div class="space-y-4">
                <ShiftPlanRequestRow
                    v-for="row in rows"
                    :key="row.key"
                    :row="row"
                    :days="daysComputed"
                    :grid-style="gridStyle"
                    :reject-active="rejectState.active"
                    :selected-days="rejectState.selectedDays"
                    :shift-selections="rejectState.shiftSelections"
                    @open-history="openHistoryDrawer"
                />

                <div v-if="!rows.length" class="text-center text-sm text-gray-500">
                    {{ $t('No shifts found for this request.') }}
                </div>
            </div>
        </div>

        <!-- RIGHT DRAWER: History -->
        <ShiftHistoryDrawer
            :open="historyDrawer.open"
            :request="request"
            :shift="selectedShift"
            :is-my-request="isMyRequest"
            @close="closeHistoryDrawer"
            @reject-change="rejectRequestChange"
        />

        <RejectShiftPlanRequestModal
            v-if="rejectState.modalOpen"
            :days="daysComputed"
            :rows="rows"
            :selected-days="rejectState.selectedDays"
            :shift-selections="rejectState.shiftSelections"
            :shift-reasons="rejectState.shiftReasons"
            :day-reasons="rejectState.dayReasons"
            :global-comment="rejectState.globalComment"
            :has-any-selection="hasAnySelection"
            :can-confirm-reject="canConfirmReject"
            @toggle-day="toggleDaySelection"
            @toggle-shift="toggleShiftSelection"
            @update-day-reason="updateDayReason"
            @update-shift-reason="updateShiftReason"
            @update-global-comment="updateGlobalComment"
            @cancel="cancelReject"
            @confirm="confirmReject"
            @close="cancelReject"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link, router} from '@inertiajs/vue3';
import {computed, reactive} from 'vue';
import {IconArrowLeft} from '@tabler/icons-vue';
import ShiftPlanRequestHeader from './components/ShiftPlanRequestHeader.vue';
import WeekOverview from './components/WeekOverview.vue';
import ShiftPlanRequestRow from './components/ShiftPlanRequestRow.vue';
import ShiftHistoryDrawer from './components/ShiftHistoryDrawer.vue';
import RejectShiftPlanRequestModal from './components/RejectShiftPlanRequestModal.vue';
import {useShiftPlanRequest} from './components/useShiftPlanRequest.js';
import {useI18n} from 'vue-i18n';

const {t} = useI18n();

const props = defineProps({
    request: {type: Object, required: true},
    shifts: {type: Array, required: true},
    days: {type: Array, required: true},
    isMyRequest: {type: Boolean, required: false, default: false},
});

const daysComputed = computed(() => {
    return props.days.map(day => {
        const rejection = props.request.rejected_days?.find(rd => rd.date === day.date);
        return {
            ...day,
            is_rejected: !!rejection,
            rejection_reason: rejection?.reason || null,
        };
    });
});
const gridStyle = computed(() => {
    const cols = daysComputed.value.length || 7;
    return {gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`};
});
const {computeDurationHours, hasOpenPostCommitChange, hasOpenWorkflowChange} = useShiftPlanRequest();

// Reject State Tracking
const rejectState = reactive({
    active: false,
    modalOpen: false,
    globalComment: '',
    selectedDays: {}, // date => true
    dayReasons: {},   // date => reason string
    shiftSelections: {}, // uniqueKey => true
    shiftReasons: {}, // uniqueKey => reason string
});

const hasAnySelection = computed(() => Object.keys(rejectState.selectedDays).length > 0 || Object.keys(rejectState.shiftSelections).length > 0);
const canConfirmReject = computed(() => {
    if (!rejectState.active) return false;

    // Wenn keine Auswahl, dann globaler Kommentar muss vorhanden sein
    if (!hasAnySelection.value) {
        return rejectState.globalComment.trim().length > 0;
    }

    // Wenn Auswahl: Alle ausgewählten Elemente müssen validiert werden
    // Mindestens irgendein Grund insgesamt muss existieren
    const anyReason = rejectState.globalComment.trim().length > 0 ||
                      Object.values(rejectState.dayReasons).some(r => r?.trim().length) ||
                      Object.values(rejectState.shiftReasons).some(r => r?.trim().length);

    return anyReason;
});

// Auswahl Handler
const toggleDaySelection = (dayDate) => {
    if (rejectState.selectedDays[dayDate]) {
        delete rejectState.selectedDays[dayDate];
        delete rejectState.dayReasons[dayDate];
    } else {
        rejectState.selectedDays[dayDate] = true;
    }
};
const toggleShiftSelection = (uniqueKey) => {
    if (rejectState.shiftSelections[uniqueKey]) {
        delete rejectState.shiftSelections[uniqueKey];
        delete rejectState.shiftReasons[uniqueKey];
    } else {
        rejectState.shiftSelections[uniqueKey] = true;
    }
};
const updateShiftReason = ({uniqueKey, reason}) => {
    rejectState.shiftReasons[uniqueKey] = reason;
};
const updateDayReason = ({day, reason}) => {
    rejectState.dayReasons[day] = reason;
};
const updateGlobalComment = (val) => {
    rejectState.globalComment = val;
};

const startReject = () => {
    rejectState.active = true;
    rejectState.modalOpen = true;
};
const cancelReject = () => {
    rejectState.active = false;
    rejectState.modalOpen = false;
    rejectState.globalComment = '';
    rejectState.selectedDays = {};
    rejectState.dayReasons = {};
    rejectState.shiftSelections = {};
    rejectState.shiftReasons = {};
};

const confirmReject = () => {
    if (!canConfirmReject.value) return;

    const parseUniqueKey = (key) => {
        const parts = key.split('-');
        return {
            shift_id: Number(parts[0]),
            row_type: parts[1],               // user / freelancer / service_provider / unassigned
            row_id: parts[2] === 'null' ? null : Number(parts[2]),
            unique_key: key,
        };
    };

    const payload = {
        global_reason: rejectState.globalComment || null,
        days: Object.keys(rejectState.selectedDays).map(date => ({
            date,
            reason: rejectState.dayReasons[date] || null,
        })),
        shifts: Object.keys(rejectState.shiftSelections).map(key => ({
            ...parseUniqueKey(key),
            reason: rejectState.shiftReasons[key] || null,
        })),
    };


    router.post(
        route('shift-plan-requests.reject', props.request.id),
        payload,
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectState.modalOpen = false;
                rejectState.active = false;
            },
            onError: (errors) => {
                console.error('Reject error', errors);
            }
        }
    );
};

const acceptRequest = () => {
    router.post(
        route('shift-plan-requests.accept', props.request.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => console.log('Request accepted'),
            onError: (errors) => console.error('Error:', errors),
        }
    );
};

// Rows aus Shifts bauen
const rejectedMap = computed(() => {
    const list = props.request.rejected_shifts ?? [];
    const map = {};
    for (const item of list) {
        if (item?.unique_key) map[item.unique_key] = item.reason;
    }
    return map;
});

const rows = computed(() => {
    const map = new Map();
    const ensureRow = (key, base) => {
        if (!map.has(key)) {
            map.set(key, {
                key,
                type: base.type,
                id: base.id,
                name: base.name,
                avatar: base.avatar || null,
                typeLabel: base.typeLabel,
                days: {},
                totals: {total_shifts: 0, total_hours: 0}
            });
        }
        return map.get(key);
    };
    const addEntry = (row, date, shift, meta = {}) => {
        if (!row.days[date]) row.days[date] = [];
        const uniqueKey = `${shift.id}-${row.type}-${row.id}`;

        // Find existing entry to avoid duplicates if same shift is assigned to same person multiple times (shouldn't happen but just in case)
        const existing = row.days[date].find(e => e.unique_key === uniqueKey);
        if (existing) return;

        row.days[date].push({
            unique_key: uniqueKey,
            shift_id: shift.id,
            start_time: shift.start,
            end_time: shift.end,
            qualification: meta.qualification || null,
            short_description: meta.short_description || shift.description || null,
            is_committed: !!shift.is_committed,
            workflow_rejection_reason: rejectedMap.value[uniqueKey] ?? meta.workflow_rejection_reason ?? shift.workflow_rejection_reason ?? null,
            has_changes_after_commit: meta.has_changes_after_commit ?? false,
            has_changes_after_workflow: meta.has_changes_after_workflow ?? false
        });
        row.totals.total_shifts += 1;
        row.totals.total_hours += computeDurationHours(shift);
    };
    const dayDates = daysComputed.value.map(d => d.date);
    for (const shift of props.shifts) {
        const date = shift.formatted_dates?.frontend_start || shift.event_start_day;
        if (!date || !dayDates.includes(date)) continue;
        let hasAssignments = false;
        if (Array.isArray(shift.users) && shift.users.length) {
            hasAssignments = true;
            for (const user of shift.users) {
                const key = `user-${user.id}`;
                const row = ensureRow(key, {
                    type: 'user',
                    id: user.id,
                    name: user.full_name || `${user.first_name} ${user.last_name}`,
                    avatar: user.profile_photo_url,
                    typeLabel: 'User'
                });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, user.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, user.id);
                addEntry(row, date, shift, {
                    qualification: user.pivot?.short_description ?? null,
                    short_description: user.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest,
                    workflow_rejection_reason: user.pivot?.workflow_rejection_reason ?? null
                });
            }
        }
        if (Array.isArray(shift.freelancer) && shift.freelancer.length) {
            hasAssignments = true;
            for (const fl of shift.freelancer) {
                const key = `freelancer-${fl.id}`;
                const row = ensureRow(key, {
                    type: 'freelancer',
                    id: fl.id,
                    name: fl.full_name || fl.name,
                    avatar: fl.profile_photo_url,
                    typeLabel: 'Freelancer'
                });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, fl.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, fl.id);
                addEntry(row, date, shift, {
                    qualification: fl.pivot?.short_description ?? null,
                    short_description: fl.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest,
                    workflow_rejection_reason: fl.pivot?.workflow_rejection_reason ?? null
                });
            }
        }
        if (Array.isArray(shift.service_provider) && shift.service_provider.length) {
            hasAssignments = true;
            for (const sp of shift.service_provider) {
                const key = `service_provider-${sp.id}`;
                const row = ensureRow(key, {
                    type: 'service_provider',
                    id: sp.id,
                    name: sp.name,
                    avatar: sp.profile_photo_url,
                    typeLabel: 'Service provider'
                });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, sp.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, sp.id);
                addEntry(row, date, shift, {
                    qualification: sp.pivot?.short_description ?? null,
                    short_description: sp.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest,
                    workflow_rejection_reason: sp.pivot?.workflow_rejection_reason ?? null
                });
            }
        }
        if (!hasAssignments) {
            const key = 'unassigned';
            const row = ensureRow(key, {
                type: 'unassigned',
                id: null,
                name: t('Unassigned shifts'),
                avatar: null,
                typeLabel: null
            });
            const hasChangesAfterCommit = hasOpenPostCommitChange(shift, null);
            const hasChangesInRequest = hasOpenWorkflowChange(shift, null);
            addEntry(row, date, shift, {
                qualification: null,
                short_description: shift.description ?? null,
                has_changes_after_commit: hasChangesAfterCommit,
                has_changes_after_workflow: hasChangesInRequest
            });
        }
    }
    return Array.from(map.values());
});

// Drawer-State
const historyDrawer = reactive({open: false, shiftId: null});
const openHistoryDrawer = (shiftId) => {
    historyDrawer.shiftId = shiftId;
    historyDrawer.open = true;
};
const closeHistoryDrawer = () => {
    historyDrawer.open = false;
    historyDrawer.shiftId = null;
};
const selectedShift = computed(() => props.shifts.find(s => s.id === historyDrawer.shiftId) || null);

// Stub-Funktion für Einzel-Änderung Reject (Drawer)
const rejectRequestChange = (change) => {
    router.patch(
        route('shift-plan-requests.change.revert', {
            shiftPlanRequest: props.request.id,
            shiftChange: change.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // z.B. Liste der Changes aktualisieren
            },
            onError: (errors) => {
                console.error('Fehler beim Zurücksetzen der Änderung:', errors);
            },
        },
    );
};
</script>
