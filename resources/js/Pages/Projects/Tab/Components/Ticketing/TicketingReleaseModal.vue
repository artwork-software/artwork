<template>
    <ArtworkBaseModal :title="withdrawing ? $t('Take off sale') : $t('Release for sale')" :description="single ? single.name : $t('{count} dates selected', { count: events.length })" modal-size="sm:max-w-2xl" @close="$emit('close')">
        <div class="flex flex-col gap-5 text-[13px]">
            <div v-if="seriesEvents.length > 1" class="inline-flex self-start p-0.5 rounded-md bg-surface-sunken border border-border-subtle" role="radiogroup">
                <button v-for="option in scopeOptions" :key="option.value" type="button" role="radio" :aria-checked="scope === option.value"
                        class="flex items-center h-7 px-3 rounded text-xs font-medium transition-colors"
                        :class="scope === option.value ? 'bg-surface text-text shadow-[0_1px_2px_rgba(28,31,36,.08)]' : 'text-text-subtle hover:text-text'"
                        @click="scope = option.value">
                    {{ option.label }}
                </button>
            </div>

            <p v-if="skipped > 0" class="text-xs text-text-muted">
                {{ withdrawing ? $t('{count} of the selected dates are not on sale and are left out.', { count: skipped }) : $t('{count} of the selected dates are already on sale and are left out.', { count: skipped }) }}
            </p>

            <div class="rounded-lg border border-border-subtle bg-surface">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="font-lexend text-xs font-medium text-text-subtle">
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Date') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Room') }}</th>
                            <th class="px-4 py-2.5 text-right font-medium">{{ $t('Places') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Prices') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="target in targets" :key="target.id" class="border-t border-border-hairline">
                            <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
                                {{ formatWhen(target, locale) }}
                                <BaseChip v-if="!withdrawing && isPast(target)" variant="warning" class="ml-2">{{ $t('Already over') }}</BaseChip>
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="target.venue">{{ target.venue.name }}</span>
                                <span v-else class="text-danger">{{ target.room ? $t('Room not synced') : $t('No room') }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ capacityOf(target) ?? '–' }}</td>
                            <td class="px-4 py-2.5">
                                <span v-if="classesOf(target).length === 0" class="text-text-subtle">–</span>
                                <span v-else class="flex flex-wrap gap-x-3 gap-y-1">
                                    <span v-for="cls in classesOf(target)" :key="cls.zone_key ?? cls.name" class="whitespace-nowrap">{{ cls.name }} <span class="text-text-subtle tabular-nums">{{ cls.quota }} ·</span> <span class="tabular-nums">{{ formatEuro(cls.price_cents) }}</span></span>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="targets.length === 0" class="border-t border-border-hairline">
                            <td colspan="4" class="px-4 py-4 text-center text-text-subtle">{{ withdrawing ? $t('None of these dates is on sale.') : $t('All of these dates are on sale already.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-start gap-2.5 rounded-md border px-3.5 py-3 leading-5 text-text"
                 :class="withdrawing ? 'border-warning-border bg-warning-surface' : 'border-info-border bg-info-surface'">
                <IconAlertTriangle v-if="withdrawing" class="size-4 shrink-0 mt-0.5 text-warning" />
                <IconInfoCircle v-else class="size-4 shrink-0 mt-0.5 text-info" />
                <span v-if="withdrawing">{{ $t('The dates disappear from the shop. Tickets already sold stay valid and refundable; such a date is marked cancelled in artwork tickets instead of removed.') }}</span>
                <span v-else>{{ $t('The dates go on sale in artwork tickets with these places and prices. Places and prices can still be changed afterwards; the production is created from this project on the first release.') }}</span>
            </div>

            <p v-if="error" class="text-sm text-danger">{{ error }}</p>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="submitting" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="submitting || targets.length === 0 || (!withdrawing && !releasable)" @click="submit">
                <IconTicket class="size-4" />
                {{ submitting ? $t('Please wait…') : withdrawing ? $t('Take {count} dates off sale', { count: targets.length }) : $t('Release {count} dates now', { count: targets.length }) }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconInfoCircle, IconTicket } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { capacityOf, classesOf, formatEuro, formatWhen, isReleasable, isReleased, seriesOf } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

const props = defineProps({
    projectId: { type: Number, required: true },
    /** The dates the action was opened for; a single one of a series may widen to the series. */
    events: { type: Array, required: true },
    /** All dates of the component, to find the rest of a series. */
    allEvents: { type: Array, required: true },
    withdrawing: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'done'])

const { t, locale } = useI18n()

const scope = ref('single')
const submitting = ref(false)
const error = ref('')

const single = computed(() => (props.events.length === 1 ? props.events[0] : null))
const seriesEvents = computed(() => (single.value ? seriesOf(single.value, props.allEvents) : []))

const scopeOptions = computed(() => [
    { value: 'single', label: t('Only this date') },
    { value: 'series', label: t('All {count} dates of the series', { count: seriesEvents.value.length }) },
])

/* What the action touches: released dates cannot be released again, and vice versa. */
const candidates = computed(() => (scope.value === 'series' ? seriesEvents.value : props.events))
const targets = computed(() => candidates.value.filter((event) => isReleased(event) === props.withdrawing))
const skipped = computed(() => candidates.value.length - targets.value.length)

function isPast(event) {
    return dayjs(event.start).isBefore(dayjs())
}

const releasable = computed(() => targets.value.every(isReleasable))

async function submit() {
    submitting.value = true
    error.value = ''
    const url = route(props.withdrawing ? 'projects.tabs.ticketing.withdraw' : 'projects.tabs.ticketing.release', { project: props.projectId })
    const body = { event_ids: targets.value.map((event) => event.id) }
    try {
        const { data } = props.withdrawing
            ? await axios.delete(url, { data: body })
            : await axios.post(url, body)
        emit('done', data)
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || requestError.message
    } finally {
        submitting.value = false
    }
}
</script>
