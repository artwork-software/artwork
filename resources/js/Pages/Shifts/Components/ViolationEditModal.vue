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
                    {{ $t('Created by') }}: {{ violation.created_by_user.first_name }} {{ violation.created_by_user.last_name }}
                </p>
                <p v-if="violation.reason" class="text-xs text-zinc-600 mt-1">
                    {{ $t('Reason') }}: {{ violation.reason }}
                </p>
            </div>

            <!-- Violation data details -->
            <div v-if="violation.violation_data && Object.keys(violation.violation_data).length" class="rounded-lg border border-zinc-100 bg-zinc-50/70 px-3 py-3">
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">{{ $t('Details') }}</label>
                <div v-for="(value, key) in violation.violation_data" :key="key" class="text-xs text-zinc-700">
                    <span class="font-medium">{{ formatDataKey(key) }}:</span> {{ value }}
                </div>
            </div>

            <!-- Compensation already granted -->
            <div v-if="violation.compensation_granted_at" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="text-xs font-semibold text-emerald-800">{{ $t('Compensation granted') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs text-emerald-700">
                    <div>
                        <span class="font-medium">{{ $t('Compensation days') }}:</span>
                        {{ violation.compensation_days }}
                    </div>
                    <div>
                        <span class="font-medium">{{ $t('Granted on') }}:</span>
                        {{ formatDate(violation.compensation_granted_at) }}
                    </div>
                </div>
            </div>

            <!-- Compensation form (not yet granted) -->
            <template v-else>
                <!-- Show existing compensation info if set -->
                <div v-if="violation.compensation_days && !isEditing" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                            <span class="text-xs font-semibold text-blue-800">{{ $t('Open compensations') }}</span>
                        </div>
                        <button
                            type="button"
                            class="text-[11px] text-blue-600 hover:text-blue-800 underline"
                            @click="isEditing = true"
                        >
                            {{ $t('Edit') }}
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-blue-700 mb-3">
                        <div>
                            <span class="font-medium">{{ $t('Compensation days') }}:</span>
                            {{ violation.compensation_days }}
                        </div>
                        <div>
                            <span class="font-medium">{{ $t('Deadline') }}:</span>
                            {{ formatDate(violation.compensation_deadline) }}
                        </div>
                        <div v-if="violation.compensation_reason" class="col-span-2">
                            <span class="font-medium">{{ $t('Reason') }}:</span>
                            {{ violation.compensation_reason }}
                        </div>
                    </div>
                    <BaseUIButton
                        :label="$t('Grant compensation')"
                        is-add-button
                        @click="grantCompensation"
                    />
                </div>

                <!-- Edit compensation form -->
                <div v-if="!violation.compensation_days || isEditing" class="space-y-4 rounded-xl border border-zinc-200 px-4 py-3">
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
                                {{ processForm.errors.compensation_days }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">
                                {{ $t('Deadline') }}
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
                                {{ processForm.errors.compensation_deadline }}
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
                </div>
            </template>

            <!-- Footer -->
            <div class="flex justify-between pt-2 border-t border-zinc-100 mt-2">
                <div class="flex items-center gap-2">
                    <BaseUIButton
                        :label="$t('Cancel')"
                        is-cancel-button
                        @click="$emit('close')"
                    />
                    <BaseUIButton
                        v-if="!violation.compensation_granted_at && violation.status === 'active'"
                        :label="$t('Ignore')"
                        is-delete-button
                        @click="ignoreViolation"
                    />
                </div>
                <BaseUIButton
                    v-if="!violation.compensation_granted_at && (!violation.compensation_days || isEditing)"
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
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    violation: { type: Object, required: true },
});

const emit = defineEmits(['close', 'updated']);

const isEditing = ref(false);

const processForm = useForm({
    compensation_days: props.violation.compensation_days || 0.5,
    compensation_deadline: props.violation.compensation_deadline || '',
    compensation_reason: props.violation.compensation_reason || '',
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function formatDataKey(key) {
    const keyMap = {
        'planned_hours': 'Geplante Stunden',
        'max_allowed': 'Maximum erlaubt',
        'consecutive_days': 'Aufeinanderfolgende Tage',
        'weekly_hours': 'Wochenstunden',
        'rest_hours': 'Ruhezeit (Stunden)',
        'min_required': 'Minimum erforderlich',
        'days_until_shift': 'Tage bis Schicht',
    };
    return keyMap[key] || key;
}

function submitProcess() {
    processForm.put(route('shift-rule-violations.process', { violation: props.violation.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
        },
    });
}

function ignoreViolation() {
    router.post(route('shift-rule-violations.ignore', { violation: props.violation.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
        },
    });
}

function grantCompensation() {
    router.post(route('shift-rule-violations.grant', { violation: props.violation.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            emit('updated');
        },
    });
}
</script>
