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
                <div class="h-5 w-5 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-600"></div>
            </div>

            <template v-else>
                <!-- No open days -->
                <div v-if="!openDays.length" class="py-6 text-center text-xs text-zinc-500 italic">
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
                                    ? 'border-artwork-buttons-hover bg-blue-50/50'
                                    : 'border-zinc-200 hover:border-zinc-300 bg-white',
                                isOverdue(dayOff) ? 'ring-1 ring-red-200' : '',
                            ]"
                            @click="selectedDayOff = dayOff"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="dayOff.value >= 1.0 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ dayOff.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                                    </span>
                                    <span class="text-xs font-medium text-zinc-700">
                                        {{ dayOff.violation?.shift_rule?.name || '-' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <span :class="isOverdue(dayOff) ? 'text-red-600 font-medium' : 'text-zinc-500'">
                                        {{ $t('Deadline') }}: {{ formatDate(dayOff.deadline) }}
                                    </span>
                                    <span v-if="isOverdue(dayOff)" class="text-[10px] text-red-500 font-medium">
                                        ({{ $t('Deadline expired') }})
                                    </span>
                                </div>
                            </div>
                            <div v-if="dayOff.reason" class="mt-1 text-[11px] text-zinc-500 truncate">
                                {{ dayOff.reason }}
                            </div>
                        </div>
                    </div>

                    <!-- Date selection -->
                    <div v-if="selectedDayOff" class="space-y-3 rounded-xl border border-zinc-200 px-4 py-3">
                        <h4 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
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

                    <WeekSchedulePreview
                        v-if="grantedDate"
                        :user-id="userId"
                        :selected-date="grantedDate"
                    />

                    <!-- Shift warning -->
                    <div v-if="shiftWarning" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="text-xs font-semibold text-amber-800">
                                {{ $t('Warning: There are shifts on this date') }}
                            </span>
                        </div>
                        <p class="text-xs text-amber-700 mb-3">
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
            <div class="flex justify-between pt-2 border-t border-zinc-100 mt-2">
                <BaseUIButton
                    :label="$t('Cancel')"
                    is-cancel-button
                    @click="$emit('close')"
                />
                <BaseUIButton
                    v-if="selectedDayOff && grantedDate && !shiftWarning"
                    :label="$t('Grant compensation day')"
                    is-add-button
                    :disabled="granting"
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
const granting = ref(false);

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

// Reset shift warning when date or selected day changes
watch([() => grantedDate.value, () => selectedDayOff.value], () => {
    shiftWarning.value = null;
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

async function grantDay(removeShifts) {
    if (!selectedDayOff.value || !grantedDate.value) return;

    granting.value = true;
    router.post(
        route('compensation-day-offs.grant', { compensationDayOff: selectedDayOff.value.id }),
        {
            granted_date: grantedDate.value,
            remove_shifts: removeShifts,
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
