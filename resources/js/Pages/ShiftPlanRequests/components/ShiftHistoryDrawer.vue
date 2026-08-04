<template>
    <transition name="fade">
        <div v-if="open && shift" class="fixed inset-0 z-50 flex">
            <div class="flex-1 bg-black/30" @click="$emit('close')"></div>
            <div class="relative w-full max-w-md bg-white shadow-xl h-full overflow-y-auto">
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-border-subtle bg-surface-sunken backdrop-blur-sm">
                    <div class="space-y-0.5">
                        <h2 class="text-sm font-semibold text-text flex items-center gap-2">
                            {{ $t('Shift history') }}
                            <span v-if="shift.is_committed"
                                  class="inline-flex items-center gap-1 rounded-full bg-success-surface px-2 py-0.5 text-[10px] font-medium text-success border border-success-border">
                                <IconLock class="h-3 w-3"/>
                                {{ $t('Committed') }}
                            </span>
                        </h2>
                        <p class="text-[11px] text-text-subtle">{{ formatDrawerHeader(shift) }}</p>
                    </div>
                    <button type="button"
                            class="inline-flex items-center justify-center rounded-full p-1.5 text-text-subtle hover:text-text-muted hover:bg-surface-sunken"
                            @click="$emit('close')">
                        <IconX class="h-4 w-4"/>
                    </button>
                </div>
                <div class="px-4 py-4 space-y-6 text-sm">
                    <div class="flex items-start gap-2 rounded-xl border border-accent-200 bg-accent-50 px-3 py-2 text-[11px] text-accent-700">
                        <IconInfoCircle class="h-4 w-4 shrink-0 mt-px"/>
                        <p>{{ $t('This panel shows all changes to the entire shift across every assigned person – not only the person you selected.') }}</p>
                    </div>
                    <section class="rounded-xl border border-border-subtle bg-surface-sunken px-3 py-3 text-[11px]">
                        <div class="flex items-center justify-between mb-2">
                            <p class="font-semibold text-text">{{ $t('Current shift overview') }}</p>
                            <div
                                class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 border border-border-subtle text-[10px] text-text-muted">
                                <span class="w-1.5 h-1.5 rounded-full bg-success" v-if="shift.in_workflow"></span>
                                <span>{{
                                        shift.in_workflow ? $t('In approval workflow') : $t('No active workflow')
                                    }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-border-subtle">
                                <p class="text-[10px] text-text-subtle uppercase tracking-wide">{{ $t('Date') }}</p>
                                <p class="text-[11px] font-medium text-text">{{
                                        formatDateShort(shift.formatted_dates?.start || shift.formatted_dates?.frontend_start || shift.event_start_day)
                                    }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-border-subtle">
                                <p class="text-[10px] text-text-subtle uppercase tracking-wide">{{ $t('Time') }}</p>
                                <p class="text-[11px] font-medium text-text">{{ shift.start }} – {{ shift.end }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-border-subtle">
                                <p class="text-[10px] text-text-subtle uppercase tracking-wide">{{ $t('Craft') }}</p>
                                <p class="text-[11px] font-medium text-text truncate">{{
                                        shift.craft?.name || '–'
                                    }}</p>
                            </div>
                            <div class="rounded-lg bg-white/80 px-2 py-1.5 border border-border-subtle">
                                <p class="text-[10px] text-text-subtle uppercase tracking-wide">{{ $t('Project') }}</p>
                                <p class="text-[11px] font-medium text-text truncate">{{
                                        shift.project?.name || '–'
                                    }}</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="shift.committed_shift_changes?.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                                {{ $t('Post-commit changes') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-[10px] font-medium text-text-muted border border-border-subtle">{{
                                    shift.committed_shift_changes.length
                                }} {{ $t('Changes') }}</span>
                        </div>
                        <ol class="space-y-3 border-l border-border-subtle pl-3">
                            <li v-for="(change, count) in shift.committed_shift_changes" :key="'csc-' + change.id"
                                class="relative rounded-lg px-3 py-2 border shadow-sm"
                                :class="change.acknowledged_at ? 'bg-success-surface border-success-border' : 'bg-danger-surface border-danger-border'">
                                <span class="absolute -left-[17.5px] top-2 h-2 w-2 rounded-full"
                                      :class="change.acknowledged_at ? 'bg-success' : 'bg-danger'"></span>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-medium">{{ $t('Changes') }} #{{ count + 1 }}</span>
                                        <!--<span
                                            class="inline-flex items-center gap-1 text-[10px] rounded-full px-2 py-0.5"
                                            :class="change.acknowledged_at ? 'bg-success-surface text-success' : 'bg-danger-surface text-danger'">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                            {{ change.acknowledged_at ? $t('Acknowledged') : $t('Open') }}
                                        </span>-->
                                    </div>
                                    <span class="text-[11px] text-text-muted">{{
                                            formatDateTime(change.changed_at || change.created_at)
                                        }}</span>
                                </div>
                                <p v-if="affectedPersonName(change)"
                                   class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-semibold text-text border border-border-subtle">
                                    <IconUser class="h-3 w-3 text-text-subtle"/>
                                    {{ affectedPersonName(change) }}
                                </p>
                                <p class="mt-1 text-[11px] text-text-muted capitalize">{{ $t('Type') }}:
                                    {{ $t(change.change_type) }}</p>
                                <div class="mt-2 rounded-lg bg-white/90 border border-border-subtle px-2 py-1.5">
                                    <div
                                        class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] text-[10px] font-semibold text-text-subtle border-b border-border-subtle pb-1 mb-1">
                                        <span class="pr-1">{{ $t('Field') }}</span>
                                        <span class="text-right pr-1">{{ $t('Before') }}</span>
                                        <span class="text-right">{{ $t('After') }}</span>
                                    </div>
                                    <div v-for="fc in extractFieldEntries(change.field_changes)" :key="fc.fieldName"
                                         class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] items-start gap-1 text-[11px] py-0.5">
                                        <div class="pr-1"><span
                                            class="font-medium text-text">{{ fieldLabel(fc.fieldName) }}</span>
                                        </div>
                                        <div class="text-right pr-1"><span
                                            class="inline-flex justify-end items-center gap-1 line-through text-text-subtle">{{
                                                formatFieldValue(fc.fieldName, fc.old_label ?? fc.old)
                                            }}</span></div>
                                        <div class="text-right"><span
                                            class="inline-flex justify-end items-center gap-1 font-semibold text-text">{{
                                                formatFieldValue(fc.fieldName, fc.new_label ?? fc.new)
                                            }}</span></div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </section>
                    <section v-if="shift.shift_plan_request_changes?.length">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                                {{ $t('Request changes') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-[10px] font-medium text-accent-700 border border-accent-200">{{
                                    shift.shift_plan_request_changes.length
                                }} {{ $t('Changes') }}</span>
                        </div>
                        <ol class="relative space-y-3 border-l border-accent-200 pl-3">
                            <li v-for="(change, idx) in shift.shift_plan_request_changes" :key="'spc-' + change.id"
                                class="relative rounded-lg bg-accent-50 px-3 py-2 border border-accent-200">
                                <span class="absolute -left-[17px] top-3 h-2 w-2 rounded-full bg-accent-500"></span>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-semibold text-accent-700">{{
                                                    $t('Modification')
                                                }} #{{ idx + 1 }}</span>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-medium text-accent-700 border border-accent-200 capitalize">{{
                                                    $t(change.change_type)
                                                }}</span>
                                        </div>
                                        <span v-if="affectedPersonName(change)"
                                              class="inline-flex w-fit items-center gap-1.5 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-semibold text-accent-700 border border-accent-200">
                                            <IconUser class="h-3 w-3 text-accent-500"/>
                                            {{ affectedPersonName(change) }}
                                        </span>
                                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-accent-700">
                                            <span class="inline-flex items-center gap-1">
                                                <IconUser class="h-3 w-3"/>
                                                <span>{{
                                                        change.changed_by ? (change.changed_by.full_name || (change.changed_by.first_name + ' ' + change.changed_by.last_name)) : $t('Unknown user')
                                                    }}</span>
                                            </span>
                                            <span class="text-accent-700">{{
                                                    formatDateTime(change.changed_at || change.created_at)
                                                }}</span>
                                        </div>
                                    </div>
                                    <button type="button"
                                            v-if="request.status !== 'approved' && !isMyRequest"
                                            class="inline-flex items-center gap-1 rounded-full border border-danger-border bg-danger-surface px-2 py-0.5 text-[8px] font-medium text-danger hover:bg-danger-surface hover:border-danger-border transition"
                                            @click.stop="$emit('reject-change', change)">
                                        <IconX class="h-3 w-3"/>
                                        {{ $t('Reject change') }}
                                    </button>
                                </div>
                                <div class="mt-2 rounded-lg bg-white/90 border border-accent-200 px-2 py-1.5">
                                    <div
                                        class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] text-[10px] font-semibold text-text-subtle border-b border-accent-200 pb-1 mb-1">
                                        <span class="pr-1">{{ $t('Field') }}</span>
                                        <span class="text-right pr-1">{{ $t('Before') }}</span>
                                        <span class="text-right">{{ $t('After') }}</span>
                                    </div>
                                    <div v-for="fc in extractFieldEntries(change.field_changes)" :key="fc.fieldName"
                                         class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] items-start gap-1 text-[11px] py-0.5">
                                        <div class="pr-1"><span
                                            class="font-medium text-text">{{ fieldLabel(fc.fieldName) }}</span>
                                        </div>
                                        <div class="text-right pr-1"><span
                                            class="inline-flex justify-end items-center gap-1 line-through text-text-subtle">{{
                                                formatFieldValue(fc.fieldName, fc.old_label ?? fc.old)
                                            }}</span></div>
                                        <div class="text-right"><span
                                            class="inline-flex justify-end items-center gap-1 font-semibold text-text">{{
                                                formatFieldValue(fc.fieldName, fc.new_label ?? fc.new)
                                            }}</span></div>
                                    </div>
                                    <div v-if="extractInitialState(change.field_changes)"
                                         class="mt-2 rounded-md bg-accent-50 px-2 py-1.5 border border-dashed border-accent-200">
                                        <p class="text-[10px] font-semibold text-accent-700 mb-1 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent-500"></span>
                                            {{ $t('Initial state') }}
                                        </p>
                                        <div
                                            v-for="(val, key) in pickInitialFields(extractInitialState(change.field_changes))"
                                            :key="key" class="flex items-start justify-between gap-2 text-[10px]">
                                            <span class="shrink-0 text-accent-700">{{ fieldLabel(key) }}</span>
                                            <span class="flex-1 text-right text-accent-700">{{
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
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                                {{ $t('Activity log') }}</h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-[10px] font-medium text-text-muted border border-border-subtle">{{
                                    shift.activities.length
                                }} {{ $t('Entries') }}</span>
                        </div>
                        <ol class="space-y-3 border-l border-border-subtle pl-3">
                            <li v-for="activity in shift.activities" :key="'act-' + activity.id"
                                class="relative rounded-lg bg-surface-sunken px-3 py-2 border border-border-subtle">
                                <span class="absolute -left-[17.5px] top-2 h-2 w-2 rounded-full bg-border-strong"></span>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] font-medium text-text first-letter:capitalize">{{
                                                    $t(activity.event)
                                                }}</span>
                                            <span v-if="activityContext(activity)"
                                                  class="inline-flex items-center gap-1 text-[10px] rounded-full px-2 py-0.5 bg-border-subtle text-text-muted">{{
                                                    $t(activityContext(activity))
                                                }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-text-muted">
                                            <span class="inline-flex items-center gap-1">
                                                <IconUser class="h-3 w-3"/>
                                                <span>{{
                                                        activity.causer ? (activity.causer.full_name || (activity.causer.first_name + ' ' + activity.causer.last_name)) : $t('System')
                                                    }}</span>
                                            </span>
                                            <span class="text-text-subtle">{{ formatDateTime(activity.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="extractActivityChanges(activity).length"
                                     class="mt-2 space-y-1 text-[11px] text-text">
                                    <div v-for="fc in extractActivityChanges(activity)" :key="fc.fieldName"
                                         class="flex items-start justify-between gap-2">
                                        <span class="font-semibold shrink-0">{{ fieldLabel(fc.fieldName) }}</span>
                                        <span class="flex-1 text-right">
                                            <span class="line-through text-text-subtle">{{
                                                    formatFieldValue(fc.fieldName, fc.old)
                                                }}</span>
                                            <span class="mx-1">→</span>
                                            <span class="font-medium text-text">{{
                                                    formatFieldValue(fc.fieldName, fc.new)
                                                }}</span>
                                        </span>
                                    </div>
                                </div>

                                <p v-if="!hasActivityTranslations(activity) && !extractActivityChanges(activity).length"
                                   class="mt-1 text-[11px] text-text-subtle">
                                    {{ $t('No detailed properties for this activity.') }}</p>
                                <p v-else class="mt-1 text-[11px] text-text-subtle">{{ activityTranslation(activity) }}</p>
                            </li>
                        </ol>
                    </section>
                    <section
                        v-if="!shift.shift_plan_request_changes?.length && !shift.committed_shift_changes?.length && !shift.activities?.length">
                        <p class="text-xs text-text-subtle">{{ $t('No history entries for this shift.') }}</p>
                    </section>
                </div>
            </div>
        </div>
    </transition>
</template>
<script setup>
import {IconLock, IconX, IconUser, IconAlertTriangle, IconInfoCircle} from '@tabler/icons-vue';
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
    affectedPersonName,
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

