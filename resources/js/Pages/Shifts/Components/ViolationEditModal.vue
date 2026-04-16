<template>
    <ArtworkBaseModal
        :title="$t('Edit rule violation')"
        description=""
        modal-size="max-w-xl"
        @close="$emit('close')"
    >
        <div class="space-y-5 text-sm">
            <!-- Rule info box -->
            <div
                class="rounded-xl border px-4 py-3"
                :style="{
                    borderColor: violation.shift_rule?.warning_color || '#ff0000',
                    backgroundColor: (violation.shift_rule?.warning_color || '#ff0000') + '10',
                }"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 inline-block h-3 w-3 rounded-full shrink-0"
                        :style="{ backgroundColor: violation.shift_rule?.warning_color || '#ff0000' }"
                    ></span>
                    <div>
                        <h4 class="font-semibold text-zinc-900">
                            {{ violation.shift_rule?.name }}
                        </h4>
                        <p class="mt-0.5 text-xs text-zinc-600">
                            {{ violation.shift_rule?.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Violation details -->
            <div class="grid grid-cols-2 gap-4 rounded-lg border border-zinc-100 bg-zinc-50/70 px-3 py-3">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-500 mb-0.5">{{ $t('Date') }}</label>
                    <p class="text-sm text-zinc-900">{{ formatDate(violation.violation_date) }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-500 mb-0.5">{{ $t('Severity') }}</label>
                    <span
                        :class="violation.severity === 'error' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'"
                        class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                    >
                        {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                    </span>
                </div>
            </div>

            <!-- Manual info -->
            <div v-if="violation.is_manual" class="rounded-lg border border-zinc-100 bg-zinc-50/70 px-3 py-3">
                <label class="block text-[11px] font-medium text-zinc-500 mb-0.5">{{ $t('Manual violation') }}</label>
                <p v-if="violation.created_by_user" class="text-xs text-zinc-700">
                    {{ $t('Created by') }}: {{ violation.created_by_user?.first_name }} {{ violation.created_by_user?.last_name }}
                </p>
                <p v-if="violation.reason" class="text-xs text-zinc-600 mt-1">
                    {{ $t('Reason') }}: {{ violation.reason }}
                </p>
            </div>

            <!-- Violation data details -->
            <div v-if="violationDetails.length" class="rounded-lg border border-zinc-100 bg-zinc-50/70 px-3 py-3">
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">{{ $t('Details') }}</label>
                <div v-for="detail in violationDetails" :key="detail.key" class="text-xs text-zinc-700">
                    <span class="font-medium">{{ detail.label }}:</span> {{ detail.value }}
                </div>
            </div>

            <!-- Compensation days assigned -->
            <div v-if="violation.compensation_days" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-semibold text-emerald-800">{{ $t('Compensation days assigned to account') }}</span>
                    </div>
                    <button
                        type="button"
                        class="text-[11px] text-emerald-600 hover:text-emerald-800 underline"
                        @click="isEditing = true"
                    >
                        {{ $t('Edit') }}
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-emerald-700">
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
            </div>

            <!-- Edit compensation form -->
            <template v-if="!violation.compensation_days || isEditing">
                <div class="space-y-4 rounded-xl border border-zinc-200 px-4 py-3">
                    <h4 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                        {{ $t('Compensation days') }}
                    </h4>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">
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
                            <p v-if="processForm.errors.compensation_days" class="mt-1 text-xs text-red-500">
                                {{ $t(processForm.errors.compensation_days) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">
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
                            <p v-if="processForm.errors.compensation_deadline" class="mt-1 text-xs text-red-500">
                                {{ $t(processForm.errors.compensation_deadline) }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-medium text-zinc-500 mb-1">
                            {{ $t('Reason') }}
                        </label>
                        <BaseTextarea
                            id="compensation_reason"
                            v-model="processForm.compensation_reason"
                            :label="$t('Reason')"
                            :show-label="false"
                            no-margin-top
                        />
                    </div>

                    <BaseCheckbox
                        id="for_holiday"
                        v-model="processForm.for_holiday"
                        :label="$t('Compensation day for public holiday')"
                        :description="$t('If activated, the compensation day reduces the daily target hours')"
                    />
                </div>
            </template>

            <!-- Ignored info -->
            <div v-if="violation.status === 'ignored'" class="rounded-lg border border-zinc-200 bg-zinc-50/70 px-3 py-3">
                <label class="block text-[11px] font-medium text-zinc-500 mb-0.5">{{ $t('Ignore reason') }}</label>
                <p class="text-xs text-zinc-700">{{ violation.ignore_reason || '-' }}</p>
                <p v-if="violation.resolved_by_user" class="text-xs text-zinc-500 mt-1">
                    {{ $t('Ignored by') }}: {{ violation.resolved_by_user?.first_name }} {{ violation.resolved_by_user?.last_name }}
                </p>
            </div>

            <!-- Inline ignore reason input -->
            <div v-if="showIgnoreInput" class="space-y-3 rounded-xl border border-red-200 bg-red-50/50 px-4 py-3">
                <h4 class="text-xs font-semibold text-red-800">{{ $t('Ignore rule violation') }}</h4>
                <BaseTextarea
                    id="ignore_reason"
                    v-model="ignoreReason"
                    :label="$t('Reason for ignoring')"
                />
                <p v-if="ignoreError" class="text-xs text-red-500">{{ $t('Reason for ignoring') }}</p>
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

            <!-- Footer -->
            <div class="flex justify-between pt-2 border-t border-zinc-100 mt-2">
                <div class="flex items-center gap-2">
                    <BaseUIButton
                        :label="$t('Cancel')"
                        is-cancel-button
                        @click="$emit('close')"
                    />
                    <BaseUIButton
                        v-if="violation.status === 'active' && !showIgnoreInput"
                        :label="$t('Ignore')"
                        is-delete-button
                        @click="showIgnoreInput = true"
                    />
                </div>
                <BaseUIButton
                    v-if="!violation.compensation_days || isEditing"
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
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const { t } = useI18n();

const props = defineProps({
    violation: { type: Object, required: true },
    compensationPeriod: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'updated']);

const isEditing = ref(false);
const showIgnoreInput = ref(false);
const ignoreReason = ref('');
const ignoreError = ref(false);
const ignoring = ref(false);

function getDefaultDeadline() {
    const days = props.violation.shift_rule?.default_compensation_deadline_days
        || props.compensationPeriod
        || 0;
    if (days > 0 && props.violation.violation_date) {
        const d = new Date(props.violation.violation_date);
        d.setDate(d.getDate() + days);
        return d.toISOString().split('T')[0];
    }
    return '';
}

const processForm = useForm({
    compensation_days: props.violation.compensation_days
        || props.violation.shift_rule?.default_compensation_days
        || 0.5,
    compensation_deadline: props.violation.compensation_deadline || getDefaultDeadline(),
    compensation_reason: props.violation.compensation_reason || '',
    for_holiday: false,
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

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
};

const datetimePattern = /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/;
const datePattern = /^\d{4}-\d{2}-\d{2}$/;

const valueLabels = {
    'shift': () => t('Shift'),
    'compensation_deadline_expired': () => t('Compensation deadline expired'),
};

function formatDataValue(value) {
    if (value === null || value === undefined) return '-';
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
    return Object.entries(data).map(([key, value]) => ({
        key,
        label: dataKeyLabels[key]?.() ?? key,
        value: formatDataValue(value),
    }));
});

function submitProcess() {
    processForm.put(route('shift-rule-violations.process', { violation: props.violation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
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
