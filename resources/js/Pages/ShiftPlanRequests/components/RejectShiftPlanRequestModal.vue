<template>
    <ArtworkBaseModal
        :title="$t('Reject shift plan request')"
        :description="$t('Select days and/or shifts to reject and provide reasons. If none selected, provide a global reason to reject the entire request.')"
        modal-size="sm:max-w-4xl"
        @close="$emit('close')"
    >
        <div class="space-y-6 text-sm">
            <!-- Global Reason -->
            <div>
                <BaseTextarea
                    id="global-rejection-reason-reason"
                    rows="2"
                    class="w-full"
                    :label="$t('Enter a global rejection reason (optional if per-item reasons set).')"
                    :model-value="globalComment"
                    @update:modelValue="$emit('update-global-comment', $event)"
                />
                <p class="mt-1 text-[11px] text-gray-500" v-if="!hasAnySelection">
                    {{ $t('No selections yet: global reason will be used to reject entire request.') }}
                </p>
            </div>

            <!-- Days & Shifts -->
            <div class="space-y-4 max-h-[45vh] overflow-y-auto pr-1">
                <div
                    v-for="day in days"
                    :key="day.date"
                    class="rounded-lg border"
                    :class="selectedDays[day.date] ? 'border-rose-300 bg-rose-50/70' : 'border-gray-200 bg-gray-50'"
                >
                    <div class="flex items-start gap-3 p-3">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center justify-between">
                                <label
                                    class="inline-flex items-center gap-1 text-[11px] font-medium cursor-pointer"
                                    :class="selectedDays[day.date] ? 'text-rose-700' : 'text-gray-700'"
                                >
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                        :checked="!!selectedDays[day.date]"
                                        @change="$emit('toggle-day', day.date)"
                                    />
                                    <span>{{ day.label }}</span>
                                    <span
                                        v-if="day.is_today"
                                        class="text-[10px] inline-flex items-center px-1 rounded bg-rose-100 text-rose-700"
                                    >
                    {{ $t('Today') }}
                  </span>
                                </label>

                                <BaseInput
                                    v-if="selectedDays[day.date]"
                                    :id="'day-reason-' + day.date"
                                    type="text"
                                    class="!w-96"
                                    is-small
                                    :label="$t('Day reason (optional)')"
                                    :model-value="dayReasons[day.date] ?? ''"
                                    @update:modelValue="$emit('update-day-reason', { day: day.date, reason: $event })"
                                />
                            </div>

                            <!-- Shifts for this day -->
                            <div class="space-y-2">
                                <template v-for="row in rows" :key="row.key + '-' + day.date">
                                    <div v-if="row.days[day.date] && row.days[day.date].length" class="space-y-1">
                                        <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">
                                            {{ row.name }}
                                        </div>

                                        <div class="grid gap-2 grid-cols-1">
                                            <div
                                                v-for="entry in row.days[day.date]"
                                                :key="entry.unique_key"
                                                class="rounded-md border text-[11px] p-2 flex flex-col gap-1"
                                                :class="shiftSelections[entry.unique_key]
                          ? 'border-rose-300 bg-white shadow-sm ring-1 ring-rose-300'
                          : 'border-gray-200 bg-white/80'"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex flex-col">
                            <span class="font-medium text-gray-900">
                              {{ entry.start_time }} – {{ entry.end_time }}
                            </span>
                                                        <span class="text-[10px] text-gray-500 truncate">
                              {{ entry.qualification || $t('Shift') }}
                            </span>
                                                    </div>

                                                    <label class="inline-flex items-center gap-1 text-[10px] text-gray-600 cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                                            :checked="!!shiftSelections[entry.unique_key]"
                                                            @change="$emit('toggle-shift', entry.unique_key)"
                                                        />
                                                        <span>{{ $t('Select') }}</span>
                                                    </label>
                                                </div>

                                                <BaseInput
                                                    v-if="shiftSelections[entry.unique_key]"
                                                    :id="'shift-reason-' + entry.unique_key"
                                                    type="text"
                                                    :label="$t('Shift reason (optional)')"
                                                    :model-value="shiftReasons[entry.unique_key] ?? ''"
                                                    @update:modelValue="$emit('update-shift-reason', { uniqueKey: entry.unique_key, reason: $event })"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div v-if="!hasAnyShiftForDay(day.date)" class="text-[10px] text-gray-400 italic">
                                    {{ $t('No shifts on this day.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                <div class="text-[11px] text-gray-600">
                    <template v-if="!hasAnySelection">
                        {{ $t('Rejecting entire request with global reason.') }}
                    </template>
                    <template v-else>
                        {{ $t('Selected') }}: {{ Object.keys(selectedDays).length }} {{ $t('Days') }},
                        {{ Object.keys(shiftSelections).length }} {{ $t('Shifts') }}
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <BaseUIButton type="button" :label="$t('Cancel')" is-cancel-button @click="$emit('cancel')" />
                    <BaseUIButton
                        type="button"
                        :label="$t('Confirm rejection')"
                        :disabled="!canConfirmReject"
                        is-delete-button
                        @click="$emit('confirm')"
                    />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    days: { type: Array, required: true },
    rows: { type: Array, required: true },
    selectedDays: { type: Object, required: true },
    shiftSelections: { type: Object, required: true },
    shiftReasons: { type: Object, required: true },
    dayReasons: { type: Object, required: true },
    globalComment: { type: String, default: '' },
    hasAnySelection: { type: Boolean, required: true },
    canConfirmReject: { type: Boolean, required: true }
});

defineEmits([
    'close',
    'toggle-day',
    'toggle-shift',
    'update-shift-reason',
    'update-day-reason',
    'update-global-comment',
    'cancel',
    'confirm'
]);

const hasAnyShiftForDay = (date) => props.rows.some(r => r.days?.[date]?.length);
</script>
