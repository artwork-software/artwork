<template>
    <ArtworkBaseModal :title="title" :description="subscription ? $t('Term, prices and what the subscription is worth per ticket.') : $t('Price, uses and what one use is worth.')"
                      modal-size="sm:max-w-3xl" @close="$emit('close')">
        <div class="flex flex-col gap-6 text-[13px]">
            <p v-if="sold" class="rounded-md bg-surface-sunken px-3.5 py-3 text-[13px] leading-5 text-text-muted">
                {{ $t('Already sold: uses, value, limits and term stay as they are. Name, description, prices and the end of sale can change; productions can only be added.') }}
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
                <h3 :class="headingClass">{{ $t('Price and uses') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <BaseInput id="pass-price" v-model="draft.price" type="number" min="0" step="0.01" label="Price in euros" required :error="form.errors.price" />
                    <div>
                        <BaseInput id="pass-uses" v-model="draft.uses" type="number" min="1" max="1000" label="Uses" required :disabled="sold" :error="form.errors.uses" />
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('One use = one ticket, reduced by the value below.') }}</p>
                    </div>
                </div>
            </section>

            <template v-else>
                <section :class="sectionClass">
                    <h3 id="pass-term-heading" :class="headingClass">{{ $t('Term') }}</h3>
                    <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-labelledby="pass-term-heading">
                        <label v-for="mode in termModes" :key="mode.value" :for="`pass-term-${mode.value}`"
                               class="flex items-start gap-3 rounded-lg border px-3.5 py-3 transition-colors"
                               :class="[draft.termMode === mode.value ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface', sold ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:bg-surface-sunken']">
                            <input :id="`pass-term-${mode.value}`" v-model="draft.termMode" type="radio" name="pass-term" :value="mode.value" :disabled="sold" class="sr-only" @change="onTermMode" />
                            <span class="mt-0.5 box-border size-4 shrink-0 rounded-full border bg-surface" :class="draft.termMode === mode.value ? 'border-[5px] border-accent-600' : 'border-border'"></span>
                            <span class="flex flex-col gap-0.5">
                                <span class="font-lexend text-[13px] font-medium text-text">{{ mode.label }}</span>
                                <span class="text-[12.5px] leading-[18px] text-text-muted">{{ mode.hint }}</span>
                            </span>
                        </label>
                    </div>

                    <div v-if="draft.termMode === 'months'" class="grid gap-4 sm:grid-cols-2">
                        <BaseInput id="pass-term-months" v-model="draft.termMonths" type="number" min="1" max="24" label="Months" required :disabled="sold" :error="form.errors.termMonths" />
                        <div>
                            <span :class="labelClass">{{ $t('After the term') }}</span>
                            <ChoiceSegments v-model="draft.afterTerm" :disabled="sold" :label="$t('After the term')"
                                            :options="[{ value: 'ends', label: $t('Ends') }, { value: 'monthly', label: $t('Runs on monthly') }]" />
                        </div>
                    </div>
                    <div v-else-if="draft.termMode === 'season'" class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <BaseInput id="pass-season-ends" v-model="draft.seasonEnds" type="date" label="Last day" required :disabled="sold" :error="form.errors.seasonEnds" />
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

                <section :class="sectionClass">
                    <h3 :class="headingClass">{{ $t('Uses') }}</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <BaseInput id="pass-uses" v-model="draft.uses" type="number" min="1" max="1000" required :disabled="sold" :error="form.errors.uses"
                                   :label="draft.usesWindow === 'month' ? 'Uses per month' : 'Uses over the term'" />
                        <div>
                            <span :class="labelClass">{{ $t('Uses count') }}</span>
                            <ChoiceSegments v-model="draft.usesWindow" :disabled="sold" :label="$t('Uses count')"
                                            :options="[{ value: 'month', label: $t('per month') }, { value: 'term', label: $t('over the whole term'), disabled: draft.termMode === 'open' }]" />
                        </div>
                        <div v-if="draft.usesWindow === 'month'">
                            <BaseInput id="pass-rollover" v-model="draft.rolloverMax" type="number" min="0" max="100" label="Carried into the next month" :disabled="sold" :error="form.errors.rolloverMax" />
                            <p class="mt-1 text-xs text-text-subtle">{{ $t('How many unused uses move on. 0: none.') }}</p>
                        </div>
                    </div>
                </section>
            </template>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ $t('What one use is worth in the checkout') }}</h3>
                <div>
                    <div class="flex flex-wrap items-end gap-4">
                        <ChoiceSegments v-model="draft.discountKind" :disabled="sold" :label="$t('What one use is worth in the checkout')"
                                        :options="[{ value: 'percent', label: $t('Percent of the ticket') }, { value: 'fixed', label: $t('Fixed amount') }]" />
                        <div class="w-44">
                            <BaseInput v-if="draft.discountKind === 'percent'" id="pass-percent" v-model="draft.discountPercent" type="number" min="1" max="100"
                                       label="Percent" required :disabled="sold" :error="form.errors.discountPercent" />
                            <BaseInput v-else id="pass-amount" v-model="draft.discountAmount" type="number" min="0.01" step="0.01"
                                       label="Amount in euros" required :disabled="sold" :error="form.errors.discountAmount" />
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
                        <BaseInput id="pass-max-order" v-model="draft.maxUsesPerOrder" type="number" min="1" max="100" label="At most per order" :disabled="sold" :error="form.errors.maxUsesPerOrder" />
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('Empty: no limit.') }}</p>
                    </div>
                    <div>
                        <BaseInput id="pass-max-event" v-model="draft.maxUsesPerEvent" type="number" min="1" max="1000" label="At most per event" :disabled="sold" :error="form.errors.maxUsesPerEvent" />
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('Across all orders of this pass. Empty: no limit.') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="flex items-end gap-2">
                            <BaseInput id="pass-max-period" v-model="draft.maxUsesPerPeriod" type="number" min="1" max="100" label="At most per period" class="!w-40"
                                       :disabled="sold" :error="form.errors.maxUsesPerPeriod" />
                            <ChoiceSegments v-model="draft.limitPeriod" :disabled="sold" :label="$t('Period')"
                                            :options="[{ value: 'day', label: $t('per day') }, { value: 'week', label: $t('per week') }, { value: 'month', label: $t('per month') }]" />
                        </div>
                        <p class="mt-1 text-xs text-text-subtle">{{ $t('Calendar day, week (Mon–Sun) or month. A booking counts on the day of the performance, a stamp on the day it is given. Empty: no limit.') }}</p>
                    </div>
                </div>
            </section>

            <section :class="sectionClass">
                <h3 :class="headingClass">{{ subscription ? $t('Sale') : $t('Validity and sale') }}</h3>
                <div v-if="!subscription" class="flex flex-wrap items-end gap-4">
                    <div>
                        <span :class="labelClass">{{ $t('Validity') }}</span>
                        <ChoiceSegments v-model="draft.validity" :disabled="sold" :label="$t('Validity')"
                                        :options="[{ value: 'unlimited', label: $t('Unlimited') }, { value: 'days', label: $t('Days from purchase') }, { value: 'until', label: $t('Until a fixed date') }]" />
                    </div>
                    <div v-if="draft.validity !== 'unlimited'" class="w-60">
                        <BaseInput v-if="draft.validity === 'days'" id="pass-validity-days" v-model="draft.validityDays" type="number" min="1" max="3650"
                                   label="Days from purchase" required :disabled="sold" :error="form.errors.validityDays" />
                        <BaseInput v-else id="pass-valid-until" v-model="draft.validUntil" type="datetime-local"
                                   label="Valid until" required :disabled="sold" :error="form.errors.validUntil" />
                    </div>
                </div>
                <div class="w-60">
                    <BaseInput id="pass-sale-ends" v-model="draft.saleEnds" type="date" label="On sale until" :error="form.errors.saleEnds" />
                    <p class="mt-1 text-xs text-text-subtle">{{ $t('Off the shop from the day after. Empty: as long as it is published.') }}</p>
                </div>
            </section>

            <section :class="sectionClass">
                <div class="flex items-baseline justify-between gap-3">
                    <h3 :class="headingClass">{{ $t('Valid for') }}</h3>
                    <span v-if="!draft.allEvents && productions.length > 0" class="text-xs text-text-subtle tabular-nums">
                        {{ $t('{count} of {total} productions', { count: coveredCount, total: productions.length }) }}
                    </span>
                </div>
                <ArtworkBaseToggle id="pass-all-events" v-model="draft.allEvents" :disabled="sold && passType?.allEvents" :label="$t('Valid for all productions')"
                                   :description="$t('Future ones too, without touching the pass.')" is-small />
                <template v-if="!draft.allEvents">
                    <p v-if="productions.length === 0" class="text-xs text-text-subtle">{{ $t('Artwork-Tickets has no productions yet. Release one from a project first.') }}</p>
                    <template v-else>
                        <div>
                            <span :class="labelClass">{{ $t('Production groups') }}</span>
                            <div v-if="groups.length > 0" class="grid gap-2 sm:grid-cols-2">
                                <div v-for="group in groups" :key="group.id" class="rounded-lg border px-3 py-2.5 transition-colors"
                                     :class="draft.groupIds.includes(group.id) ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface'">
                                    <BaseCheckbox :id="`pass-group-${group.id}`" :label="group.name"
                                                  :description="$t('{count} productions', { count: group.eventIds.length }, group.eventIds.length)"
                                                  :disabled="fixedGroupIds.includes(group.id)" :model-value="draft.groupIds.includes(group.id)"
                                                  @update:model-value="(on) => pickGroup(group.id, on)" />
                                </div>
                            </div>
                            <p v-if="groups.length > 0" class="mt-1 text-xs text-text-subtle">{{ $t('Also counts for whatever joins a group later.') }}</p>
                            <p v-else class="text-xs text-text-subtle">
                                {{ $t('No production groups yet.') }}
                                <Link :href="route('settings.tickets.groups')" class="text-text underline underline-offset-2">{{ $t('Create one under Production groups, e.g. for all workshops.') }}</Link>
                            </p>
                        </div>
                        <div>
                            <span :class="labelClass">{{ $t('Single productions') }}</span>
                            <ProductionPicker v-model="draft.eventIds" :productions="productions" :fixed-ids="sold ? passType.eventIds : []"
                                              :covered-by="coveredBy" id-prefix="pass-production" />
                        </div>
                        <p v-if="coveredCount === 0" class="text-xs text-text-subtle">
                            {{ $t('Without a production the pass cannot be used anywhere yet.') }}
                        </p>
                    </template>
                </template>
            </section>

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
import { computed, reactive } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import ProductionPicker from '@/Pages/Settings/Tickets/ProductionPicker.vue'
import { passTypeDraft, passTypePayload } from '@/Pages/Settings/Tickets/drafts.js'

