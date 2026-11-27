<template>
    <ArtworkBaseModal :title="discount ? discount.name : $t('New discount')"
                      :description="$t('The shop lowers every ticket it covers by itself, inside the period. Tickets already sold keep their price.')"
                      modal-size="sm:max-w-2xl" @close="$emit('close')">
        <div class="flex flex-col gap-5 text-[13px]">
            <BaseInput id="discount-name" v-model="draft.name" label="Name" required :error="form.errors.name" maxlength="120"
                       :placeholder="$t('e.g. Early bird')" />
            <p class="-mt-3 text-xs text-text-subtle">{{ $t('The shop shows the name next to the lowered prices.') }}</p>

            <div class="grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                <ChoiceSegments v-model="draft.kind" :label="$t('Discount')"
                                :options="[{ value: 'percent', label: $t('Percent of the ticket') }, { value: 'fixed', label: $t('Fixed amount per ticket') }, { value: 'package', label: $t('Package price') }]" />
                <BaseInput id="discount-value" v-model="draft.value" type="number" min="0.01" step="0.01" required :error="form.errors.value"
                           :label="{ percent: 'Percent', fixed: 'Amount in euros', package: 'Package price in euros' }[draft.kind]" class="sm:max-w-[180px]" />
            </div>

            <div class="grid gap-1">
                <BaseInput id="discount-min-dates" v-model="draft.minDates" type="number" min="2" max="50" :required="draft.kind === 'package'"
                           :error="form.errors.minDates" :label="draft.kind === 'package' ? 'Dates in the package' : 'Only from … dates'" class="sm:max-w-[180px]" />
                <p class="text-xs text-text-subtle">{{ draft.kind === 'package'
                    ? $t('Each set of this many different dates in one booking costs the package price together; further dates cost their single price.')
                    : $t('Optional: counts different dates in one booking, e.g. 10 % from 3 dates (tiered discount).') }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <BaseInput id="discount-from" v-model="draft.validFrom" type="date" label="From" :error="form.errors.validFrom" />
                <BaseInput id="discount-until" v-model="draft.validUntil" type="date" label="Until (inclusive)" :error="form.errors.validUntil" />
            </div>

            <ScopeSection v-model:all-events="draft.allEvents" v-model:event-ids="draft.eventIds" v-model:group-ids="draft.groupIds"
                          :productions="productions" :groups="groups" id-prefix="discount"
                          :all-description="$t('Future ones too, without touching the discount.')"
                          :empty-hint="$t('Without a production the discount counts nowhere yet.')" />
            <p v-if="form.errors.eventIds" class="-mt-3 text-xs text-danger">{{ form.errors.eventIds }}</p>

            <ArtworkBaseToggle id="discount-paused" v-model="draft.paused" :label="$t('Paused')" :description="$t('Paused, the shop sells at the regular prices.')" is-small />

            <p v-if="stranded.length" class="flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ packageOnlyWarning(stranded, t) }}</span>
            </p>

            <ul v-if="form.errors.tickets" class="rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                <li>{{ form.errors.tickets }}</li>
            </ul>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="form.processing" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="form.processing || draft.name.trim().length < 2" @click="submit">
                {{ form.processing ? $t('Saving…') : discount ? $t('Save') : $t('Create discount') }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import ScopeSection from '@/Pages/Settings/Tickets/ScopeSection.vue'
import { fromCents, toCents } from '@/Pages/Settings/Tickets/drafts.js'
import { packageOnlyWarning, strandedProductions } from '@/Pages/Settings/Tickets/packageReach.js'

/* One automatic discount, new or existing: a Frühbucher, a Staffel (from a number of dates) or a
   package (that many dates at one price). Percent travels as basis points, an amount or a package
   price as cents. */
const props = defineProps({
    /** The discount as tickets sends it; null for a new one. */
    discount: { type: Object, default: null },
    /** A new package for this production, from its project's ticketing tab. */
    packageFor: { type: String, default: null },
    /** All of the house's discounts, to tell whether a package-only production keeps a package. */
    discounts: { type: Array, required: true },
    productions: { type: Array, required: true },
    groups: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const { t } = useI18n()

const source = props.discount
const draft = reactive({
    name: source?.name ?? '',
    kind: source?.kind ?? (props.packageFor ? 'package' : 'percent'),
    value: fromCents(source?.value),
    minDates: source?.minDates ? String(source.minDates) : '',
    validFrom: source?.validFrom ?? '',
    validUntil: source?.validUntil ?? '',
    allEvents: source?.allEvents ?? !props.packageFor,
    eventIds: source ? [...source.eventIds] : props.packageFor ? [props.packageFor] : [],
    groupIds: [...(source?.groupIds ?? [])],
    paused: source?.status === 'paused',
})
const form = useForm({})

/* Pausing a package or narrowing it can leave a package-only production without one on sale. */
const stranded = computed(() => (props.discount
    ? strandedProductions(props.discounts, props.productions, props.groups, props.discount, {
        ...props.discount,
        kind: draft.kind,
        status: draft.paused ? 'paused' : 'active',
        allEvents: draft.allEvents,
        eventIds: draft.allEvents ? [] : draft.eventIds,
        groupIds: draft.allEvents ? [] : draft.groupIds,
    })
    : []))

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('close') }
    const request = form.transform(() => ({
        name: draft.name.trim(),
        kind: draft.kind,
        // Percent in basis points (15 % → 1500), an amount or a package price in cents.
        value: toCents(draft.value),
        minDates: draft.minDates === '' ? null : Number(draft.minDates),
        allEvents: draft.allEvents,
        eventIds: draft.allEvents ? [] : draft.eventIds,
        groupIds: draft.allEvents ? [] : draft.groupIds,
        validFrom: draft.validFrom || null,
        validUntil: draft.validUntil || null,
        status: draft.paused ? 'paused' : 'active',
    }))

    if (props.discount) {
        request.put(route('settings.tickets.discounts.update', props.discount.id), options)
    } else {
        request.post(route('settings.tickets.discounts.store'), options)
    }
}
</script>
