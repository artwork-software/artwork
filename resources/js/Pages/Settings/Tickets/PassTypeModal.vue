<template>
    <ArtworkBaseModal :title="title" :description="subscription ? $t('Term, prices and what the subscription is worth per ticket.') : $t('Price, uses and what one use is worth.')"
                      modal-size="sm:max-w-3xl" @close="$emit('close')">
        <div class="flex flex-col gap-6 text-[13px]">
            <p v-if="sold" class="rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                {{ subscription
                    ? $t('Already sold: what you change also applies to every running subscription, uses from their next period. Only prices, term and whether uses count per month or per term apply to new subscriptions alone.')
                    : $t('Already sold: what you change also applies to every pass sold. Only the price applies to new sales alone.') }}
                {{ $t('Before saving you see exactly what changes.') }}
            </p>

            <section class="grid gap-4">
                <BaseInput id="pass-name" v-model="draft.name" label="Name" required :error="form.errors.name" maxlength="120"
                           :placeholder="subscription ? $t('e.g. Theatre subscription') : $t('e.g. 10-visit pass')" />
                <div>
                    <BaseTextarea id="pass-description" v-model="draft.description" label="Description" :maxlength="4000" rows="3" />
                    <p class="mt-1 text-xs text-text-subtle">{{ $t('Shown on the pass in the shop.') }}</p>
                </div>
            </section>

            <section v-if="!subscription" :class="sectionClass">
                <h3 :class="headingClass">{{ $t('Price') }}</h3>
                <BaseInput id="pass-price" v-model="draft.price" type="number" min="0" step="0.01" label="Price in euros" required :error="form.errors.price" class="sm:w-1/2" />
            </section>

            <template v-else>
                <section :class="sectionClass">
                    <h3 id="pass-term-heading" :class="headingClass">{{ $t('Term') }}</h3>
                    <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-labelledby="pass-term-heading">
                        <label v-for="mode in termModes" :key="mode.value" :for="`pass-term-${mode.value}`"
                               class="flex items-start gap-3 rounded-lg border px-3.5 py-3 transition-colors"
                               :class="[draft.termMode === mode.value ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface cursor-pointer hover:bg-surface-sunken']">
                            <input :id="`pass-term-${mode.value}`" v-model="draft.termMode" type="radio" name="pass-term" :value="mode.value" class="sr-only" @change="onTermMode" />
                            <span class="mt-0.5 box-border size-4 shrink-0 rounded-full border bg-surface" :class="draft.termMode === mode.value ? 'border-[5px] border-accent-600' : 'border-border'"></span>
                            <span class="flex flex-col gap-0.5">
                                <span class="font-lexend text-[13px] font-medium text-text">{{ mode.label }}</span>
                                <span class="text-[12.5px] leading-[18px] text-text-muted">{{ mode.hint }}</span>
                            </span>
                        </label>
                    </div>

                    <div v-if="draft.termMode === 'months'" class="grid gap-4 sm:grid-cols-2">
                        <BaseInput id="pass-term-months" v-model="draft.termMonths" type="number" min="1" max="24" label="Months" required :error="form.errors.termMonths" />
                        <div>
                            <span :class="labelClass">{{ $t('After the term') }}</span>
                            <ChoiceSegments v-model="draft.afterTerm" :label="$t('After the term')"
                                            :options="[{ value: 'ends', label: $t('Ends') }, { value: 'monthly', label: $t('Runs on monthly') }]" />
                        </div>
                    </div>
                    <div v-else-if="draft.termMode === 'season'" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <BaseInput id="pass-season-ends" v-model="draft.seasonEnds" type="date" label="Last day" required :error="form.errors.seasonEnds" />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('On sale while at least one whole month is left until then.') }}</p>
                        </div>
                    </div>
                </section>

                <section :class="sectionClass">
                    <h3 :class="headingClass">{{ $t('Prices') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <BaseInput id="pass-monthly" v-model="draft.monthlyPrice" type="number" min="0.5" step="0.01" label="Price per month in euros" :error="form.errors.monthlyPrice" />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('Empty: no monthly payment.') }}</p>
                        </div>
                        <div v-if="draft.termMode !== 'open'">
                            <BaseInput id="pass-upfront" v-model="draft.upfrontPrice" type="number" min="0.5" step="0.01" label="One-off price in euros" :error="form.errors.upfrontPrice" />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('For the whole term, paid once. Empty: no one-off payment.') }}</p>
                        </div>
                    </div>
                    <p v-if="sold" class="text-xs text-text-subtle">{{ $t('New prices apply to new subscriptions; running ones keep theirs.') }}</p>
                </section>
            </template>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ $t('Uses') }}</h3>
                <div class="grid gap-2" role="radiogroup" :aria-label="$t('Uses')">
                    <div v-for="mode in usesModes" :key="mode.value" class="rounded-lg border transition-colors"
                         :class="[usesMode === mode.value ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface', { 'opacity-60': mode.disabled }]">
                        <label class="flex items-start gap-2.5 px-3 py-2.5" :class="mode.disabled ? 'cursor-not-allowed' : 'cursor-pointer'">
                            <input type="radio" name="pass-uses-mode" class="mt-0.5 size-3.5 text-accent-600 focus:ring-accent-500" :value="mode.value"
                                   :checked="usesMode === mode.value" :disabled="mode.disabled" @change="chooseUses(mode.value)">
                            <span class="grid gap-0.5">
                                <span class="font-medium text-text">{{ mode.label }}</span>
                                <span class="text-xs text-text-subtle">{{ mode.hint }}</span>
                            </span>
                        </label>
                        <div v-if="usesMode === mode.value && mode.unit" class="flex flex-col gap-3 pb-3 pl-9 pr-3">
                            <div class="flex items-center gap-2">
                                <BaseInput id="pass-uses" v-model="draft.uses" type="number" min="1" max="1000" :label="mode.unit" :show-label="false"
                                           required :error="form.errors.uses" is-small class="!w-24" />
                                <span class="text-text-muted">{{ mode.unit }}</span>
                            </div>
                            <div v-if="mode.value === 'month'">
                                <div class="flex items-center gap-2">
                                    <BaseInput id="pass-rollover" v-model="draft.rolloverMax" type="number" min="0" max="100" label="Carried into the next month"
                                               :show-label="false" :error="form.errors.rolloverMax" is-small class="!w-24" />
                                    <span class="text-text-muted">{{ $t('unused ones carried into the next month') }}</span>
                                </div>
                                <p class="mt-1 text-xs text-text-subtle">{{ $t('0: whatever is not used in the month lapses.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <span :class="labelClass">{{ $t('At most') }}</span>
                    <div class="flex items-center gap-2">
                        <BaseInput id="pass-max-period" v-model="draft.maxUsesPerPeriod" type="number" min="1" max="100" label="At most" :show-label="false"
                                   :error="form.errors.maxUsesPerPeriod" is-small class="!w-24" />
                        <ChoiceSegments v-model="draft.limitPeriod" :label="$t('Period')" :options="limitPeriods" />
                    </div>
                    <p class="mt-1 text-xs text-text-subtle">{{ $t('Optional. Weeks run Monday to Sunday. A booking counts on the day of the performance, a stamp on the day it is given.') }}</p>
                </div>
                <div v-if="draft.unlimited && noPace" class="flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                    <IconAlertTriangle class="mt-0.5 size-4 shrink-0 text-warning" />
                    <span>{{ $t('Without a limit the pass can be used any number of times, even for several dates on the same day. Best set one, e.g. 1 per week.') }}</span>
                </div>
                <p class="text-xs text-text-subtle">{{ $t('One use = one ticket, reduced by the value below.') }}</p>
            </section>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ $t('Discount per use') }}</h3>
                <div>
                    <div class="flex flex-wrap items-end gap-4">
                        <ChoiceSegments v-model="draft.discountKind" :label="$t('Discount per use')"
                                        :options="[{ value: 'percent', label: $t('Percent of the ticket') }, { value: 'fixed', label: $t('Fixed amount') }]" />
                        <div class="w-44">
                            <BaseInput v-if="draft.discountKind === 'percent'" id="pass-percent" v-model="draft.discountPercent" type="number" min="1" max="100"
                                       label="Percent" required :error="form.errors.discountPercent" />
                            <BaseInput v-else id="pass-amount" v-model="draft.discountAmount" type="number" min="0.01" step="0.01"
                                       label="Amount in euros" required :error="form.errors.discountAmount" />
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-text-subtle">
                        {{ draft.discountKind === 'percent' ? $t('100 % = full admission.') : $t('Per use, gross; never more than the ticket costs.') }}
                    </p>
                </div>
            </section>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ $t('Redeeming and limits') }}</h3>
                <div class="grid gap-3">
                    <ArtworkBaseToggle id="pass-redeemable" v-model="draft.redeemableInCheckout" :label="$t('Redeemable in the checkout')"
                                       :description="$t('Signed-in customers can use the pass when buying tickets.')" is-small />
                    <ArtworkBaseToggle id="pass-stampable" v-model="draft.stampable" :label="$t('Stampable')"
                                       :description="$t('The house can stamp uses under customers in Artwork-Tickets, like a punch card.')" is-small />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <BaseInput id="pass-max-order" v-model="draft.maxUsesPerOrder" type="number" min="1" max="100" label="At most per order" :error="form.errors.maxUsesPerOrder" />
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('Empty: no limit.') }}</p>
                    </div>
                    <div>
                        <BaseInput id="pass-max-event" v-model="draft.maxUsesPerEvent" type="number" min="1" max="1000" label="At most per event" :error="form.errors.maxUsesPerEvent" />
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('Across all orders of this pass. Empty: no limit.') }}</p>
                    </div>
                </div>
            </section>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ subscription ? $t('Sale') : $t('Validity and sale') }}</h3>
                <div v-if="!subscription" class="flex flex-wrap items-end gap-4">
                    <div>
                        <span :class="labelClass">{{ $t('Validity') }}</span>
                        <ChoiceSegments v-model="draft.validity" :label="$t('Validity')"
                                        :options="[{ value: 'unlimited', label: $t('Unlimited') }, { value: 'days', label: $t('Days from purchase') }, { value: 'until', label: $t('Until a fixed date') }]" />
                    </div>
                    <div v-if="draft.validity !== 'unlimited'" class="w-60">
                        <BaseInput v-if="draft.validity === 'days'" id="pass-validity-days" v-model="draft.validityDays" type="number" min="1" max="3650"
                                   label="Days from purchase" required :error="form.errors.validityDays" />
                        <BaseInput v-else id="pass-valid-until" v-model="draft.validUntil" type="datetime-local"
                                   label="Valid until" required :error="form.errors.validUntil" />
                    </div>
                </div>
                <div class="w-60">
                    <BaseInput id="pass-sale-ends" v-model="draft.saleEnds" type="date" label="On sale until" :error="form.errors.saleEnds" />
                    <p class="mt-1 text-xs text-text-subtle">{{ $t('Off the shop from the day after. Empty: as long as it is published.') }}</p>
                </div>
            </section>

            <ScopeSection v-model:all-events="draft.allEvents" v-model:event-ids="draft.eventIds" v-model:group-ids="draft.groupIds"
                          :productions="productions" :groups="groups" id-prefix="pass"
                          :all-description="$t('Future ones too, without touching the pass.')"
                          :empty-hint="$t('Without a production the pass cannot be used anywhere yet.')" />

            <SoldChangesModal v-if="confirming" :pass-type="passType" :before="before" :after="passTypePayload(draft)"
                              :productions="productions" :groups="groups" @close="confirming = false" @confirm="save" />

            <ul v-if="Object.keys(form.errors).length" class="rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
            </ul>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="form.processing" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="form.processing || draft.name.trim().length < 2" @click="submit">
                {{ form.processing ? $t('Saving…') : passType ? $t('Save') : subscription ? $t('Create subscription') : $t('Create pass') }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import ScopeSection from '@/Pages/Settings/Tickets/ScopeSection.vue'
import SoldChangesModal from '@/Pages/Settings/Tickets/SoldChangesModal.vue'
import { passTypeDraft, passTypePayload, soldPassChanges } from '@/Pages/Settings/Tickets/drafts.js'

/* One Mehrfachkarte or Abo, new or existing. Everything stays editable after a sale and reaches the
   passes sold too; saving a sold one first shows what exactly changes. */
const props = defineProps({
    /** The type as tickets sends it; null for a new one. */
    passType: { type: Object, default: null },
    /** What a new type becomes; an existing one keeps its kind. */
    kind: { type: String, default: 'card' },
    productions: { type: Array, required: true },
    groups: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const { t } = useI18n()

const draft = reactive(passTypeDraft(props.passType, props.kind))
const form = useForm({})

const subscription = computed(() => draft.kind === 'subscription')
const sold = computed(() => (props.passType?.issuedCount ?? 0) > 0)
const confirming = ref(false)
const before = props.passType ? passTypePayload(passTypeDraft(props.passType, props.kind)) : null

const sectionClass = 'grid gap-4 border-t border-border-hairline pt-5'
const headingClass = 'font-lexend text-[13px] font-semibold text-text'
const labelClass = 'mb-1 block font-lexend text-xs font-medium text-[#3F424A]'

const title = computed(() => props.passType ? props.passType.name : subscription.value ? t('New subscription') : t('New pass'))

const termModes = [
    { value: 'open', label: t('Open-ended'), hint: t('Runs month by month and can be cancelled monthly.') },
    { value: 'months', label: t('Fixed term'), hint: t('A number of months from the purchase.') },
    { value: 'season', label: t('Season to a date'), hint: t('Ends on a fixed day; later buyers pay only the whole months until then.') },
]

const usesMode = computed(() => (draft.unlimited ? 'unlimited' : subscription.value ? draft.usesWindow : 'count'))

const usesModes = computed(() => {
    const unlimited = { value: 'unlimited', label: t('Unlimited'), unit: null,
        hint: subscription.value ? t('Any number of times, like a flat rate.') : t('Any number of times, e.g. a season pass.') }

    if (!subscription.value) {
        return [{ value: 'count', label: t('Fixed number'), hint: t('E.g. a 10-visit pass.'), unit: t('uses') }, unlimited]
    }

    const open = draft.termMode === 'open'
    return [
        { value: 'month', label: t('New every month'), hint: t('A fixed number per month, e.g. 2 performances.'), unit: t('uses per month') },
        { value: 'term', label: t('For the whole term'), unit: t('uses in total'), disabled: open,
            hint: open ? t('Only with a fixed term.') : t('One allowance for the whole term, used whenever the holder likes.') },
        unlimited,
    ]
})

const noPace = computed(() => String(draft.maxUsesPerPeriod ?? '').trim() === '')

/* A month's allowance already caps the month; a type that has the month anyway keeps seeing it. */
const limitPeriods = computed(() => [
    { value: 'day', label: t('per day') },
    { value: 'week', label: t('per week') },
    ...(usesMode.value !== 'month' || draft.limitPeriod === 'month' ? [{ value: 'month', label: t('per month') }] : []),
])

function chooseUses(mode) {
    draft.unlimited = mode === 'unlimited'
    if (mode === 'month' || mode === 'term') {
        draft.usesWindow = mode
    }

    if (!noPace.value) {
        return
    }
    if (mode === 'unlimited') {
        draft.maxUsesPerPeriod = '1'
        draft.limitPeriod = 'week'
    } else if (mode === 'month' && draft.limitPeriod === 'month') {
        draft.limitPeriod = 'week'
    }
}

/** Open-ended has no term to spread uses over. */
function onTermMode() {
    if (draft.termMode === 'open') {
        draft.usesWindow = 'month'
    }
}

function submit() {
    const changes = sold.value ? soldPassChanges(before, passTypePayload(draft)) : null
    if (changes && Object.values(changes).some((labels) => labels.length > 0)) {
        confirming.value = true
    } else {
        save()
    }
}

function save() {
    const options = { preserveScroll: true, onSuccess: () => emit('close'), onFinish: () => { confirming.value = false } }
    const request = form.transform(() => passTypePayload(draft))

    if (props.passType) {
        request.put(route('settings.tickets.passes.update', props.passType.id), options)
    } else {
        request.post(route('settings.tickets.passes.store'), options)
    }
}
</script>
