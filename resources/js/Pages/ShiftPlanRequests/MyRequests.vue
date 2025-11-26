<template>
    <AppLayout :title="$t('Meine Dienstplananfragen')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center gap-3">
                <Link
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50"
                    :href="route('shifts.approvals.requests')"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>{{ $t('Zur Übersicht') }}</span>
                </Link>
            </div>

            <!-- Header Card (read-only, reduziert) -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-4 sm:px-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ request?.title || $t('Dienstplananfrage') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ request?.description || '' }}</p>
                        <p class="mt-2 text-xs text-gray-500">
                            <strong>{{ $t('KW') }}</strong> {{ request.week_number }} / {{ request.year }}
                            <span class="mx-2">•</span>
                            <strong>{{ $t('Angefragt am') }}</strong>
                            {{ formatDateTime(request.requested_at) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                            :class="statusClass(request.status)"
                        >
                            {{ statusLabel(request.status) }}
                        </span>
                    </div>
                </div>

                <div v-if="request.status === 'rejected' && request.rejection_reason" class="px-4 pb-4 sm:px-6">
                    <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">{{ request.rejection_reason }}</div>
                </div>
            </div>

            <!-- Day Header -->
            <WeekOverview :days="daysComputed" :grid-style="gridStyle"/>

            <!-- Rows (read-only) -->
            <div class="space-y-4">
                <ShiftPlanRequestRow
                    v-for="row in rows"
                    :key="row.key"
                    :row="row"
                    :days="daysComputed"
                    :grid-style="gridStyle"
                    :readonly="true"
                    :reject-active="false"
                    :selected-days="{}"
                    :shift-selections="{}"
                />

                <div v-if="!rows.length" class="text-center text-sm text-gray-500">
                    {{ $t('Keine Schichten für diese Anfrage gefunden.') }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from '@inertiajs/vue3';
import {computed} from 'vue';
import {useI18n} from 'vue-i18n';
import WeekOverview from './components/WeekOverview.vue';
import ShiftPlanRequestRow from './components/ShiftPlanRequestRow.vue';
import {useShiftPlanRequest} from './components/useShiftPlanRequest.js';

const {t} = useI18n();

const props = defineProps({
    request: {type: Object, required: true},
    shifts: {type: Array, required: true},
    days: {type: Array, required: true},
});

const daysComputed = computed(() => props.days);
const gridStyle = computed(() => {
    const cols = daysComputed.value.length || 7;
    return {gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`};
});

const {computeDurationHours, hasOpenPostCommitChange, hasOpenWorkflowChange} = useShiftPlanRequest();

// Status helpers
const statusLabel = (s) => {
    if (!s) return t('Unbekannt');
    if (s === 'pending' || s === 'review') return t('Prüfung ausstehend');
    if (s === 'accepted' || s === 'approved') return t('Angenommen');
    if (s === 'rejected') return t('Abgelehnt');
    return t('Unbekannt');
};
const statusClass = (s) => {
    if (s === 'pending' || s === 'review') return 'bg-yellow-50 text-yellow-800';
    if (s === 'accepted' || s === 'approved') return 'bg-green-50 text-green-800';
    if (s === 'rejected') return 'bg-red-50 text-red-800';
    return 'bg-gray-50 text-gray-800';
};

const formatDateTime = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return date.toLocaleString(undefined, {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

// Rows aus Shifts bauen (identisch zu Show.vue, read-only)
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
        row.days[date].push({
            shift_id: shift.id,
            start_time: shift.start,
            end_time: shift.end,
            qualification: meta.qualification || null,
            short_description: meta.short_description || shift.description || null,
            is_committed: !!shift.is_committed,
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
                    type: 'user', id: user.id, name: user.full_name || `${user.first_name} ${user.last_name}`, avatar: user.profile_photo_url, typeLabel: 'User'
                });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, user.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, user.id);
                addEntry(row, date, shift, {
                    qualification: user.pivot?.short_description ?? null,
                    short_description: user.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest
                });
            }
        }
        if (Array.isArray(shift.freelancer) && shift.freelancer.length) {
            hasAssignments = true;
            for (const fl of shift.freelancer) {
                const key = `freelancer-${fl.id}`;
                const row = ensureRow(key, { type: 'freelancer', id: fl.id, name: fl.full_name || fl.name, avatar: fl.profile_photo_url, typeLabel: 'Freelancer' });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, fl.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, fl.id);
                addEntry(row, date, shift, {
                    qualification: fl.pivot?.short_description ?? null,
                    short_description: fl.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest
                });
            }
        }
        if (Array.isArray(shift.service_provider) && shift.service_provider.length) {
            hasAssignments = true;
            for (const sp of shift.service_provider) {
                const key = `service_provider-${sp.id}`;
                const row = ensureRow(key, { type: 'service_provider', id: sp.id, name: sp.name, avatar: sp.profile_photo_url, typeLabel: 'Service provider' });
                const hasChangesAfterCommit = hasOpenPostCommitChange(shift, sp.id);
                const hasChangesInRequest = hasOpenWorkflowChange(shift, sp.id);
                addEntry(row, date, shift, {
                    qualification: sp.pivot?.short_description ?? null,
                    short_description: sp.pivot?.short_description ?? null,
                    has_changes_after_commit: hasChangesAfterCommit,
                    has_changes_after_workflow: hasChangesInRequest
                });
            }
        }
        if (!hasAssignments) {
            const key = 'unassigned';
            const row = ensureRow(key, { type: 'unassigned', id: null, name: t('Unassigned shifts'), avatar: null, typeLabel: null });
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
</script>

<style scoped>
/* kleine Styles falls nötig */
</style>
