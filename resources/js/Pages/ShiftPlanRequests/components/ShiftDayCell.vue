<template>
    <div class="min-h-[3.25rem] rounded-xl border border-dashed p-1.5 flex flex-col gap-1"
         :class="[ rejectActive && isSelectedDay ? 'ring-2 ring-danger-border' : '',
             day.is_rejected ? 'border-danger bg-danger-surface shadow-none' : 'border-border-subtle bg-surface-sunken'
         ]">
        <div v-if="day.rejection_reason" class="text-[10px] text-danger font-bold leading-tight mb-1 px-1 border-b border-danger-border pb-1">
            {{ day.rejection_reason }}
        </div>
        <!-- Regelverstöße (ShiftRuleViolations) des Users an diesem Tag — Markup analog ShiftPlanCell.
             Mit Bearbeitungsrecht (can plan shifts + Regeln bearbeiten) öffnet der Klick das ViolationEditModal,
             sonst gibt es nur den Tooltip. -->
        <div v-if="violations.length" class="flex items-center justify-end gap-0.5 px-0.5">
            <component
                :is="canEditViolations ? 'button' : 'div'"
                v-for="violation in violations"
                :key="violation.id"
                :type="canEditViolations ? 'button' : null"
                class="h-4 w-4 flex items-center justify-center"
                :class="canEditViolations ? 'cursor-pointer rounded hover:bg-surface-sunken' : ''"
                :title="violationTooltip(violation)"
                @click.stop="canEditViolations && $emit('open-violation', violation)"
            >
                <!-- Bearbeitet (resolved): Warndreieck in Warnfarbe MIT grünem Haken-Badge -->
                <span v-if="violation.status === 'resolved'" class="relative inline-flex h-3.5 w-3.5">
                    <svg class="h-3.5 w-3.5" :class="violationColorClass(violation)" :style="violationColorStyle(violation)" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <svg class="absolute -bottom-1 -right-1 h-2.5 w-2.5 rounded-full bg-white text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </span>
                <!-- Offener Verstoß: Warndreieck in Warnfarbe (ohne Regel: text-warning) -->
                <svg v-else class="h-3.5 w-3.5" :class="violationColorClass(violation)" :style="violationColorStyle(violation)" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </component>
        </div>
        <template v-if="entries && entries.length">
            <div v-for="entry in entries"
                 :key="entry.unique_key"
                 class="rounded-lg px-2 py-1.5 flex flex-col gap-0.5 text-[11px] transition relative"
                 :class="entry.is_removed_ghost ? 'border border-dashed border-danger-border bg-danger-surface/40'
                     : (entry.is_individual_time ? 'bg-accent-50 border border-accent-200' : [entryCardClass(entry), 'cursor-pointer'])"
                 @click="(!entry.is_individual_time && !entry.is_removed_ghost) && $emit('open-history', entry.shift_id)">

                <!-- Removed / Deleted Ghost Entry -->
                <template v-if="entry.is_removed_ghost">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-danger line-through">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <IconTrash class="h-3 w-3 text-danger shrink-0" />
                    </div>
                    <span class="inline-flex w-fit items-center gap-1 rounded-full bg-danger-surface px-1.5 py-0.5 text-[9px] font-semibold text-danger">
                        {{ entry.reason === 'shift_deleted' ? $t('Subsequently deleted') : $t('Removed from shift') }}
                    </span>
                    <div v-if="entry.qualification" class="text-[10px] text-danger/80 line-through truncate">{{ entry.qualification }}</div>
                </template>

                <!-- Shift Entry -->
                <template v-else-if="!entry.is_individual_time">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-text">{{ entry.start_time }} – {{ entry.end_time }}</span>
                        <div class="flex items-center gap-1">
                            <span v-if="entry.is_subsequently_added" class="inline-flex items-center gap-1 rounded-full bg-warning-surface px-1.5 py-0.5 text-[9px] font-semibold text-warning">
                                {{ $t('Subsequently added') }}
                            </span>
                            <span v-if="entry.is_committed" class="inline-flex items-center gap-1 text-[10px] text-text-subtle">
                                <IconLock class="h-3 w-3" />
                                {{ $t('Committed') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] text-text-subtle truncate">{{ entry.qualification || $t('Shift') }}</span>
                        <span v-if="entry.has_changes_after_commit" class="inline-flex items-center gap-1 text-[10px] text-danger">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Changed') }}
                        </span>
                        <span v-if="entry.has_changes_after_workflow" class="inline-flex items-center gap-1 text-[10px] text-special-orange">
                            <IconAlertTriangle class="h-3 w-3" />
                            {{ $t('Change requested') }}
                        </span>
                    </div>
                    <div v-if="entry.room_name" class="flex items-center gap-1 text-[10px] text-text-subtle">
                        <IconMapPin class="h-3 w-3 shrink-0" />
                        <span class="truncate">{{ entry.room_name }}</span>
                    </div>
                    <div v-if="entry.short_description" class="flex items-start gap-1 text-[10px] text-text-subtle">
                        <IconNote class="h-3 w-3 shrink-0 mt-px" />
                        <span class="line-clamp-2">{{ entry.short_description }}</span>
                    </div>
                    <div v-if="entry.workflow_rejection_reason" class="text-[10px] text-danger font-medium leading-tight mt-0.5">
                        {{ entry.workflow_rejection_reason }}
                    </div>
                </template>

                <!-- Individual Time Entry -->
                <template v-else>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-text">
                            {{ entry.full_day ? $t('Full day') : `${entry.start_time} – ${entry.end_time}` }}
                        </span>
                        <IconClock class="h-3 w-3 text-accent-500" />
                    </div>
                    <span class="text-[10px] text-accent-600 truncate">{{ entry.title || $t('Individual time') }}</span>
                </template>
            </div>
        </template>
        <template v-else>
            <div class="flex h-full items-center justify-center text-[11px] text-text-subtle">
                {{ $t('No shift') }}
            </div>
        </template>
    </div>
</template>
<script setup>
import { IconAlertTriangle, IconClock, IconLock, IconMapPin, IconNote, IconTrash } from '@tabler/icons-vue';
import {computed} from 'vue';
import {useI18n} from 'vue-i18n';
import {formatViolationMeasure} from '@/Pages/ShiftWarnings/ruleTypes.js';

const {t} = useI18n();

/** Farbe: Regelfarbe, ohne Regel (manuell "Sonstiges") der Warn-Token */
const violationColorStyle = (violation) =>
    (violation.shift_rule ? { color: violation.shift_rule.warning_color || '#ff0000' } : null);
const violationColorClass = (violation) => (violation.shift_rule ? '' : 'text-warning');

/** Tooltip: Regelname/Titel · Status · Messwert und Grenze (gleiche Formatierung wie ShiftPlanCell) */
const violationTooltip = (violation) => {
    const parts = [violation.shift_rule?.name || violation.title || t('Rule violation')];
    parts.push(violation.status === 'resolved' ? t('Processed') : t('Open'));
    const measure = formatViolationMeasure(violation, t);
    if (measure) parts.push(measure);
    if (violation.shift_rule?.description) parts.push(violation.shift_rule.description);
    return parts.join(' · ');
};

const props = defineProps({
    entries: { type: Array, default: () => [] },
    violations: { type: Array, default: () => [] },
    rejectActive: { type: Boolean, default: false },
    dayDate: { type: String, required: true },
    day: { type: Object, default: () => ({}) },
    selectedDays: { type: Object, required: true },
    shiftSelections: { type: Object, required: true },
    /** Verstoß-Marker klickbar (öffnet das Bearbeiten-Modal) — nur mit Bearbeitungsrecht */
    canEditViolations: { type: Boolean, default: false },
});
defineEmits(['open-history', 'open-violation']);
const isSelectedDay = computed(() => !!props.selectedDays[props.dayDate]);
const entryCardClass = (entry) => {
    let base = entry.has_changes_after_commit ? 'border border-danger-border bg-danger-surface/70 shadow-none hover:border-danger-border' : 'bg-white shadow-sm hover:ring-1 hover:ring-accent-200';

    if (entry.is_rejected || entry.workflow_rejection_reason) {
        base = 'border border-danger bg-danger-surface/30 shadow-none hover:border-danger';
    }

    if (props.rejectActive && props.shiftSelections[entry.unique_key]) {
        return base + ' ring-2 ring-danger-border';
    }
    return base;
};
</script>
