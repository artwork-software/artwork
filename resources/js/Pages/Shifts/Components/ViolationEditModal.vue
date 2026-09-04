<template>
    <ArtworkBaseModal
        :title="$t('Edit rule violation')"
        description=""
        modal-size="max-w-xl"
        @close="$emit('close')"
    >
        <div class="space-y-5 text-sm">
            <!-- Rule info box -->
            <!-- Ohne Regel (manuell "Sonstiges"): Titel statt Regelname, Warn-Token statt Regelfarbe, kein Messwert -->
            <div
                class="rounded-xl border px-4 py-3"
                :class="violation.shift_rule ? '' : 'border-warning-border bg-warning-surface'"
                :style="violation.shift_rule ? {
                    borderColor: violation.shift_rule.warning_color || '#ff0000',
                    backgroundColor: (violation.shift_rule.warning_color || '#ff0000') + '10',
                } : null"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 inline-block h-3 w-3 rounded-full shrink-0"
                        :class="violation.shift_rule ? '' : 'bg-warning'"
                        :style="violation.shift_rule ? { backgroundColor: violation.shift_rule.warning_color || '#ff0000' } : null"
                    ></span>
                    <div>
                        <h4 class="font-semibold text-text">
                            {{ violation.shift_rule?.name || violation.title || $t('Rule violation') }}
                        </h4>
                        <p v-if="violation.shift_rule?.description" class="mt-0.5 text-xs text-text-muted">
                            {{ violation.shift_rule.description }}
                        </p>
                        <p v-else-if="!violation.shift_rule" class="mt-0.5 text-xs text-text-muted">
                            {{ $t('Manual violation without rule') }}
                        </p>
                        <p v-if="measure" class="mt-1 text-xs font-medium text-text">
                            {{ measure }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs: Details / Verlauf -->
            <div class="flex items-center gap-1 border-b border-border-subtle">
                <button
                    type="button"
                    class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition"
                    :class="activeTab === 'details' ? 'border-accent-600 text-text' : 'border-transparent text-text-subtle hover:text-text'"
                    @click="activeTab = 'details'"
                >
                    {{ $t('Details') }}
                </button>
                <button
                    type="button"
                    class="px-3 py-1.5 text-xs font-medium border-b-2 -mb-px transition"
                    :class="activeTab === 'history' ? 'border-accent-600 text-text' : 'border-transparent text-text-subtle hover:text-text'"
                    @click="openHistoryTab"
                >
                    {{ $t('History') }}
                    <span v-if="historyEntries.length" class="ml-1 rounded-full bg-surface-sunken px-1.5 py-px text-[10px] text-text-subtle">{{ historyEntries.length }}</span>
                </button>
            </div>

            <template v-if="activeTab === 'details'">
                <!-- Violation details -->
                <div class="grid grid-cols-2 gap-4 rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-3">
                    <div>
                        <label class="block text-[11px] font-medium text-text-subtle mb-0.5">{{ $t('Date') }}</label>
                        <p class="text-sm text-text">{{ formatDate(violation.violation_date) }}</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-text-subtle mb-0.5">{{ $t('Severity') }}</label>
                        <span
                            :class="violation.severity === 'error' ? 'bg-danger-surface text-danger' : 'bg-warning-surface text-warning'"
                            class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                        >
                            {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                        </span>
                    </div>
                </div>

                <!-- Genehmigungsvermerk: bearbeitet / ignoriert von … am … -->
                <div v-if="approvalNote" class="rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-2 text-xs text-text-muted flex items-center gap-2">
                    <IconUserCheck class="h-4 w-4 text-text-subtle shrink-0" stroke-width="1.5" />
                    <span>{{ approvalNote }}</span>
                </div>

                <!-- Manual info -->
                <div v-if="violation.is_manual" class="rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-3">
                    <label class="block text-[11px] font-medium text-text-subtle mb-0.5">{{ $t('Manual violation') }}</label>
                    <p v-if="violation.created_by_user" class="text-xs text-text-muted">
                        {{ $t('Created by') }}: {{ violation.created_by_user?.first_name }} {{ violation.created_by_user?.last_name }}
                    </p>
                    <p v-if="violation.reason" class="text-xs text-text-muted mt-1">
                        {{ $t('Reason') }}: {{ violation.reason }}
                    </p>
                </div>

                <!-- Anspruch auf Ersatzruhetag (Arbeit an Sondertag) -->
                <div v-if="isHolidayViolation" class="rounded-lg border border-special-teal/40 bg-special-teal/10 px-3 py-2 text-xs text-text">
                    {{ $t('Work on special day {name}: the person is entitled to a replacement rest day.', { name: violation.violation_data?.holiday_name || '' }) }}
                </div>

                <!-- Violation data details -->
                <div v-if="violationDetails.length" class="rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-3">
                    <label class="block text-[11px] font-medium text-text-subtle mb-1">{{ $t('Details') }}</label>
                    <div v-for="detail in violationDetails" :key="detail.key" class="text-xs text-text-muted">
                        <span class="font-medium">{{ detail.label }}:</span> {{ detail.value }}
                    </div>
                </div>

                <!-- Compensation days assigned -->
                <div v-if="violation.compensation_days" class="rounded-xl border border-success-border bg-success-surface px-4 py-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-success"></span>
                            <span class="text-xs font-semibold text-success">{{ $t('Compensation days assigned to account') }}</span>
                        </div>
                        <button
                            v-if="canReprocess"
                            type="button"
                            class="text-[11px] text-success hover:text-success underline"
                            @click="isEditing = true"
                        >
                            {{ $t('Edit') }}
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-success">
                        <div>
                            <span class="font-medium">{{ $t('Compensation days') }}:</span>
                            {{ violation.compensation_days }}
                        </div>
                        <div>
                            <span class="font-medium">{{ $t('Deadline until granted') }}:</span>
                            {{ formatDate(violation.compensation_deadline) }}
                        </div>
                        <div v-if="violation.compensation_reason" class="col-span-2">
                            <span class="font-medium">{{ $t('Reason') }}:</span>
                            {{ violation.compensation_reason }}
                        </div>
                    </div>
                    <p v-if="hasGrantedCompensation" class="mt-2 text-[11px] text-text-muted">
                        {{ $t('A compensation day has already been granted — this rule violation can no longer be edited.') }}
                    </p>
                </div>

                <!-- Edit compensation form -->
                <template v-if="showProcessForm">
                    <div class="space-y-4 rounded-xl border border-border-subtle px-4 py-3">
                        <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ $t('Compensation days') }}
                        </h4>

                        <SettingsGuideBanner
                            variant="static"
                            :paragraphs="[
                                'Changes here are credited directly to the person\'s compensation account — this is an account-effective booking, not just a display.'
                            ]"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-text-subtle mb-1">
                                    {{ $t('Substitute days off') }}
                                </label>
                                <BaseInput
                                    type="number"
                                    id="compensation_days"
                                    v-model.number="processForm.compensation_days"
                                    :label="$t('Substitute days off')"
                                    :show-label="false"
                                    :min="0.5"
                                    :step="0.5"
                                    no-margin-top
                                />
                                <p v-if="processForm.errors.compensation_days" class="mt-1 text-xs text-danger">
                                    {{ $t(processForm.errors.compensation_days) }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-text-subtle mb-1">
                                    {{ $t('Deadline until granted') }}
                                </label>
                                <BaseInput
                                    type="date"
                                    id="compensation_deadline"
                                    v-model="processForm.compensation_deadline"
                                    :label="$t('Deadline')"
                                    :show-label="false"
                                    no-margin-top
                                />
                                <p v-if="defaultDeadlineDays > 0" class="mt-1 text-[11px] text-text-subtle">
                                    {{ defaultDeadlineSource === 'rule'
                                        ? $t('Default according to rule: {n} days', { n: defaultDeadlineDays })
                                        : $t('Default according to contract: {n} days', { n: defaultDeadlineDays }) }}
                                    <button type="button" class="ml-1 text-accent-600 hover:text-accent-700 underline" @click="applyDefaultDeadline">
                                        {{ $t('apply') }}
                                    </button>
                                </p>
                                <p v-if="processForm.errors.compensation_deadline" class="mt-1 text-xs text-danger">
                                    {{ $t(processForm.errors.compensation_deadline) }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <BaseTextarea
                                id="compensation_reason"
                                v-model="processForm.compensation_reason"
                                :label="$t('Reason')"
                                no-margin-top
                            />
                        </div>

                        <BaseCheckbox
                            id="for_holiday"
                            v-model="processForm.for_holiday"
                            :label="$t('Compensation day for public holiday')"
                            :description="$t('If activated, the compensation day reduces the daily target hours')"
                        />

                        <p v-if="reprocessError" class="text-xs text-danger">{{ reprocessError }}</p>
                    </div>
                </template>

                <!-- Ignored info -->
                <div v-if="violation.status === 'ignored'" class="rounded-lg border border-border-subtle bg-surface-sunken/70 px-3 py-3">
                    <label class="block text-[11px] font-medium text-text-subtle mb-0.5">{{ $t('Ignore reason') }}</label>
                    <p class="text-xs text-text-muted">{{ violation.ignore_reason || '-' }}</p>
                </div>

                <!-- Inline ignore reason input -->
                <div v-if="showIgnoreInput" class="space-y-3 rounded-xl border border-danger-border bg-danger-surface/50 px-4 py-3">
                    <h4 class="text-xs font-semibold text-danger">{{ $t('Ignore rule violation') }}</h4>
                    <BaseTextarea
                        id="ignore_reason"
                        v-model="ignoreReason"
                        :label="$t('Reason for ignoring')"
                    />
                    <p v-if="ignoreError" class="text-xs text-danger">{{ $t('Reason for ignoring') }}</p>
                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            :label="$t('Confirm ignore')"
                            is-delete-button
                            is-small
                            :disabled="ignoring"
                            @click="submitIgnore"
                        />
                        <BaseUIButton
                            :label="$t('Cancel')"
                            is-cancel-button
                            is-small
                            @click="showIgnoreInput = false; ignoreReason = ''; ignoreError = false"
                        />
                    </div>
                </div>
            </template>

            <!-- Verlauf -->
            <template v-else>
                <div v-if="historyLoading" class="py-6 text-center text-xs text-text-subtle">
                    {{ $t('Loading') }}…
                </div>
                <div v-else-if="historyError" class="rounded-lg border border-danger-border bg-danger-surface/50 px-3 py-2 text-xs text-danger">
                    {{ historyError }}
                </div>
                <div v-else-if="!historyEntries.length" class="py-8 text-center">
                    <IconHistory class="mx-auto h-8 w-8 text-text-subtle" stroke-width="1.5" />
                    <p class="mt-2 text-xs text-text-subtle">{{ $t('No history entries yet.') }}</p>
                </div>
                <ol v-else class="space-y-2">
                    <li
                        v-for="entry in historyEntries"
                        :key="entry.id"
                        class="rounded-lg border border-border-subtle bg-surface-sunken/60 px-3 py-2"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-text">{{ historyEventLabel(entry) }}</span>
                            <span class="text-[11px] text-text-subtle whitespace-nowrap">{{ formatDateTime(entry.created_at) }}</span>
                        </div>
                        <p class="text-[11px] text-text-subtle mt-0.5">
                            {{ entry.causer ? `${entry.causer.first_name ?? ''} ${entry.causer.last_name ?? ''}`.trim() : $t('System') }}
                        </p>
                        <!-- Beim Anlegen nur das Ereignis zeigen, keine Roh-IDs (Regel/Person) als Aenderungsliste -->
                        <ul v-if="entry.changes?.length && entry.event !== 'created'" class="mt-1.5 space-y-0.5">
                            <li v-for="change in entry.changes" :key="change.field" class="text-[11px] text-text-muted">
                                <span class="font-medium">{{ historyFieldLabel(change.field) }}:</span>
                                <span class="line-through text-text-subtle" v-if="change.old !== null && change.old !== undefined && change.old !== ''">{{ formatHistoryValue(change.field, change.old) }}</span>
                                <span v-else class="text-text-subtle">–</span>
                                <span class="mx-1">→</span>
                                <span>{{ formatHistoryValue(change.field, change.new) }}</span>
                            </li>
                        </ul>
                        <p v-if="entry.extra?.delete_reason" class="mt-1 text-[11px] text-text-muted">
                            <span class="font-medium">{{ $t('Reason') }}:</span> {{ entry.extra.delete_reason }}
                        </p>
                    </li>
                </ol>
            </template>

            <!-- Footer -->
            <div class="flex justify-between pt-2 border-t border-border-subtle mt-2">
                <div class="flex items-center gap-2">
                    <BaseUIButton
                        :label="$t('Cancel')"
                        is-cancel-button
                        @click="$emit('close')"
                    />
                    <BaseUIButton
                        v-if="violation.status === 'active' && !showIgnoreInput && activeTab === 'details'"
                        :label="$t('Ignore')"
                        is-delete-button
                        @click="showIgnoreInput = true"
                    />
                </div>
                <BaseUIButton
                    v-if="showProcessForm && activeTab === 'details'"
                    :label="$t('Save')"
                    is-add-button
                    :disabled="processForm.processing"
                    @click="submitProcess"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { IconHistory, IconUserCheck } from '@tabler/icons-vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue';
import { formatViolationMeasure } from '@/Pages/ShiftWarnings/ruleTypes.js';

const { t } = useI18n();

const props = defineProps({
    violation: { type: Object, required: true },
    compensationPeriod: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'updated']);

const activeTab = ref('details');
const isEditing = ref(false);
const showIgnoreInput = ref(false);
const ignoreReason = ref('');
const ignoreError = ref(false);
const ignoring = ref(false);
const reprocessError = ref('');

// Verlauf + Genehmigungsvermerk werden beim Öffnen vom History-Endpunkt geladen (die Verstoß-Objekte
// aus dem Schichtplan tragen resolved_by nur als ID, nicht als Name).
const historyLoading = ref(false);
const historyError = ref('');
const historyEntries = ref([]);
const historyMeta = ref(null);

const isHolidayViolation = computed(() => !!props.violation.violation_data?.for_holiday);
// Messwert nur bei Verstößen mit Regel (manuelle ohne Regel haben keine violation_data)
const measure = computed(() => (props.violation.shift_rule ? formatViolationMeasure(props.violation, t) : ''));

// Frist-Standard: Regel (default_compensation_deadline_days), sonst Vertrags-compensation_period
const defaultDeadlineSource = computed(() =>
    props.violation.shift_rule?.default_compensation_deadline_days ? 'rule' : 'contract'
);
const defaultDeadlineDays = computed(() =>
    Number(props.violation.shift_rule?.default_compensation_deadline_days || props.compensationPeriod || 0)
);

function getDefaultDeadline() {
    const days = defaultDeadlineDays.value;
    if (days > 0 && props.violation.violation_date) {
        const d = new Date(props.violation.violation_date);
        d.setDate(d.getDate() + days);
        // lokales Datum formatieren – toISOString() würde nach UTC verschieben (Off-by-one)
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }
    return '';
}

function applyDefaultDeadline() {
    processForm.compensation_deadline = getDefaultDeadline();
}

const processForm = useForm({
    compensation_days: props.violation.compensation_days
        || props.violation.shift_rule?.default_compensation_days
        || 0.5,
    compensation_deadline: props.violation.compensation_deadline
        ? String(props.violation.compensation_deadline).slice(0, 10)
        : getDefaultDeadline(),
    compensation_reason: props.violation.compensation_reason || '',
    // Arbeit an Sondertag: Ersatzfrei für Feiertag vorbelegen (dokumentierter Anspruch auf Ersatzruhetag)
    for_holiday: isHolidayViolation.value,
});

const hasGrantedCompensation = computed(() => !!historyMeta.value?.has_granted_compensation);

// DP-17: Nachbearbeitung nur, solange kein Ersatzfreitag gewährt ist
const canReprocess = computed(() => {
    if (props.violation.status === 'ignored') return false;
    if (historyMeta.value) return !!historyMeta.value.can_reprocess || props.violation.status === 'active';
    return props.violation.status !== 'ignored';
});

const showProcessForm = computed(() => {
    if (props.violation.status === 'ignored') return false;
    if (!props.violation.compensation_days) return props.violation.status === 'active';
    return isEditing.value && canReprocess.value;
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatDateTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
}

const resolvedByName = computed(() => {
    const user = historyMeta.value?.resolved_by_user ?? props.violation.resolved_by_user;
    if (!user) return '';
    return `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
});

const resolvedAt = computed(() => historyMeta.value?.resolved_at ?? props.violation.resolved_at ?? props.violation.updated_at ?? null);

const approvalNote = computed(() => {
    const status = props.violation.status;
    if (status !== 'resolved' && status !== 'ignored') return '';
    const name = resolvedByName.value || t('Unknown');
    const date = formatDateTime(resolvedAt.value);
    return status === 'ignored'
        ? t('Ignored by {name} on {date}', { name, date })
        : t('Processed/approved by {name} on {date}', { name, date });
});

const dataKeyLabels = {
    'planned_hours': () => t('Planned hours'),
    'max_allowed': () => t('Maximum allowed'),
    'consecutive_days': () => t('Consecutive days'),
    'weekly_hours': () => t('Weekly hours'),
    'rest_hours': () => t('Rest hours'),
    'min_required': () => t('Minimum required'),
    'days_until_shift': () => t('Days until shift'),
    'previous_segment_end': () => t('Previous shift end'),
    'current_segment_start': () => t('Next shift start'),
    'next_segment_type': () => t('Next entry type'),
    'type': () => t('Type'),
    'original_violation_date': () => t('Original violation date'),
    'compensation_days': () => t('Compensation days'),
    'holiday_name': () => t('Special day'),
    'shift_start': () => t('Shift start'),
    'day': () => t('Date'),
};

// Interne Schlüssel, die im Detailblock nichts erklären
const hiddenDataKeys = ['for_holiday', 'entitlement', 'weekday', 'reason_case', 'message'];

const datetimePattern = /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/;
const datePattern = /^\d{4}-\d{2}-\d{2}$/;

const valueLabels = {
    'shift': () => t('Shift'),
    'compensation_deadline_expired': () => t('Compensation deadline expired'),
    'half_day_off_on_special_day': () => t('Half day off on a special day'),
    'morning': () => t('Morning'),
    'afternoon': () => t('Afternoon'),
    'both': () => t('Whole day'),
};

function formatDataValue(value) {
    if (value === null || value === undefined) return '-';
    if (typeof value === 'boolean') return value ? t('Yes') : t('No');
    if (typeof value === 'string' && datetimePattern.test(value)) {
        const d = new Date(value);
        return d.toLocaleDateString('de-DE') + ', ' + d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    }
    if (typeof value === 'string' && datePattern.test(value)) {
        return new Date(value).toLocaleDateString('de-DE');
    }
    if (typeof value === 'string' && valueLabels[value]) {
        return valueLabels[value]();
    }
    if (typeof value === 'number') {
        return Number.isInteger(value) ? value : value.toFixed(1);
    }
    return value;
}

const violationDetails = computed(() => {
    const data = props.violation.violation_data;
    if (!data || typeof data !== 'object') return [];
    return Object.entries(data)
        .filter(([key]) => !hiddenDataKeys.includes(key))
        .map(([key, value]) => ({
            key,
            label: dataKeyLabels[key]?.() ?? key,
            value: formatDataValue(value),
        }));
});

// Verlauf
const historyEventLabels = {
    created: () => t('Created'),
    updated: () => t('Updated'),
    deleted: () => t('Deleted'),
    reprocessed: () => t('Reprocessed'),
    deleted_with_reason: () => t('Deleted with reason'),
};

function historyEventLabel(entry) {
    const subject = entry.subject_type === 'compensation_day_off' ? t('Compensation day off') : t('Rule violation');
    const event = historyEventLabels[entry.event]?.() ?? entry.event ?? entry.description ?? '';
    return `${subject}: ${event}`;
}

const historyFieldLabels = {
    status: () => t('Status'),
    resolved_at: () => t('Processed at'),
    resolved_by: () => t('Processed by'),
    reason: () => t('Reason'),
    ignore_reason: () => t('Ignore reason'),
    compensation_days: () => t('Compensation days'),
    compensation_deadline: () => t('Deadline until granted'),
    compensation_reason: () => t('Reason'),
    for_holiday: () => t('Compensation day for public holiday'),
    half_day_period: () => t('Half day period'),
    value: () => t('Value'),
    deadline: () => t('Deadline'),
    granted_date: () => t('Granted date'),
    granted_by: () => t('Granted by'),
    granted_at: () => t('Granted at'),
    user_id: () => t('Person'),
    violation_id: () => t('Rule violation'),
    shift_rule_id: () => t('Rule'),
    violation_date: () => t('Date'),
};

function historyFieldLabel(field) {
    return historyFieldLabels[field]?.() ?? field;
}

const statusLabels = {
    active: () => t('Open'),
    resolved: () => t('Processed'),
    ignored: () => t('Ignored'),
};

function formatHistoryValue(field, value) {
    if (value === null || value === undefined || value === '') return '–';
    if (field === 'status' && statusLabels[value]) return statusLabels[value]();
    return formatDataValue(value);
}

async function loadHistory() {
    historyLoading.value = true;
    historyError.value = '';
    try {
        const { data } = await axios.get(route('shift-rules.violations.history', { violation: props.violation.id }));
        historyEntries.value = data.entries ?? [];
        historyMeta.value = data.violation ?? null;
    } catch (error) {
        historyError.value = t('History could not be loaded.');
    } finally {
        historyLoading.value = false;
    }
}

function openHistoryTab() {
    activeTab.value = 'history';
    if (!historyEntries.value.length && !historyLoading.value) {
        loadHistory();
    }
}

onMounted(() => {
    loadHistory();
});

function submitProcess() {
    reprocessError.value = '';
    processForm.put(route('shift-rule-violations.process', { violation: props.violation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
        },
        onError: (errors) => {
            // 422 der Nachbearbeitung (bereits gewährt) kommt ohne Feldfehler
            if (errors && typeof errors === 'object' && !Object.keys(errors).length) {
                reprocessError.value = t('This rule violation cannot be edited anymore: a compensation day has already been granted.');
            }
        },
    });
}

function submitIgnore() {
    ignoreError.value = false;
    if (!ignoreReason.value.trim()) {
        ignoreError.value = true;
        return;
    }
    ignoring.value = true;
    router.post(route('shift-rule-violations.ignore', { violation: props.violation.id }), {
        ignore_reason: ignoreReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => emit('updated'),
        onFinish: () => { ignoring.value = false; },
    });
}

</script>