/* One Mehrfachkarte or Abo, new or existing. Once something is sold, what holders bought is
   fixed — the fields for it are disabled here, and tickets refuses the same. */
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
/** Sold: the groups it already counts for stay. */
const fixedGroupIds = computed(() => sold.value ? props.passType.groupIds : [])

/** Productions counted through a picked group: id → the group names. */
const coveredBy = computed(() => {
    const names = {}
    for (const group of props.groups.filter((candidate) => draft.groupIds.includes(candidate.id))) {
        for (const id of group.eventIds) {
            names[id] = names[id] ? `${names[id]}, ${group.name}` : group.name
        }
    }
    return names
})

const coveredCount = computed(() => {
    const listed = new Set(props.productions.map((production) => production.id))
    return new Set([...draft.eventIds, ...Object.keys(coveredBy.value)].filter((id) => listed.has(id))).size
})

const sectionClass = 'grid gap-4 border-t border-border-hairline pt-5'
const headingClass = 'font-lexend text-[13px] font-semibold text-text'
const labelClass = 'mb-1 block font-lexend text-xs font-medium text-[#3F424A]'

const title = computed(() => props.passType ? props.passType.name : subscription.value ? t('New subscription') : t('New pass'))

const termModes = [
    { value: 'open', label: t('Open-ended'), hint: t('Runs month by month and can be cancelled monthly.') },
    { value: 'months', label: t('Fixed term'), hint: t('A number of months from the purchase.') },
    { value: 'season', label: t('Season to a date'), hint: t('Ends on a fixed day; later buyers pay only the whole months until then.') },
]

/** Open-ended has no term to spread uses over. */
function onTermMode() {
    if (draft.termMode === 'open') {
        draft.usesWindow = 'month'
    }
}

function pickGroup(id, on) {
    draft.groupIds = on ? [...draft.groupIds, id] : draft.groupIds.filter((groupId) => groupId !== id)
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('close') }
    const request = form.transform(() => passTypePayload(draft))

    if (props.passType) {
        request.put(route('settings.tickets.passes.update', props.passType.id), options)
    } else {
        request.post(route('settings.tickets.passes.store'), options)
    }
}
</script>
