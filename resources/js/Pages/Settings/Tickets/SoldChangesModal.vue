<template>
    <ArtworkBaseModal :title="$t('Save changes to {name}?', { name: passType.name })"
                      :description="subscription
                          ? $t('{count} subscriptions are running.', { count: passType.issuedCount }, passType.issuedCount)
                          : $t('{count} passes are already sold.', { count: passType.issuedCount }, passType.issuedCount)"
                      modal-size="sm:max-w-xl" z-index="999999999999999999999" @close="$emit('close')">
        <div class="grid gap-4 text-[13px] leading-5">
            <div v-for="block in blocks" :key="block.key" class="grid gap-2.5 rounded-md border px-3.5 py-3" :class="block.tone">
                <p class="font-lexend text-xs font-semibold text-text">{{ block.heading }}</p>
                <dl class="grid gap-1.5">
                    <div v-for="row in block.rows" :key="row.label" class="grid gap-x-3 sm:grid-cols-[190px_minmax(0,1fr)]">
                        <dt class="text-text-subtle">{{ row.label }}</dt>
                        <dd v-if="row.from === row.to" class="text-text">{{ $t('Changed') }}</dd>
                        <dd v-else class="flex flex-wrap items-baseline gap-x-1.5">
                            <span class="text-text-subtle line-through decoration-text-subtle/60">{{ row.from }}</span>
                            <IconArrowRight class="size-3.5 shrink-0 self-center text-text-subtle" aria-hidden="true" />
                            <span class="font-medium text-text">{{ row.to }}</span>
                        </dd>
                    </div>
                </dl>
                <p v-if="block.hint" class="text-xs text-text-subtle">{{ block.hint }}</p>
            </div>
        </div>
        <template #footer>
            <button type="button" class="ui-button" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" @click="$emit('confirm')">{{ $t('Save changes') }}</button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconArrowRight } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import { formatDate, formatDateTime, formatEuro } from '@/Helper/ticketingFormat.js'
import { soldPassChanges } from '@/Pages/Settings/Tickets/drafts.js'
import { validFor } from '@/Pages/Settings/Tickets/discounts.js'

/* Before a sold pass type is saved: what changes, from what to what, and whether it reaches the
   passes already sold or only new buyers. */
const props = defineProps({
    passType: { type: Object, required: true },
    /** passTypePayload before and after the edit. */
    before: { type: Object, required: true },
    after: { type: Object, required: true },
    productions: { type: Array, required: true },
    groups: { type: Array, required: true },
})
defineEmits(['close', 'confirm'])

const { t } = useI18n()
const subscription = computed(() => props.passType.kind === 'subscription')
const changes = computed(() => soldPassChanges(props.before, props.after))

const yesNo = (on) => (on ? t('Yes') : t('No'))
const atMost = (count) => count ?? t('Unlimited')
const until = (date, format) => (date ? t('until {date}', { date: format(date) }) : t('Unlimited'))
const periods = { day: 'per day', week: 'per week', month: 'per month' }

/** Each label's name and how a payload shows it. */
const fields = {
    name: ['Name', (p) => p.name],
    uses: ['Uses', (p) => {
        if (p.uses === null) return t('Unlimited')
        const amount = `${p.uses} ${t(!subscription.value ? 'uses' : p.usesWindow === 'month' ? 'uses per month' : 'uses in total')}`
        return p.rolloverMax > 0 ? `${amount} · ${t('Carried into the next month')}: ${p.rolloverMax}` : amount
    }],
    validity: ['Validity', (p) => (p.validityDays ? `${p.validityDays} ${t('Days from purchase')}` : until(p.validUntil, formatDateTime))],
    discount: ['Discount per use', (p) => (p.discountKind === 'percent'
        ? `${p.discountPercent ?? 0} %`
        : t('{amount} per ticket', { amount: formatEuro(p.discountAmount ?? 0) }))],
    perOrder: ['At most per order', (p) => atMost(p.maxUsesPerOrder)],
    perEvent: ['At most per event', (p) => atMost(p.maxUsesPerEvent)],
    perPeriod: ['At most', (p) => (p.maxUsesPerPeriod ? `${p.maxUsesPerPeriod} ${t(periods[p.limitPeriod])}` : t('Unlimited'))],
    redeemable: ['Redeemable in the checkout', (p) => yesNo(p.redeemableInCheckout)],
    stampable: ['Stampable', (p) => yesNo(p.stampable)],
    scope: ['Valid for', (p) => validFor(p, props.productions, props.groups, t) || t('None')],
    price: ['Price', (p) => (subscription.value
        ? [p.monthlyPrice && `${formatEuro(p.monthlyPrice)} ${t('per month')}`, p.upfrontPrice && `${t('One-off')} ${formatEuro(p.upfrontPrice)}`]
            .filter(Boolean).join(' · ')
        : formatEuro(p.price ?? 0))],
    term: ['Term', (p) => ({
        open: t('Open-ended'),
        months: `${p.termMonths} ${t('Months')}`,
        season: until(p.seasonEnds, formatDate),
    })[p.termMode]],
    sale: ['On sale until', (p) => until(p.saleEnds, formatDate)],
    // Long text: only that it changed.
    description: ['Description', () => ''],
}

const usesChange = computed(() => changes.value.sold.includes('uses'))

const blocks = computed(() => [
    { key: 'sold', tone: 'border-warning-border bg-warning-surface',
      heading: subscription.value ? t('Also for every running subscription') : t('Also for every pass already sold'),
      hint: !usesChange.value ? null : subscription.value
          ? t('Running subscriptions get the new uses from their next period.')
          : t('Each pass sold moves by the same difference, never below what it has used.') },
    { key: 'newOnly', tone: 'border-border-subtle bg-surface-sunken',
      heading: subscription.value ? t('Only for new subscriptions') : t('Only for new sales') },
].map((block) => ({
    ...block,
    rows: changes.value[block.key].map((key) => {
        const [label, show] = fields[key]
        return { label: t(label), from: show(props.before), to: show(props.after) }
    }),
})).filter((block) => block.rows.length > 0))
</script>
