<template>
    <ArtworkBaseModal
        :title="title"
        :description="description"
        modal-size="sm:max-w-xl"
        @close="$emit('close')"
    >
        <div class="space-y-5">
            <!-- Status -->
            <div class="flex flex-wrap items-center gap-2">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1', meta.chip]">
                    <span :class="['mr-1.5 h-1.5 w-1.5 rounded-full', meta.dot]"></span>
                    {{ $t(meta.label) }}
                </span>
                <span class="text-xs text-text-subtle">{{ $t(meta.description) }}</span>
            </div>

            <!-- Kennzahlen -->
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Shifts committed') }}</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-text">{{ cell.shifts_committed }} / {{ cell.shifts_total }}</dd>
                </div>
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Unacknowledged changes') }}</dt>
                    <dd :class="['mt-0.5 text-sm font-semibold', cell.open_changes > 0 ? 'text-warning' : 'text-text']">{{ cell.open_changes }}</dd>
                </div>
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Open rule violations') }}</dt>
                    <dd :class="['mt-0.5 text-sm font-semibold', cell.open_violations > 0 ? 'text-danger' : 'text-text']">{{ cell.open_violations }}</dd>
                </div>
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Open slots / demand') }}</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-text">
                        {{ cell.open_slots }} / {{ cell.required_slots }}
                        <span class="text-xs font-normal text-text-subtle">({{ $t('staffed') }}: {{ cell.staffed_slots }})</span>
                    </dd>
                </div>
            </dl>

            <!-- Frist -->
            <div v-if="cell.deadline_date" class="flex items-center gap-2 text-sm">
                <span :class="['h-2.5 w-2.5 rounded-full', deadline?.classes ?? 'bg-border']"></span>
                <span class="text-text">
                    {{ $t('Request deadline') }}: {{ cell.deadline_date_formatted }}
                </span>
                <span v-if="deadline" class="text-xs text-text-subtle">· {{ $t(deadline.label) }}</span>
            </div>
            <p v-else class="text-xs text-text-subtle">
                {{ $t('No request deadline configured for this craft.') }}
            </p>

            <!-- Anfrage -->
            <div v-if="cell.request_id" class="rounded-lg border border-border-subtle px-3 py-2 text-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-text">{{ $t('Approval request') }} #{{ cell.request_id }}</span>
                    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1', requestStatusClasses(cell.request_status)]">
                        {{ requestStatusLabel(cell.request_status) }}
                    </span>
                </div>
                <p v-if="cell.requested_at" class="mt-1 text-xs text-text-subtle">
                    {{ $t('Requested on') }}: {{ formatDateTime(cell.requested_at) }}
                </p>
                <p v-if="cell.reviewed_at" class="text-xs text-text-subtle">
                    {{ $t('Reviewed on') }}: {{ formatDateTime(cell.reviewed_at) }}
                </p>
                <p v-if="cell.review_comment" class="mt-1 text-xs text-text-muted">
                    {{ $t('Comment') }}: {{ cell.review_comment }}
                </p>
            </div>

            <BaseAlertComponent v-if="errorMessage" type="error" :message="errorMessage" />

            <!-- Aktionen -->
            <div class="flex flex-wrap items-center gap-2 border-t border-border-subtle pt-4">
                <BaseUIButton
                    type="button"
                    variant="secondary"
                    size="sm"
                    icon="IconCalendarWeek"
                    :label="$t('Go to week in shift plan')"
                    :processing="processing === 'goto'"
                    :disabled="processing !== null"
                    @click="goToWeek"
                />
                <BaseUIButton
                    v-if="actions.canRequest"
                    type="button"
                    variant="primary"
                    size="sm"
                    icon="IconSend"
                    :label="$t('Submit request')"
                    :processing="processing === 'request'"
                    :disabled="processing !== null"
                    @click="submitRequest"
                />
                <BaseUIButton
                    v-if="actions.canCommitDirectly"
                    type="button"
                    variant="primary"
                    size="sm"
                    icon="IconCalendarCheck"
                    :label="$t('Commit shift plan')"
                    :processing="processing === 'commit'"
                    :disabled="processing !== null"
                    @click="commitDirectly"
                />
                <BaseUIButton
                    v-if="requestHref"
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="IconFileDescription"
                    :label="$t('Go to request')"
                    @click="visit(requestHref)"
                />
                <BaseUIButton
                    v-if="violationsHref && cell.open_violations > 0"
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="IconAlertTriangle"
                    :label="$t('Open rule violations')"
                    @click="visit(violationsHref)"
                />
                <BaseUIButton
                    v-if="changesHref && cell.open_changes > 0"
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="IconListCheck"
                    :label="$t('Changes')"
                    @click="visit(changesHref)"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import { useShiftPlanRequest } from '@/Pages/ShiftPlanRequests/components/useShiftPlanRequest.js'
