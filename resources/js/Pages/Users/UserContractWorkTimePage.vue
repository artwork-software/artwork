<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <TinyPageHeadline
                    :title="$t('Contract & working hours')"
                    :description="$t('Contract periods and working hours of this person over time. Plan a change from a date; the previous period is closed automatically.')"
                />
            </div>
            <div class="flex items-center justify-end gap-2">
                <BaseUIButton
                    :label="$t('Plan change')"
                    is-add-button
                    :icon="IconCalendarPlus"
                    @click.stop="openPlanModal(null)"
                />
            </div>
        </div>

        <VisualFeedback :show-save-success="showVisualFeedback" />

        <!-- Leerzustand -->
        <div v-if="segments.length === 0" class="mt-5 rounded-lg border border-dashed border-warning-border bg-warning-surface px-4 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <component :is="IconFileSearch" class="size-5 shrink-0 text-warning mt-0.5" stroke-width="1.5" />
                    <div>
                        <p class="text-sm font-semibold text-text font-lexend">{{ $t('No contract and no working hours assigned') }}</p>
                        <p class="text-xs text-text-muted mt-0.5">
                            {{ $t('Without a contract no rule check applies and there are no season figures. Without working hours there is no daily target for the hours account and overtime calculation.') }}
                        </p>
                    </div>
                </div>
                <BaseUIButton
                    class="shrink-0"
                    :label="$t('Plan change')"
                    :icon="IconCalendarPlus"
                    is-add-button
                    @click.stop="openPlanModal(null)"
                />
            </div>
        </div>

        <!-- Zeitstrahl: neueste Zeiträume oben -->
        <ol v-else class="mt-5 relative border-l-2 border-border-subtle ml-3 space-y-4">
            <template v-for="(segment, index) in segments" :key="segment.start ?? 'beginning'">
                <!-- „Heute"-Markierung vor dem aktuellen Zeitraum (bzw. zwischen Zukunft und Vergangenheit) -->
                <li v-if="segment.showTodayMarker" class="relative pl-6" aria-hidden="true">
                    <span class="absolute -left-[9px] top-1/2 -translate-y-1/2 size-4 rounded-full bg-accent-500 ring-4 ring-surface"></span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-accent-700 font-lexend uppercase tracking-wide">{{ $t('Today') }}</span>
                        <span class="text-xs text-text-subtle">{{ formatDate(today) }}</span>
                        <span class="flex-1 border-t border-dashed border-accent-200"></span>
                    </div>
                </li>

                <li class="relative pl-6">
                    <span class="absolute -left-[7px] top-5 size-3 rounded-full ring-4 ring-surface"
                          :class="segment.status === 'current' ? 'bg-accent-500' : (segment.status === 'future' ? 'bg-accent-200' : 'bg-border')"></span>

                    <div class="rounded-lg bg-surface border shadow-raised overflow-hidden"
                         :class="segment.status === 'current' ? 'border-accent-200' : 'border-border-subtle'">
                        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 bg-surface-sunken/60 border-b border-border-subtle">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-text font-lexend tabular-nums">
                                    {{ formatPeriod(segment.start, segment.end, $t) }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                      :class="statusClasses[segment.status]">
                                    {{ $t(statusLabels[segment.status]) }}
                                </span>
                            </div>
                            <BaseUIButton
                                v-if="index === 0 || segment.status !== 'past'"
                                :label="$t('Plan change from here')"
                                :icon="IconCalendarPlus"
                                is-small
                                variant="ghost"
                                @click.stop="openPlanModal(segment.start ?? today)"
                            />
                        </div>

                        <ContractPeriodRow
                            :key="`c-${segment.contract?.id ?? 'none'}-${segment.start ?? 'b'}`"
                            :assign="segment.contract"
                            :user-id="userToEdit.id"
                            :user-contracts="userContracts"
                            :today="today"
                            @plan-from="openPlanModal(segment.start ?? today)"
                            @saved="flashSuccess"
                        />
                        <WorkTimePeriodRow
                            :key="`w-${segment.workTime?.id ?? 'none'}-${segment.start ?? 'b'}`"
                            :work-time="segment.workTime"
                            :user-id="userToEdit.id"
                            :work-time-patterns="workTimePatterns"
                            @plan-from="openPlanModal(segment.start ?? today)"
                            @saved="flashSuccess"
                        />
                    </div>
                </li>
            </template>
        </ol>

        <PlanChangeModal
            v-if="showPlanModal"
            :user-id="userToEdit.id"
            :user-contracts="userContracts"
            :work-time-patterns="workTimePatterns"
            :today="today"
            :current-contract="planContext.contract"
            :current-work-time="planContext.workTime"
            :preset-from="planContext.from"
            @close="showPlanModal = false"
            @saved="flashSuccess"
        />
    </UserEditHeader>
</template>

<script setup>
import {computed, ref} from "vue";
import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ContractPeriodRow from "@/Pages/Users/ContractWorkTime/ContractPeriodRow.vue";
import WorkTimePeriodRow from "@/Pages/Users/ContractWorkTime/WorkTimePeriodRow.vue";
import PlanChangeModal from "@/Pages/Users/ContractWorkTime/PlanChangeModal.vue";
import {IconCalendarPlus, IconFileSearch} from "@tabler/icons-vue";
import {
    addDays,
    entryValidOn,
    formatDate,
    formatPeriod
} from "@/Pages/Users/ContractWorkTime/contractWorkTimeFields.js";

const props = defineProps({
    userToEdit: { type: Object, required: true },
    currentTab: { type: String, required: true },
    /** Vertragszeiträume (Historie), ältester zuerst */
    contractAssigns: { type: Array, default: () => [] },
    /** Arbeitszeit-Sätze (Historie), ältester zuerst */
    workTimes: { type: Array, default: () => [] },
    userContracts: { type: Array, default: () => [] },
    workTimePatterns: { type: Array, default: () => [] },
    today: { type: String, required: true },
});

const showVisualFeedback = ref(false);
const showPlanModal = ref(false);
const planContext = ref({ from: null, contract: null, workTime: null });

const statusLabels = { past: 'Past', current: 'Current', future: 'Planned' };
const statusClasses = {
    past: 'bg-surface-sunken text-text-muted border border-border-subtle',
    current: 'bg-accent-100 text-accent-700',
    future: 'bg-accent-50 text-accent-700 border border-accent-200',
};

/**
 * Zeitstrahl-Segmente: Jede Grenze (Beginn eines Satzes, Tag nach einem Ende) eröffnet ein Segment;
 * je Segment der dort gültige Vertrag und Arbeitszeit-Satz. Neueste oben.
 */
const segments = computed(() => {
    const starts = new Set();
    [...props.contractAssigns, ...props.workTimes].forEach(entry => {
        starts.add(entry.valid_from ?? '');
        if (entry.valid_until) {
            starts.add(addDays(entry.valid_until, 1));
        }
    });

    const sorted = [...starts].sort();
    const built = sorted.map((start, index) => {
        const next = sorted[index + 1] ?? null;
        const end = next ? addDays(next, -1) : null;
        const probe = start || end || props.today;
        return {
            start: start || null,
            end,
            contract: entryValidOn(props.contractAssigns, probe),
            workTime: entryValidOn(props.workTimes, probe),
        };
    }).filter(segment => segment.contract || segment.workTime);

    built.forEach(segment => {
        const startsAfterToday = !!segment.start && segment.start > props.today;
        const endsBeforeToday = !!segment.end && segment.end < props.today;
        segment.status = startsAfterToday ? 'future' : (endsBeforeToday ? 'past' : 'current');
    });

    const newestFirst = built.reverse();
    // „Heute"-Marker: vor dem ersten Segment, das nicht in der Zukunft liegt
    const markerIndex = newestFirst.findIndex(segment => segment.status !== 'future');
    newestFirst.forEach((segment, index) => {
        segment.showTodayMarker = index === markerIndex;
    });

    return newestFirst;
});

const openPlanModal = (from) => {
    const probe = from ?? props.today;
    planContext.value = {
        from: from ?? props.today,
        contract: entryValidOn(props.contractAssigns, probe),
        workTime: entryValidOn(props.workTimes, probe),
    };
    showPlanModal.value = true;
};

const flashSuccess = () => {
    showVisualFeedback.value = true;
    setTimeout(() => {
        showVisualFeedback.value = false;
    }, 3000);
};
</script>
