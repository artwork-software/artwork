<template>
    <ArtworkBaseModal :title="title" :description="description" modal-size="sm:max-w-3xl" @close="$emit('close')">
        <div class="flex max-h-[68vh] flex-col gap-5 overflow-y-auto text-[13px]">
            <!-- Abo: Vertrag, bezahlte Zeiträume und was das Haus von Hand tun kann -->
            <section v-if="subscription" class="flex flex-col gap-4">
                <dl class="grid grid-cols-3 gap-2">
                    <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                        <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Price') }}</dt>
                        <dd class="text-[15px] font-semibold tabular-nums text-text">
                            {{ formatEuro(subscription.priceCents) }}
                            <span class="text-xs font-normal text-text-subtle">{{ subscription.paymentOption === 'monthly' ? $t('per month') : $t('once') }}</span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                        <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Paid until') }}</dt>
                        <dd class="text-[15px] font-semibold tabular-nums text-text">{{ subscription.currentPeriodEnd ? formatDate(subscription.currentPeriodEnd) : '–' }}</dd>
                    </div>
                    <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                        <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Status') }}</dt>
                        <dd class="pt-0.5"><BaseChip :variant="state.variant">{{ state.label }}</BaseChip></dd>
                    </div>
                </dl>

                <div v-if="mayManage && running" class="flex flex-col gap-3 rounded-lg border border-border-subtle px-4 py-3">
                    <!-- Pausieren: bis zu einem Tag, mit Abkürzungen in Wochen -->
                    <template v-if="confirming === 'pause'">
                        <div class="flex flex-wrap items-end gap-2">
                            <button v-for="weeks in [2, 4, 8]" :key="weeks" type="button" class="ui-button-small" @click="resumesOn = inDays(weeks * 7)">
                                {{ $t('{weeks} weeks', { weeks }) }}
                            </button>
                            <BaseInput id="subscription-resumes-on" v-model="resumesOn" type="date" :min="inDays(1)" :max="inDays(365)" label="Usable again from" class="!w-56" />
                        </div>
                        <p class="text-text-muted">
                            {{ prepaid
                                ? $t('This subscription is already paid: the pause only extends the pass, which is blocked until then. Performances during the pause cannot be made up. The holder gets an e-mail.')
                                : $t('Until then the pass is blocked. The next charge and every later date move back by the length of the pause. The holder gets an e-mail.') }}
                        </p>
                        <div class="flex flex-wrap justify-end gap-2">
                            <button type="button" class="ui-button" @click="confirming = null">{{ $t('Cancel') }}</button>
                            <button type="button" class="ui-button-add" :disabled="busy || !resumesOn" @click="change({ kind: 'pause', resumes_on: resumesOn })">{{ $t('Pause') }}</button>
                        </div>
                    </template>

                    <div v-else-if="confirming === 'cancel'" class="flex flex-wrap items-center gap-2">
                        <span class="flex-1">{{ $t('Cancel this subscription? It ends with the paid period, never inside the minimum term. The holder gets the confirmation by e-mail.') }}</span>
                        <button type="button" class="ui-button" @click="confirming = null">{{ $t('Keep') }}</button>
                        <button type="button" class="ui-button-add" :disabled="busy" @click="change({ kind: 'cancel' })">{{ $t('Cancel subscription') }}</button>
                    </div>

                    <div v-else-if="confirming === 'endNow'" class="flex flex-col gap-2 rounded-lg border border-danger-border bg-danger-surface px-3 py-3">
                        <p class="text-text">{{ $t('End this subscription now? The pass stops working at once and nothing more is charged. Nothing already paid is refunded automatically — settle that directly with the holder.') }}</p>
                        <div class="flex flex-wrap justify-end gap-2">
                            <button type="button" class="ui-button" @click="confirming = null">{{ $t('Keep') }}</button>
                            <button type="button" class="ui-button-delete" :disabled="busy" @click="change({ kind: 'endNow' })">{{ $t('End at once') }}</button>
                        </div>
                    </div>

                    <div v-else class="flex flex-wrap gap-2">
                        <button v-if="pausable" type="button" class="ui-button" @click="openPause"><IconPlayerPause class="size-3.5" />{{ $t('Pause') }}</button>
                        <button v-if="paused" type="button" class="ui-button" :disabled="busy" @click="change({ kind: 'resume' })"><IconPlayerPlay class="size-3.5" />{{ $t('End pause') }}</button>
                        <button v-if="cancellable" type="button" class="ui-button" @click="confirming = 'cancel'">{{ $t('Cancel subscription') }}</button>
                        <button v-if="withdrawable" type="button" class="ui-button" :disabled="busy" @click="change({ kind: 'withdrawCancellation' })">{{ $t('Withdraw cancellation') }}</button>
                        <button type="button" class="ui-button-delete" @click="confirming = 'endNow'">{{ $t('End at once') }}</button>
                    </div>

                    <p v-if="error" class="text-danger">{{ error }}</p>
                </div>

                <div>
                    <p class="mb-1.5 font-lexend text-xs font-medium text-text">{{ $t('Paid periods') }}</p>
                    <p v-if="!subscription.periods.length" class="text-text-subtle">{{ $t('No period paid yet.') }}</p>
                    <ol v-else class="divide-y divide-border-hairline">
                        <li v-for="period in subscription.periods" :key="period.index" class="grid grid-cols-[2rem_1fr_auto] gap-x-3 py-2 tabular-nums">
                            <span class="text-text-subtle">{{ period.index }}.</span>
                            <span>
                                {{ formatDate(period.startsAt) }} – {{ formatDate(period.endsAt) }}
                                <span class="block text-xs text-text-subtle">
                                    {{ $t('+{granted} uses', { granted: period.usesGranted }) }}<template v-if="period.usesCarried"> · {{ $t('{count} carried', { count: period.usesCarried }) }}</template><template v-if="period.usesForfeited"> · {{ $t('{count} lapsed', { count: period.usesForfeited }) }}</template>
                                </span>
                            </span>
                            <span class="text-right">{{ period.paidCents === null ? period.orderNumber ?? '–' : formatEuro(period.paidCents) }}</span>
                        </li>
                    </ol>
                </div>
            </section>

            <TicketingCardPanel v-if="passId" :key="cardKey" :pass-id="passId" :may-manage="mayManage" :subscription="Boolean(subscription || pass?.subscriptionId)" @changed="$emit('changed')" />
        </div>

        <template #footer>
            <button type="button" class="ui-button-add" @click="$emit('close')">{{ $t('Close') }}</button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { IconPlayerPause, IconPlayerPlay } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import TicketingCardPanel from '@/Pages/Ticketing/Components/TicketingCardPanel.vue'