import { availableActions, deadlineMeta, shiftPlanFilterRange, statusMeta } from '@/Pages/Shifts/WeekStatus/weekStatus.js'

const props = defineProps({
    cell: { type: Object, required: true },
    week: { type: Object, required: true },
    craft: { type: Object, required: true },
    workflowEnabled: { type: Boolean, default: false },
    canCommit: { type: Boolean, default: false },
    canApproveRequests: { type: Boolean, default: false },
    canSeeViolations: { type: Boolean, default: false },
    canSeeChangeList: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'changed'])

const { t } = useI18n()
const page = usePage()
const { statusLabel: requestStatusLabel, statusClasses: requestStatusClasses, formatDateTime } = useShiftPlanRequest()

const meta = computed(() => statusMeta(props.cell?.status))
const deadline = computed(() => deadlineMeta(props.cell?.deadline_state))
const actions = computed(() => availableActions(props.cell, {
    workflowEnabled: props.workflowEnabled,
    canCommit: props.canCommit,
}))

const title = computed(() => `${props.craft.name} · KW ${props.week.week_number} / ${props.week.year}`)
const description = computed(() => `${props.week.monday_formatted} – ${props.week.sunday_formatted}`)

const requestHref = computed(() => {
    if (!props.cell?.request_id) return null
    return props.canApproveRequests
        ? route('shift-plan-requests.show', props.cell.request_id)
        : route('shift-plan-requests.my.show', props.cell.request_id)
})
const violationsHref = computed(() => (props.canSeeViolations ? route('shift-rules.pending') : null))
const changesHref = computed(() => (
    props.canSeeChangeList
        ? route('shifts.approvals.changes-craft', { craft: props.craft.id, filter: 'open' })
        : null
))

// 'goto' | 'request' | 'commit' | null
const processing = ref(null)
const errorMessage = ref('')

const visit = (href) => router.visit(href)

const firstError = (errors) => {
    const messages = Object.values(errors || {}).flat().filter(Boolean)
    return messages[0] ?? ''
}

// Dienstplan liest den Zeitraum aus dem gespeicherten Nutzerfilter — erst Mo–So setzen,
// dann shifts.plan öffnen (wie goToWeekInShiftPlan in useShiftPlanRequestActions.js).
const goToWeek = async () => {
    const range = shiftPlanFilterRange(props.week)
    if (!range || processing.value) return
    processing.value = 'goto'
    const user = page.props?.auth?.user
    try {
        await axios.patch(route('update.user.shift.calendar.filter.dates', user.id), {
            start_date: range.start,
            end_date: range.end,
            isDailyView: !!user.shift_plan_daily_view,
        })
    } catch (e) {
        // Filter konnte nicht gesetzt werden → Dienstplan trotzdem öffnen
    } finally {
        processing.value = null
        router.visit(route('shifts.plan'))
    }
}

const submitRequest = () => {
    if (processing.value) return
    processing.value = 'request'
    errorMessage.value = ''
    router.post(
        route('commit-shift-workflow-request.store'),
        {
            week_number: Number(props.week.week_number),
            year: Number(props.week.year),
            craft_ids: [Number(props.craft.id)],
        },
        {
            preserveScroll: true,
            onSuccess: () => emit('changed'),
            onError: (errors) => {
                errorMessage.value = firstError(errors) || t('The request could not be submitted.')
            },
            onFinish: () => {
                processing.value = null
            },
        }
    )
}

// shifts.commit antwortet ohne Inertia-Redirect → axios + anschließender Reload der Seite
// (der Flash-Toast des Backends kommt mit dem Reload an).
const commitDirectly = async () => {
    if (processing.value) return
    processing.value = 'commit'
    errorMessage.value = ''
    try {
        await axios.post(route('shifts.commit'), {
            week_number: Number(props.week.week_number),
            year: Number(props.week.year),
            craft_ids: [Number(props.craft.id)],
        })
        emit('changed')
    } catch (e) {
        errorMessage.value = e?.response?.data?.message
            || firstError(e?.response?.data?.errors)
            || t('The shift plan could not be committed.')
    } finally {
        processing.value = null
    }
}
</script>
