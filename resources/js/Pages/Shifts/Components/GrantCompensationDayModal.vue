<template>
    <ArtworkBaseModal
        :title="$t('Grant compensation days for') + ' ' + userName"
        description=""
        modal-size="max-w-3xl"
        @close="$emit('close')"
    >
        <div class="space-y-5 text-sm">
            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="h-5 w-5 animate-spin rounded-full border-2 border-border border-t-zinc-600"></div>
            </div>

            <template v-else>
                <!-- No open days -->
                <div v-if="!openDays.length" class="py-6 text-center text-xs text-text-subtle italic">
                    {{ $t('No open compensation days.') }}
                </div>

                <!-- Open days list -->
                <template v-else>
                    <div class="space-y-2">
                        <div
                            v-for="dayOff in openDays"
                            :key="dayOff.id"
                            class="rounded-lg border px-3 py-2.5 cursor-pointer transition-colors"
                            :class="[
                                selectedDayOff?.id === dayOff.id
                                    ? 'border-accent-700 bg-accent-50/50'
                                    : 'border-border-subtle hover:border-border bg-white',
                                isOverdue(dayOff) ? 'ring-1 ring-danger-border' : '',
                            ]"
                            @click="selectedDayOff = dayOff"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="dayOff.value >= 1.0 ? 'bg-accent-100 text-accent-700' : 'bg-warning-surface text-warning'"
                                    >
                                        {{ dayOff.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                                    </span>
                                    <span
                                        v-if="dayOff.half_day_period === 'morning' || dayOff.half_day_period === 'afternoon'"
                                        class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold bg-warning-surface text-warning"
                                    >
                                        {{ dayOff.half_day_period === 'morning' ? $t('Morning') : $t('Afternoon') }}
                                    </span>
                                    <span class="text-xs font-medium text-text-muted">
                                        {{ dayOff.violation?.shift_rule?.name || '-' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span :class="isOverdue(dayOff) ? 'text-danger font-medium' : 'text-text-subtle'">
                                        {{ $t('Deadline') }}: {{ formatDate(dayOff.deadline) }}
                                    </span>
                                    <span v-if="isOverdue(dayOff)" class="text-[10px] text-danger font-medium">
                                        ({{ $t('Deadline expired') }})
                                    </span>
                                </div>
                            </div>
                            <div v-if="dayOff.reason" class="mt-1 text-[11px] text-text-subtle truncate">
                                {{ dayOff.reason }}
                            </div>
                        </div>
                    </div>

                    <!-- Date selection -->
                    <div v-if="selectedDayOff" class="space-y-3 rounded-xl border border-border-subtle px-4 py-3">
                        <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ $t('Select date') }}
                        </h4>
                        <BaseInput
                            type="date"
                            id="granted_date"
                            v-model="grantedDate"
                            :label="$t('Select date')"
                            :show-label="false"
                            no-margin-top
                        />
                    </div>

                    <!-- Half day period selection (only for half compensation days) -->
                    <div
                        v-if="selectedDayOff && isHalfDay(selectedDayOff)"
                        class="space-y-2 rounded-xl border border-border-subtle px-4 py-3"
                    >
                        <h4 class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            {{ $t('Time of day') }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="option in halfDayPeriodOptions"
                                :key="option.value"
                                class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs cursor-pointer"
                                :class="halfDayPeriod === option.value
                                    ? 'border-accent-700 bg-accent-50/50 text-text'
                                    : 'border-border-subtle text-text-muted hover:border-border'"
                            >
                                <input
                                    type="radio"
                                    class="hidden"
                                    name="half_day_period"
                                    :value="option.value"
                                    v-model="halfDayPeriod"
                                />
                                {{ $t(option.label) }}
                            </label>
                        </div>
                        <p class="text-[11px] text-text-subtle">
                            {{ $t('"Both" uses a second open half day off (morning + afternoon = whole day off).') }}
                        </p>
                    </div>

                    <WeekSchedulePreview
                        v-if="grantedDate"
                        :user-id="userId"
                        :selected-date="grantedDate"
                    />

                    <!-- Special day (Sondertag) rule warning -->
                    <div v-if="specialDayWarning" class="rounded-xl border border-warning-border bg-warning-surface px-4 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-warning"></span>
                            <span class="text-xs font-semibold text-warning">
                                {{ $t('Rule violation') }}
                            </span>
                        </div>
                        <p class="text-xs text-warning mb-1">
                            {{ $t('If you assign this now, it violates rule') }}: <span class="font-semibold">{{ specialDayWarning.name }}</span>
                        </p>
                        <p v-if="specialDayWarning.description" class="text-[11px] text-warning mb-3">
                            {{ specialDayWarning.description }}
                        </p>
                        <div class="flex gap-2">
                            <BaseUIButton
                                :label="$t('Assign anyway')"
                                is-delete-button
                                is-small
                                :disabled="granting"
                                @click="proceedAfterSpecialDay"
                            />
                            <BaseUIButton
                                :label="$t('Cancel')"
                                is-cancel-button
                                is-small
                                @click="specialDayWarning = null"
                            />
                        </div>
                    </div>

                    <!-- Shift warning -->
                    <div v-if="shiftWarning && !specialDayWarning" class="rounded-xl border border-warning-border bg-warning-surface px-4 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-warning"></span>
                            <span class="text-xs font-semibold text-warning">
                                {{ $t('Warning: There are shifts on this date') }}
                            </span>
                        </div>
                        <p class="text-xs text-warning mb-3">
                            {{ shiftWarning.shift_count }} {{ shiftWarning.shift_count === 1 ? $t('Shift') : $t('Shifts') }}
                        </p>
                        <div class="flex gap-2">
                            <BaseUIButton
                                :label="$t('Remove shifts and grant')"
                                is-delete-button
                                is-small
                                @click="grantDay(true)"
                            />
                            <BaseUIButton
                                :label="$t('Grant without removing shifts')"
                                is-add-button
                                is-small
                                @click="grantDay(false)"
                            />
                        </div>
                    </div>
                </template>
            </template>

            <!-- Footer -->
            <div class="flex justify-between pt-2 border-t border-border-subtle mt-2">
                <BaseUIButton
                    :label="$t('Cancel')"
                    is-cancel-button
                    @click="$emit('close')"
                />
                <BaseUIButton
                    v-if="selectedDayOff && grantedDate && !shiftWarning && !specialDayWarning"
                    :label="$t('Grant compensation day')"
                    is-add-button
                    :disabled="granting || (isHalfDay(selectedDayOff) && !halfDayPeriod)"
                    @click="checkAndGrant"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import WeekSchedulePreview from '@/Pages/Shifts/Components/WeekSchedulePreview.vue';

const props = defineProps({
    userId: { type: Number, required: true },
    preselectedDate: { type: String, default: '' },
    userName: { type: String, default: '' },
});

const emit = defineEmits(['close', 'granted']);

const loading = ref(true);
const openDays = ref([]);
const selectedDayOff = ref(null);
const grantedDate = ref(props.preselectedDate || '');
const shiftWarning = ref(null);
const specialDayWarning = ref(null);
const lastCheck = ref(null);
const granting = ref(false);
const halfDayPeriod = ref(null);

const halfDayPeriodOptions = [
    { value: 'morning', label: 'Morning' },
    { value: 'afternoon', label: 'Afternoon' },
    { value: 'both', label: 'Both' },
];

function isHalfDay(dayOff) {
    return dayOff && parseFloat(dayOff.value) < 1.0;
}

onMounted(async () => {
    try {
        const response = await axios.get(route('compensation-day-offs.open', { user: props.userId }));
        openDays.value = response.data;
    } catch (e) {
        openDays.value = [];
    } finally {
        loading.value = false;
    }
});

// Reset warnings when date or selected day changes
watch([() => grantedDate.value, () => selectedDayOff.value], () => {
    shiftWarning.value = null;
    specialDayWarning.value = null;
    lastCheck.value = null;
});

// Reset period when switching to another compensation day
watch(() => selectedDayOff.value, () => {
    halfDayPeriod.value = null;
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function isOverdue(dayOff) {
    if (!dayOff.deadline) return false;
    return new Date(dayOff.deadline) < new Date();
}

async function checkAndGrant() {
    if (!selectedDayOff.value || !grantedDate.value) return;

    granting.value = true;
    try {
        const response = await axios.post(
            route('compensation-day-offs.check', { compensationDayOff: selectedDayOff.value.id }),
            { granted_date: grantedDate.value }
        );

        lastCheck.value = response.data;

        // Special day rule warning takes precedence; requires explicit "Assign anyway".
        if (response.data.special_day_rule) {
            specialDayWarning.value = response.data.special_day_rule;
            granting.value = false;
            return;
        }

        if (response.data.has_shifts) {
            shiftWarning.value = response.data;
            granting.value = false;
            return;
        }

        await grantDay(false);
    } catch (e) {
        granting.value = false;
    }
}

// User confirmed the special-day rule violation -> continue, still honoring the shift conflict flow.
function proceedAfterSpecialDay() {
    specialDayWarning.value = null;
    if (lastCheck.value?.has_shifts) {
        shiftWarning.value = lastCheck.value;
        return;
    }
    grantDay(false);
}

async function grantDay(removeShifts) {
    if (!selectedDayOff.value || !grantedDate.value) return;
    if (isHalfDay(selectedDayOff.value) && !halfDayPeriod.value) return;

    granting.value = true;
    router.post(
        route('compensation-day-offs.grant', { compensationDayOff: selectedDayOff.value.id }),
        {
            granted_date: grantedDate.value,
            remove_shifts: removeShifts,
            half_day_period: isHalfDay(selectedDayOff.value) ? halfDayPeriod.value : null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('granted');
            },
            onFinish: () => {
                granting.value = false;
            },
        }
    );
}
</script>