import { formatDate, formatEuro } from '@/Helper/ticketingFormat.js'
import { subscriptionState } from '@/Pages/Ticketing/Components/holderState.js'

/* One row of "Karten & Abos" opened: a Mehrfachkarte, or an Abo with its card. Who may manage passes
   corrects the card and pauses, cancels or ends the Abo here; Artwork-Tickets keeps the rules and mails
   the holder. The page reads the row again after every change. */
const props = defineProps({
    pass: { type: Object, default: null },
    subscription: { type: Object, default: null },
    mayManage: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'changed'])

const { t } = useI18n()

const confirming = ref(null)
const resumesOn = ref('')
const busy = ref(false)
const error = ref('')
/* A changed Abo moves its card's dates too; a new key reads the card again. */
const cardKey = ref(0)

const holder = computed(() => props.subscription ?? props.pass)
const title = computed(() => holder.value.holderName)
const description = computed(() => (props.subscription
    ? `${props.subscription.planName} · ${props.subscription.holderEmail}`
    : `${props.pass.name} · ${props.pass.holderEmail}`))
const passId = computed(() => props.subscription?.passId ?? props.pass?.id ?? null)

const state = computed(() => subscriptionState(props.subscription, t))
const running = computed(() => ['active', 'past_due'].includes(props.subscription?.status))
const paused = computed(() => props.subscription?.pausedUntil && dayjs(props.subscription.pausedUntil).isAfter(dayjs()))
const pausable = computed(() => props.subscription?.status === 'active'
    && !paused.value
    && props.subscription.currentPeriodEnd
    && dayjs(props.subscription.currentPeriodEnd).isAfter(dayjs()))
/* A term that ends by itself needs no cancelling. */
const cancellable = computed(() => props.subscription?.afterTerm !== 'ends' && !props.subscription?.cancelAt)
/* A cancellation by the holder or the house, not the term's own end. */
const withdrawable = computed(() => ['buyer', 'house'].includes(props.subscription?.cancelledBy))
const prepaid = computed(() => props.subscription?.paymentOption === 'upfront' || Boolean(props.subscription?.seasonEndsAt))

function inDays(days) {
    return dayjs().add(days, 'day').format('YYYY-MM-DD')
}

function openPause() {
    resumesOn.value = inDays(28)
    confirming.value = 'pause'
}

async function change(command) {
    busy.value = true
    error.value = ''
    try {
        await axios.post(route('ticketing.section.subscription.change', props.subscription.id), command)
        confirming.value = null
        cardKey.value += 1
        emit('changed')
    } catch (failure) {
        error.value = failure.response?.data?.message ?? t('Artwork-Tickets could not be reached.')
    } finally {
        busy.value = false
    }
}
</script>
