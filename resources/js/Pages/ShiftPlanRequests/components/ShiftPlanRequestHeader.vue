<template>
    <div class="rounded-2xl border border-border-subtle bg-white shadow-sm p-4 sm:p-5 flex flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="h-11 w-11 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm" :style="{ backgroundColor: request.craft?.color || '#4f46e5' }">
                    {{ request.craft?.abbreviation }}
                </div>
                <div class="space-y-1">
                    <h1 class="text-lg font-semibold text-text">{{ request.craft?.name }}</h1>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-text-subtle">
                        <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-xs font-medium text-accent-700">
                            <IconCalendarWeek class="h-4 w-4" />
                            {{ $t('KW') }} {{ request.week_number }} / {{ request.year }}
                        </span>
                        <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1', statusClasses(request.status)]">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ statusLabel(request.status) }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <IconClock class="h-4 w-4" />
                            <span>{{ $t('Requested at') }}: {{ formatDateTime(request.requested_at) }}</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:items-end text-xs text-text-muted w-full sm:w-auto">
                <!-- Genehmiger*innen: Freigeben / Ablehnen -->
                <div class="flex flex-wrap gap-2 justify-end" v-if="request.status === 'pending' && !isMyRequest">
                    <BaseUIButton
                        type="button"
                        is-add-button
                        @click="$emit('accept')"
                        icon="IconCheck"
                        :label="$t('Accept')"
                        :processing="processing === 'accept'"
                        :disabled="!!processing"
                    />
                    <BaseUIButton
                        type="button"
                        is-delete-button
                        @click="$emit('start-reject')"
                        icon="IconCancel"
                        :label="$t('Reject')"
                        :processing="processing === 'reject'"
                        :disabled="!!processing"
                    />
                </div>

                <!-- Antragsteller*in: offene Anfrage zurückziehen -->
                <div class="flex flex-wrap gap-2 justify-end" v-if="canWithdraw">
                    <BaseUIButton
                        type="button"
                        variant="secondary"
                        icon="IconArrowBackUp"
                        :label="$t('Withdraw request')"
                        :processing="processing === 'withdraw'"
                        :disabled="!!processing"
                        @click="$emit('withdraw')"
                    />
                </div>

                <!-- Antragsteller*in: abgelehnt → im Dienstplan nachbessern und erneut einreichen -->
                <div class="flex flex-wrap gap-2 justify-end" v-if="canResubmit">
                    <BaseUIButton
                        type="button"
                        is-add-button
                        icon="IconCalendarWeek"
                        :label="$t('Go to week in shift plan')"
                        :processing="processing === 'goto'"
                        :disabled="!!processing"
                        @click="$emit('go-to-week')"
                    />
                    <BaseUIButton
                        type="button"
                        variant="secondary"
                        icon="IconSend"
                        :label="$t('Resubmit for approval')"
                        :processing="processing === 'resubmit'"
                        :disabled="!!processing"
                        @click="$emit('resubmit')"
                    />
                </div>

                <div class="flex items-center gap-2" v-if="request.reviewed_by">
                    <img v-if="request.reviewed_by.profile_photo_url" :src="request.reviewed_by.profile_photo_url" alt="" class="h-7 w-7 rounded-full object-cover" />
                    <div class="text-right">
                        <div class="font-medium text-text">{{ request.reviewed_by.full_name || request.reviewed_by.first_name }}</div>
                        <div class="text-[11px] text-text-subtle">{{ $t('Reviewed at') }} {{ formatDateTime(request.reviewed_at) }}</div>
                    </div>
                </div>

                <div
                    v-if="request.review_comment"
                    :class="request.status === 'rejected' ? 'text-danger' : (request.status === 'approved' ? 'text-success' : 'text-text-subtle')"
                    class="max-w-xs text-right text-[11px]"
                >{{ (request.status === 'rejected' ? $t('Rejection Reason') : $t('Note')) + ': ' }}{{ request.review_comment }}</div>

                <!-- show who requested if not my request -->
                <div v-if="!isMyRequest && request.requested_by" class="flex items-center gap-2 mt-2">
                    <img v-if="request.requested_by.profile_photo_url" :src="request.requested_by.profile_photo_url" alt="" class="h-7 w-7 rounded-full object-cover" />
                    <div class="text-right">
                        <div class="font-medium text-text">{{ request.requested_by.full_name || request.requested_by.first_name }}</div>
                        <div class="text-[11px] text-text-subtle">{{ $t('Requested at') }} {{ formatDateTime(request.requested_at) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-text-subtle">
            <IconInfoCircle class="h-4 w-4 text-accent-500" />
            <span>{{ $t('Below you see all shifts of this craft in the requested week, grouped by person and day.') }}</span>
        </div>
        <!-- Abgelehnt: Hinweis für die antragstellende Person -->
        <div v-if="canResubmit" class="flex items-start gap-2 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
            <IconInfoCircle class="h-4 w-4 shrink-0 mt-px" />
            <span>{{ $t('The shifts were reset to the state before the request. Adjust the plan in the shift plan and submit the week again.') }}</span>
        </div>
    </div>
</template>
<script setup>
import { useShiftPlanRequest } from './useShiftPlanRequest.js';
import { IconCalendarWeek, IconClock, IconInfoCircle } from '@tabler/icons-vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
const props = defineProps({
    request: { type: Object, required: true },
    isMyRequest: { type: Boolean, default: false },
    // Antragsteller*in-Aktionen (von Show.vue über useShiftPlanRequestActions ermittelt)
    canWithdraw: { type: Boolean, default: false },
    canResubmit: { type: Boolean, default: false },
    processing: { type: String, default: null },
});
const emits = defineEmits(['accept', 'start-reject', 'withdraw', 'resubmit', 'go-to-week']);
const { statusClasses, statusLabel, formatDateTime } = useShiftPlanRequest();
</script>
