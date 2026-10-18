<template>
    <div class="space-y-5">
        <!-- Kennzahlen: Zeitkonto = offene Überstunden − Minusstunden -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3" :class="showPayoutFigures ? 'lg:grid-cols-5' : ''">
            <div class="rounded-lg border border-border-subtle px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Time account') }}</p>
                <p class="mt-1 text-xl font-semibold" :class="signClass(local.balance_minutes)">{{ local.balance_formatted }}</p>
                <p class="mt-0.5 text-[10px] text-text-subtle">{{ $t('= open overtime − minus hours') }}</p>
            </div>
            <div class="rounded-lg border border-border-subtle px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Open overtime') }}</p>
                <p class="mt-1 text-xl font-semibold text-text">{{ local.overtime_formatted }}</p>
            </div>
            <div class="rounded-lg border border-border-subtle px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Minus hours') }}</p>
                <p class="mt-1 text-xl font-semibold" :class="local.debt_minutes > 0 ? 'text-danger' : 'text-text'">{{ local.debt_formatted }}</p>
            </div>
            <template v-if="showPayoutFigures">
                <div class="rounded-lg border px-4 py-3"
                     :class="local.payable_minutes > 0 ? 'border-danger-border bg-danger-surface/40' : 'border-border-subtle'">
                    <p class="text-[11px] uppercase tracking-wide" :class="local.payable_minutes > 0 ? 'text-danger' : 'text-text-subtle'">
                        {{ $t('Deadline expired') }}
                    </p>
                    <p class="mt-1 text-xl font-semibold" :class="local.payable_minutes > 0 ? 'text-danger' : 'text-text'">
                        {{ local.payable_formatted }}
                    </p>
                </div>
                <div class="rounded-lg border border-border-subtle px-4 py-3">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $t('Paid out') }}</p>
                    <p class="mt-1 text-xl font-semibold text-text">{{ local.paid_out_formatted }}</p>
                </div>
            </template>
        </div>

        <!-- Regel -->
        <p v-if="local.rule_without_period" class="flex items-start gap-1.5 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
            <PropertyIcon name="IconAlertTriangle" class="size-4 shrink-0" />
            {{ $t('The overtime rule is active, but no compensation period is stored in the contract – overtime has no deadline and cannot become payable.') }}
        </p>
        <p v-else-if="local.rule_active" class="text-xs text-text-muted">
            {{ $t('Overtime rule active: overtime not compensated within {n} days can be paid out.', { n: local.compensation_period }) }}
        </p>
        <p v-else class="text-xs text-text-muted">
            {{ $t('No overtime rule in the contract: overview of how overtime and minus hours accrued and were compensated – without deadlines or payout.') }}
            <template v-if="local.payable_minutes > 0">
                {{ $t('{hours} come from an earlier contract period with an overtime rule and have passed their deadline; they can only be paid out while an overtime rule is active.', { hours: local.payable_formatted }) }}
            </template>
        </p>

        <!-- Gespeicherter Kontostand weicht von Buchungen + Auszahlungen ab (Altdaten) -->
        <p v-if="local.account_difference_minutes !== 0" class="flex items-start gap-1.5 rounded-lg border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
            <PropertyIcon name="IconAlertTriangle" class="size-4 shrink-0" />
            {{ $t('The stored time account differs by {diff} from the sum of all bookings and payouts (legacy data). The values here are calculated from the bookings.', { diff: local.account_difference_formatted }) }}
        </p>

        <!-- Auszahlung: nur bei aktiver Regel und abgelaufenen Überstunden -->
        <div v-if="!readOnly && local.can_pay_out && local.payable_now_minutes > 0" class="rounded-lg border border-border-subtle p-3">
            <h4 class="text-sm font-semibold text-text mb-1">{{ $t('Pay out overtime') }}</h4>
            <p class="text-[11px] text-text-subtle mb-3">
                {{ $t('Up to {max} can be paid out (expired overtime, at most the positive time account). The booking reduces the time account; the actual payment happens outside artwork.', { max: local.payable_now_formatted }) }}
            </p>
            <div class="flex flex-wrap items-start gap-3">
                <div class="w-28">
                    <BaseInput id="overtime-payout-hours" v-model="form.hours" type="number" :label="$t('Hours')" is-small />
                </div>
                <div class="w-28">
                    <BaseInput id="overtime-payout-minutes" v-model="form.minutes" type="number" :label="$t('Minutes')" is-small />
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <BaseInput id="overtime-payout-comment" v-model="form.comment" type="text" :label="$t('Comment')" is-small />
                </div>
                <BaseUIButton :label="$t('Pay out')" :use-translation="false" icon="IconCash"
                              :disabled="submitting || totalMinutes < 1 || totalMinutes > local.payable_now_minutes"
                              @click="submitPayout" />
            </div>
            <p v-if="totalMinutes > local.payable_now_minutes" class="mt-1 text-xs text-warning">
                {{ $t('The amount exceeds the payable overtime.') }}
            </p>
            <p v-if="error" class="mt-1 text-xs text-danger">{{ error }}</p>
        </div>

        <!-- Verlauf je Monat -->
        <div>
            <h4 class="text-sm font-semibold text-text mb-2">{{ $t('History') }}</h4>
            <p v-if="!local.months.length" class="text-sm text-text-subtle italic">{{ $t('No overtime recorded.') }}</p>
            <div v-for="month in local.months" :key="month.key" class="mb-2 rounded-lg border border-border-subtle">
                <button type="button" class="flex w-full items-center justify-between gap-3 rounded-t-lg bg-surface-sunken px-3 py-2 text-left"
                        @click="toggleMonth(month.key)">
                    <span class="flex items-center gap-2 text-sm font-semibold text-text">
                        <PropertyIcon :name="isOpen(month.key) ? 'IconChevronDown' : 'IconChevronRight'" class="size-4" />
                        {{ month.label }}
                    </span>
                    <span class="flex flex-wrap items-center justify-end gap-3 text-xs">
                        <span class="text-success">{{ month.plus_formatted }}</span>
                        <span class="text-danger">{{ month.minus_formatted }}</span>
                        <span v-if="month.payouts > 0" class="text-text-muted">{{ $t('Payouts') }} {{ month.payouts_formatted }}</span>
                        <span class="text-text-muted">{{ $t('Balance at month end') }}: <strong :class="signClass(month.end_balance)">{{ month.end_balance_formatted }}</strong></span>
                    </span>
                </button>
                <div v-if="isOpen(month.key)" class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="text-left text-text-subtle">
                                <th class="px-3 py-2 font-medium">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 font-medium">{{ $t('Change') }}</th>
                                <th class="px-3 py-2 font-medium">{{ $t('Details') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ $t('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="row in month.rows" :key="row.type + row.date + '-' + (row.payout_id ?? '')">
                                <td class="px-3 py-2 text-text-muted whitespace-nowrap">{{ formatDate(row.date) }}</td>
                                <td class="px-3 py-2 font-medium whitespace-nowrap" :class="signClass(row.change)">{{ row.change_formatted }}</td>
                                <td class="px-3 py-2 text-text-muted">
                                    <!-- Plustag -->
                                    <template v-if="row.type === 'plus'">
                                        <div v-if="row.repaid_debts.length">{{ $t('Compensates minus hours of') }} {{ listParts(row.repaid_debts) }}</div>
                                        <div v-if="row.overtime > 0" class="flex flex-wrap items-center gap-1.5">
                                            <span>{{ $t('Overtime') }} {{ row.overtime_formatted }}</span>
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
                                            <span v-if="row.remaining > 0 && row.remaining < row.overtime">{{ $t('Remaining') }} {{ row.remaining_formatted }}</span>
                                            <span v-if="row.deadline" :class="row.status === 'payable' ? 'text-danger' : ''">· {{ $t('Deadline') }} {{ formatDate(row.deadline) }}</span>
                                        </div>
                                        <div v-for="(use, idx) in row.used_by" :key="idx" class="text-text-subtle">
                                            {{ use.type === 'payout' ? $t('Paid out on') : $t('Compensated on') }} {{ formatDate(use.date) }} ({{ use.formatted }})
                                        </div>
                                    </template>
                                    <!-- Minustag -->
                                    <template v-else-if="row.type === 'minus'">
                                        <div v-if="row.compensated.length">{{ $t('Reduces overtime of') }} {{ listParts(row.compensated) }}</div>
                                        <div v-if="row.debt_created > 0" class="flex flex-wrap items-center gap-1.5">
                                            <span>{{ $t('Minus hours') }} {{ row.debt_created_formatted }}</span>
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                                  :class="row.debt_remaining > 0 ? 'bg-danger-surface text-danger' : 'bg-success-surface text-success'">
                                                {{ row.debt_remaining > 0 ? $t('Open') : $t('Compensated') }}
                                            </span>
                                            <span v-if="row.debt_remaining > 0 && row.debt_remaining < row.debt_created">{{ $t('Remaining') }} {{ row.debt_remaining_formatted }}</span>
                                        </div>
                                        <div v-for="(repay, idx) in row.repaid_by" :key="idx" class="text-text-subtle">
                                            {{ $t('Compensated on') }} {{ formatDate(repay.date) }} ({{ repay.formatted }})
                                        </div>
                                    </template>
                                    <!-- Auszahlung -->
                                    <template v-else>
                                        <div>
                                            {{ $t('Overtime payout') }}<template v-if="row.created_by"> · {{ row.created_by }}</template><template v-if="row.comment"> · {{ row.comment }}</template>
                                        </div>
                                        <div v-if="row.used.length" class="text-text-subtle">{{ $t('From overtime of') }} {{ listParts(row.used) }}</div>
                                        <div v-if="row.debt_created > 0" class="flex flex-wrap items-center gap-1.5">
                                            <span>{{ $t('Minus hours') }} {{ row.debt_created_formatted }}</span>
                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                                  :class="row.debt_remaining > 0 ? 'bg-danger-surface text-danger' : 'bg-success-surface text-success'">
                                                {{ row.debt_remaining > 0 ? $t('Open') : $t('Compensated') }}
                                            </span>
                                        </div>
                                        <div v-for="(repay, idx) in row.repaid_by" :key="idx" class="text-text-subtle">
                                            {{ $t('Compensated on') }} {{ formatDate(repay.date) }} ({{ repay.formatted }})
                                        </div>
                                    </template>
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap" :class="signClass(row.balance_after)">{{ row.balance_after_formatted }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'
import { failedRequestMessage } from '@/Helper/appToast.js'
import { useI18n } from 'vue-i18n'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    userId: { type: Number, required: true },
    data: { type: Object, required: true },
    // Selbstansicht ("Meine Zahlen"): keine Auszahlung buchen
    readOnly: { type: Boolean, default: false },
})

const { t } = useI18n()

const local = ref({ ...props.data })
watch(() => props.data, (v) => { local.value = { ...v } })
// Neu entstandene Monate (z. B. nach einer Auszahlung) ebenfalls aufklappen
watch(() => local.value.months, (months, previous) => {
    const known = new Set((previous ?? []).map(m => m.key))
    const added = (months ?? []).filter(m => !known.has(m.key)).map(m => m.key)
    if (added.length) openMonths.value = new Set([...openMonths.value, ...added])
})

// Frist-/Auszahlungskennzahlen nur, wenn die Regel aktiv ist oder es schon Auszahlungen/Fristabläufe gab
const showPayoutFigures = computed(() => local.value.rule_active || local.value.paid_out_minutes > 0 || local.value.payable_minutes > 0)

// Aktueller und Vormonat sind aufgeklappt, ältere Monate zu
const openMonths = ref(new Set((props.data.months ?? []).slice(0, 2).map(m => m.key)))
const isOpen = (key) => openMonths.value.has(key)
const toggleMonth = (key) => {
    const next = new Set(openMonths.value)
    next.has(key) ? next.delete(key) : next.add(key)
    openMonths.value = next
}

const form = reactive({ hours: 0, minutes: 0, comment: '' })
const submitting = ref(false)
const error = ref('')

const totalMinutes = computed(() => (Number(form.hours) || 0) * 60 + (Number(form.minutes) || 0))

const submitPayout = async () => {
    error.value = ''
    if (totalMinutes.value < 1) return
    submitting.value = true
    try {
        const res = await axios.post(route('user.overtime.payout', { user: props.userId }), {
            minutes: totalMinutes.value,
            comment: form.comment || null,
        }, { skipErrorToast: true }) // Fehler steht im Panel
        local.value = res.data
        form.hours = 0
        form.minutes = 0
        form.comment = ''
    } catch (e) {
        error.value = failedRequestMessage(e)
    } finally {
        submitting.value = false
    }
}

const statusLabel = (status) => {
    const map = {
        open: t('Open'),
        compensated: t('Compensated'),
        payable: t('Deadline expired'),
        paid_out: t('Paid out'),
    }
    return map[status] || status
}

const statusClass = (status) => {
    const map = {
        open: 'bg-accent-100 text-accent-700',
        compensated: 'bg-success-surface text-success',
        payable: 'bg-danger-surface text-danger',
        paid_out: 'bg-surface-sunken text-text-muted',
    }
    return map[status] || 'bg-surface-sunken text-text-muted'
}

const signClass = (minutes) => (minutes > 0 ? 'text-success' : minutes < 0 ? 'text-danger' : 'text-text')

const listParts = (parts) => parts.map(p => `${formatDate(p.date)} (${p.formatted})`).join(', ')

const formatDate = (value) => {
    if (!value) return '-'
    const [year, month, day] = String(value).split('-')
    return day && month && year ? `${day}.${month}.${year}` : value
}
</script>
