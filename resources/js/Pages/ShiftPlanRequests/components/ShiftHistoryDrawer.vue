<template>
    <transition name="fade">
        <div v-if="open && shift" class="fixed inset-0 z-50 flex">
            <div class="flex-1 bg-black/30" @click="$emit('close')"></div>
            <div class="relative w-full max-w-md bg-white shadow-xl h-full overflow-y-auto">
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50/80 backdrop-blur-sm">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            {{ $t('Shift history') }}
                            <span v-if="shift.is_committed"
                                  class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 border border-emerald-100">
                                <IconLock class="h-3 w-3"/>
                                {{ $t('Committed') }}
                            </span>
                        </h2>
                        <p class="text-[11px] text-gray-500">{{ formatDrawerHeader(shift) }}</p>
                    </div>
                    <button type="button"
                            class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                            @click="$emit('close')">
                        <IconX class="h-4 w-4"/>
                    </button>
                </div>
                <div class="px-4 py-4 space-y-6 text-sm">
                    <section class="rounded-xl border border-gray-100 bg-gray-50/80 px-3 py-3 text-[11px]">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-gray-800">{{ $t('Current shift overview') }}</p>
                            <div
                                class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 border border-gray-200 text-[10px] text-gray-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" v-if="shift.in_workflow"></span>
                                <span>{{
                                        shift.in_workflow ? $t('In approval workflow') : $t('No active workflow')
                                    }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $t('Date') }}</p>
                                <p class="text-[11px] font-medium text-gray-900">{{
                                        formatDateShort(shift.formatted_dates?.start || shift.formatted_dates?.frontend_start || shift.event_start_day)
                                    }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $t('Time') }}</p>
                                <p class="text-[11px] font-medium text-gray-900">{{ shift.start }} – {{ shift.end }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $t('Craft') }}</p>
                                <p class="text-[11px] font-medium text-gray-900 truncate">{{
                                        shift.craft?.name || '–'
                                    }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-gray-100">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $t('Project') }}</p>
                                <p class="text-[11px] font-medium text-gray-900 truncate">{{
                                        shift.project?.name || '–'
                                    }}</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="shift.committed_shift_changes?.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $t('Post-commit changes') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-medium text-gray-700 border border-gray-200">{{
                                    shift.committed_shift_changes.length
                                }} {{ $t('Changes') }}</span>
                        </div>
                        <ol class="space-y-3 border-l border-gray-200 pl-3">
                            <li v-for="(change, count) in shift.committed_shift_changes" :key="'csc-' + change.id"
                                class="relative rounded-lg px-3 py-2 border shadow-sm"
                                :class="change.acknowledged_at ? 'bg-emerald-50/70 border-emerald-100' : 'bg-red-50/70 border-red-100'">
                                <span class="absolute -left-[17.5px] top-2 h-2 w-2 rounded-full"
                                      :class="change.acknowledged_at ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-medium">{{ $t('Changes') }} #{{ count + 1 }}</span>
                                        <!--<span
                                            class="inline-flex items-center gap-1 text-[10px] rounded-full px-2 py-0.5"
                                            :class="change.acknowledged_at ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            {{ change.acknowledged_at ? $t('Acknowledged') : $t('Open') }}
                                        </span>-->
                                    </div>
                                    <span class="text-[11px] text-gray-600">{{
                                            formatDateTime(change.changed_at || change.created_at)
                                        }}</span>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-700 capitalize">{{ $t('Type') }}:
                                    {{ $t(change.change_type) }}</p>
                                <div class="mt-2 rounded-lg bg-white/90 border border-gray-200 px-2 py-1.5">
                                    <div
                                        class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] text-[10px] font-semibold text-gray-500 border-b border-gray-200 pb-1 mb-1">
                                        <span class="pr-1">{{ $t('Field') }}</span>
                                        <span class="text-right pr-1">{{ $t('Before') }}</span>
                                        <span class="text-right">{{ $t('After') }}</span>
                                    </div>
                                    <div v-for="fc in extractFieldEntries(change.field_changes)" :key="fc.fieldName"
                                         class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] items-start gap-1 text-[11px] py-0.5">
                                        <div class="pr-1"><span
                                            class="font-medium text-gray-800">{{ fieldLabel(fc.fieldName) }}</span>
                                        </div>
                                        <div class="text-right pr-1"><span
                                            class="inline-flex justify-end items-center gap-1 line-through text-gray-400">{{
                                                formatFieldValue(fc.fieldName, fc.old_label ?? fc.old)
                                            }}</span></div>
                                        <div class="text-right"><span
                                            class="inline-flex justify-end items-center gap-1 font-semibold text-gray-900">{{
                                                formatFieldValue(fc.fieldName, fc.new_label ?? fc.new)
                                            }}</span></div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </section>
                    <section v-if="shift.shift_plan_request_changes?.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $t('Request changes') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-medium text-indigo-700 border border-indigo-100">{{
                                    shift.shift_plan_request_changes.length
                                }} {{ $t('Changes') }}</span>
                        </div>
                        <ol class="relative space-y-3 border-l border-indigo-100 pl-3">
                            <li v-for="(change, idx) in shift.shift_plan_request_changes" :key="'spc-' + change.id"
                                class="relative rounded-lg bg-indigo-50/70 px-3 py-2 border border-indigo-100">
                                <span class="absolute -left-[17px] top-3 h-2 w-2 rounded-full bg-indigo-400"></span>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-semibold text-indigo-900">{{
                                                    $t('Modification')
                                                }} #{{ idx + 1 }}</span>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-medium text-indigo-700 border border-indigo-100 capitalize">{{
                                                    $t(change.change_type)
                                                }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-indigo-900/80">
                                            <span class="inline-flex items-center gap-1">
                                                <IconUser class="h-3 w-3"/>
                                                <span>{{
                                                        change.changed_by ? (change.changed_by.full_name || (change.changed_by.first_name + ' ' + change.changed_by.last_name)) : $t('Unknown user')
                                                    }}</span>
                                            </span>
                                            <span class="text-indigo-900/70">{{
                                                    formatDateTime(change.changed_at || change.created_at)
                                                }}</span>
                                        </div>
                                    </div>
                                    <button type="button"
                                            v-if="request.status !== 'approved' && !isMyRequest"
                                            class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[8px] font-medium text-rose-700 hover:bg-rose-100 hover:border-rose-300 transition"
                                            @click.stop="$emit('reject-change', change)">
                                        <IconX class="h-3 w-3"/>
                                        {{ $t('Reject change') }}
                                    </button>
                                </div>
                                <div class="mt-2 rounded-lg bg-white/90 border border-indigo-100/70 px-2 py-1.5">
                                    <div
                                        class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] text-[10px] font-semibold text-gray-500 border-b border-indigo-100/70 pb-1 mb-1">
                                        <span class="pr-1">{{ $t('Field') }}</span>
                                        <span class="text-right pr-1">{{ $t('Before') }}</span>
                                        <span class="text-right">{{ $t('After') }}</span>
                                    </div>
                                    <div v-for="fc in extractFieldEntries(change.field_changes)" :key="fc.fieldName"
                                         class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] items-start gap-1 text-[11px] py-0.5">
                                        <div class="pr-1"><span
                                            class="font-medium text-gray-800">{{ fieldLabel(fc.fieldName) }}</span>
                                        </div>
                                        <div class="text-right pr-1"><span
                                            class="inline-flex justify-end items-center gap-1 line-through text-gray-400">{{
                                                formatFieldValue(fc.fieldName, fc.old_label ?? fc.old)
                                            }}</span></div>
                                        <div class="text-right"><span
                                            class="inline-flex justify-end items-center gap-1 font-semibold text-gray-900">{{
                                                formatFieldValue(fc.fieldName, fc.new_label ?? fc.new)
                                            }}</span></div>
                                    </div>
                                    <div v-if="extractInitialState(change.field_changes)"
                                         class="mt-2 rounded-md bg-indigo-50/60 px-2 py-1.5 border border-dashed border-indigo-200">
                                        <p class="text-[10px] font-semibold text-indigo-900 mb-1 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                            {{ $t('Initial state') }}
                                        </p>
                                        <div
                                            v-for="(val, key) in pickInitialFields(extractInitialState(change.field_changes))"
                                            :key="key" class="flex items-start justify-between gap-2 text-[10px]">
                                            <span class="shrink-0 text-indigo-900/90">{{ fieldLabel(key) }}</span>
                                            <span class="flex-1 text-right text-indigo-900">{{
                                                    formatFieldValue(key, val)
                                                }}</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </section>
                    <section v-if="shift.activities?.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $t('Activity log') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2 py-0.5 text-[10px] font-medium text-gray-700 border border-gray-200">{{
                                    shift.activities.length
                                }} {{ $t('Entries') }}</span>
                        </div>
                        <ol class="space-y-3 border-l border-gray-200 pl-3">
                            <li v-for="activity in shift.activities" :key="'act-' + activity.id"
                                class="relative rounded-lg bg-gray-50 px-3 py-2 border border-gray-100">
                                <span class="absolute -left-[17.5px] top-2 h-2 w-2 rounded-full bg-gray-400"></span>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-medium text-gray-800 first-letter:capitalize">{{
                                                    $t(activity.event)
                                                }}</span>
                                            <span v-if="activityContext(activity)"
                                                  class="inline-flex items-center gap-1 text-[10px] rounded-full px-2 py-0.5 bg-gray-200 text-gray-700">{{
                                                    $t(activityContext(activity))
                                                }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-gray-600">
                                            <span class="inline-flex items-center gap-1">
                                                <IconUser class="h-3 w-3"/>
                                                <span>{{
                                                        activity.causer ? (activity.causer.full_name || (activity.causer.first_name + ' ' + activity.causer.last_name)) : $t('System')
                                                    }}</span>
                                            </span>
                                            <span class="text-gray-500">{{ formatDateTime(activity.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="extractActivityChanges(activity).length"
                                     class="mt-2 space-y-1 text-[11px] text-gray-800">
                                    <div v-for="fc in extractActivityChanges(activity)" :key="fc.fieldName"
                                         class="flex items-start justify-between gap-2">
                                        <span class="font-semibold shrink-0">{{ fieldLabel(fc.fieldName) }}</span>
                                        <span class="flex-1 text-right">
                                            <span class="line-through text-gray-400">{{
                                                    formatFieldValue(fc.fieldName, fc.old)
                                                }}</span>
                                            <span class="mx-1">→</span>
                                            <span class="font-medium text-gray-900">{{
                                                    formatFieldValue(fc.fieldName, fc.new)
                                                }}</span>
                                        </span>
                                    </div>
                                </div>

                                <p v-if="!hasActivityTranslations(activity) && !extractActivityChanges(activity).length"
                                   class="mt-1 text-[11px] text-gray-500">
                                    {{ $t('No detailed properties for this activity.') }}</p>
                                <p v-else class="mt-1 text-[11px] text-gray-500">{{ activityTranslation(activity) }}</p>
                            </li>
                        </ol>
                    </section>
                    <section
                        v-if="!shift.shift_plan_request_changes?.length && !shift.committed_shift_changes?.length && !shift.activities?.length">
                        <p class="text-xs text-gray-500">{{ $t('No history entries for this shift.') }}</p>
                    </section>
                </div>
            </div>
        </div>
    </transition>
</template>
<script setup>
import {IconLock, IconX, IconUser, IconAlertTriangle} from '@tabler/icons-vue';
import {useShiftPlanRequest} from './useShiftPlanRequest.js';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    shift: {
        type: Object,
        default: null
    },
    request:{
        type: Object,
        default: null
    },
    isMyRequest: {
        type: Boolean,
        default: false
    }
});
const {
    formatDateTime,
    formatDateShort,
    extractFieldEntries,
    fieldLabel,
    formatFieldValue,
    extractInitialState,
    pickInitialFields,
    activityContext,
    extractActivityChanges,
    hasActivityTranslations,
    activityTranslation,
    formatDrawerHeader
} = useShiftPlanRequest();
</script>

