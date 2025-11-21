<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-5 flex flex-col gap-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <div class="h-11 w-11 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm" :style="{ backgroundColor: request.craft?.color || '#4f46e5' }">
                    {{ request.craft?.abbreviation }}
                </div>
                <div class="space-y-1">
                    <h1 class="text-lg font-semibold text-gray-900">{{ request.craft?.name }}</h1>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                            <IconCalendarWeek class="h-4 w-4" />
                            KW {{ request.week_number }} / {{ request.year }}
                        </span>
                        <span :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1', statusClasses(request.status)]">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $t(request.status) }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <IconClock class="h-4 w-4" />
                            <span>{{ $t('Requested at') }}: {{ formatDateTime(request.requested_at) }}</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:items-end text-xs text-gray-600 w-full sm:w-auto">
                <div class="flex flex-wrap gap-2 justify-end" v-if="request.status === 'pending'">
                    <BaseUIButton
                        type="button"
                        is-add-button
                        @click="$emit('accept')"
                        icon="IconCheck"
                        :label="$t('Accept')"
                    />
                    <BaseUIButton
                        type="button"
                        is-delete-button
                        @click="$emit('start-reject')"
                        icon="IconCancel"
                        :label="$t('Reject')"
                    />
                </div>
                <div class="flex items-center gap-2" v-if="request.reviewed_by">
                    <img v-if="request.reviewed_by.profile_photo_url" :src="request.reviewed_by.profile_photo_url" alt="" class="h-7 w-7 rounded-full object-cover" />
                    <div class="text-right">
                        <div class="font-medium text-gray-900">{{ request.reviewed_by.full_name || request.reviewed_by.first_name }}</div>
                        <div class="text-[11px] text-gray-500">{{ $t('Reviewed at') }} {{ formatDateTime(request.reviewed_at) }}</div>
                    </div>
                </div>
                <div v-if="request.review_comment" class="max-w-xs text-right text-[11px] text-gray-500">“{{ request.review_comment }}”</div>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <IconInfoCircle class="h-4 w-4 text-indigo-400" />
            <span>{{ $t('Below you see all shifts of this craft in the requested week, grouped by person and day.') }}</span>
        </div>
    </div>
</template>
<script setup>
import { useShiftPlanRequest } from './useShiftPlanRequest.js';
import { useI18n } from 'vue-i18n';
import { IconCalendarWeek, IconClock, IconInfoCircle, IconX, IconCheck } from '@tabler/icons-vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
const props = defineProps({ request: { type: Object, required: true } });
const emits = defineEmits(['accept','start-reject']);
const { t } = useI18n();
const { statusClasses, formatDateTime } = useShiftPlanRequest();
</script>
