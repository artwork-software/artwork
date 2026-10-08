<template>
    <div class="mt-5 pb-20">
        <ToolbarHeader
            :icon="IconBuildingStore"
            :title="$t('Artwork-Tickets')"
            icon-bg-class="bg-accent-50 text-accent-700"
            :description="$t('Dates of this project that sell tickets: prices, places and release for sale.')"
            :search-enabled="false"
        >
            <template #actions>
                <a v-if="payload?.connection.connected" :href="route('ticketing.open')" target="_blank" rel="noopener" class="ui-button">
                    <IconExternalLink class="size-3.5" />{{ $t('Open Artwork-Tickets') }}
                </a>
            </template>
        </ToolbarHeader>

        <div class="mt-6">
            <div v-if="loading" class="flex items-center gap-2 text-sm text-text-subtle">
                <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
            </div>

            <p v-else-if="loadError" class="text-sm text-danger">{{ loadError }}</p>

            <!-- Not connected -->
            <EmptyState v-else-if="!payload.connection.connected" :icon="IconPlugConnectedX" :title="$t('Not connected yet')"
                        :text="$t('Dates can be released for sale once this installation is connected to Artwork-Tickets.')">
                <Link v-if="canManageTicketing" :href="route('settings.tickets')" class="ui-button-add mt-5 inline-flex">
                    <IconPlugConnected class="size-4" />{{ $t('Connect Artwork-Tickets') }}
                </Link>
            </EmptyState>

            <!-- No event type may sell -->
            <EmptyState v-else-if="!payload.hasSellingEventTypes" :icon="IconTicketOff" :title="$t('No event type sells tickets yet')"
                        :text="$t('Mark the event types that sell tickets, e.g. performances, in the event type settings. Their dates then appear here.')">
                <Link v-if="canManageEventTypes" :href="route('event_types.management')" class="ui-button mt-5 inline-flex">{{ $t('Event type settings') }}</Link>
            </EmptyState>

            <!-- No dates -->
            <EmptyState v-else-if="payload.events.length === 0" :icon="IconCalendarOff" :title="$t('No dates that sell tickets')"
                        :text="$t('This project has no dates of an event type that sells tickets. Create them in the calendar; they appear here automatically.')" />

            <template v-else>
                <div v-if="payload.connection.billingComplete === false" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                    <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                    <span>
                        {{ $t('Details of the ticket house are still missing in Artwork-Tickets: legal details, legal pages, payout account or the accepted terms. Until they are complete, no date can be released for sale.') }}
                        <Link v-if="canManageTicketing" :href="route('settings.tickets.billing')" class="font-medium text-accent-600 hover:underline">{{ $t('Fill in now') }}</Link>
                    </span>
                </div>

                <div v-if="payload.ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                    <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                    <span>{{ $t('Artwork-Tickets could not be reached: {message} Venue defaults are missing until it is back.', { message: payload.ticketsError }) }}</span>
                </div>

                <TicketingProductionCard v-if="canEditComponent || payload.production.linked" class="mb-5" :project-id="project.id" :production="payload.production" :reductions="payload.reductions" :can-edit="canEditComponent" @saved="applyPayload" />

                <div class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised">
                    <!-- One toolbar: filters by default, the actions for the picked dates once there is a
                         selection. It sticks below the project header while the table scrolls. -->
                    <div class="sticky z-30 rounded-t-lg" :style="{ top: 'var(--project-header-height, 0px)' }">
                        <div v-if="selectedEvents.length" class="flex min-h-12 flex-wrap items-center gap-x-3 gap-y-2 rounded-t-lg border-b border-accent-200 bg-accent-50 px-4 py-2 text-[13px]">
                            <span class="font-medium tabular-nums text-text">{{ $t('{count} dates selected', { count: selectedEvents.length }) }}</span>
                            <span class="h-4 w-px bg-accent-200" aria-hidden="true"></span>
                            <button type="button" class="ui-button-small" @click="editing = selectedEvents">
                                <IconPencil class="size-3.5" stroke-width="1.75" />{{ $t('Edit sale') }}
                            </button>
                            <button v-if="selectedReleasable.length" type="button" class="ui-button-add-small" @click="confirming = { events: selectedReleasable, withdrawing: false }">
                                <IconTicket class="size-3.5" stroke-width="1.75" />{{ $t('Release {count}', { count: selectedReleasable.length }) }}
                            </button>
                            <button v-if="selectedReleased.length" type="button" class="ui-button-small" @click="confirming = { events: selectedEvents, withdrawing: true }">
                                <IconTicketOff class="size-3.5" stroke-width="1.75" />{{ $t('Withdraw {count}', { count: selectedReleased.length }) }}
                            </button>
                            <button type="button" class="ml-auto text-xs text-text-subtle hover:text-text" @click="selected = new Set()">{{ $t('Clear selection') }}</button>
                        </div>

                        <div v-else class="flex min-h-12 flex-wrap items-center gap-x-2.5 gap-y-2 rounded-t-lg border-b border-border-hairline bg-surface-header px-4 py-2">
                            <div class="inline-flex gap-0.5 rounded-md border border-border-subtle bg-surface-sunken p-0.5" role="radiogroup">
                                <button v-for="option in statusOptions" :key="option.value" type="button" role="radio" :aria-checked="filters.status === option.value"
                                        class="flex h-[26px] items-center gap-1.5 rounded px-2.5 text-xs font-medium transition-colors"
                                        :class="filters.status === option.value ? 'bg-surface text-text shadow-[0_1px_2px_rgba(28,31,36,.08)]' : 'text-text-subtle hover:text-text'"
                                        @click="filters.status = option.value">
                                    {{ option.label }}<span class="font-normal tabular-nums text-text-subtle">{{ option.count }}</span>
                                </button>
                            </div>
                            <span class="h-4 w-px bg-border-subtle" aria-hidden="true"></span>

                            <TicketingFilterMenu :label="$t('Room')" :active="filters.rooms.length > 0" @reset="filters.rooms = []">
                                <label v-for="room in roomOptions" :key="room.key" class="flex h-[34px] cursor-pointer items-center gap-2.5 px-3 text-[13px] text-text hover:bg-surface-sunken">
                                    <SelectBox :checked="filters.rooms.includes(room.key)" :label="room.label" @change="toggleFilter('rooms', room.key, $event)" />
                                    <span class="flex-1 truncate">{{ room.label }}</span>
                                    <span class="text-xs tabular-nums text-text-subtle">{{ room.count }}</span>
                                </label>
                            </TicketingFilterMenu>

                            <DateRangeControl :key="rangeKey" :date-value-array="filters.range ?? fullSpan" mode="local" compact :show-navigation="false" :show-today="false" @change="filters.range = $event" />

                            <TicketingFilterMenu v-if="hasSeries" :label="$t('Series')" :active="filters.series !== 'all'" @reset="filters.series = 'all'">
                                <button v-for="option in seriesOptions" :key="option.value" type="button" role="radio" :aria-checked="filters.series === option.value"
                                        class="flex h-[34px] w-full items-center gap-2.5 px-3 text-left text-[13px] text-text hover:bg-surface-sunken" @click="filters.series = option.value">
                                    <span class="flex size-4 items-center justify-center"><IconCheck v-if="filters.series === option.value" class="size-4 text-accent-600" stroke-width="2.5" /></span>
                                    {{ option.label }}
                                </button>
                            </TicketingFilterMenu>

                            <label class="ml-auto flex h-7 w-56 items-center gap-2 rounded-md border border-border bg-surface px-2.5 text-xs text-text-subtle focus-within:border-accent-600">
                                <IconSearch class="size-3.5 shrink-0" stroke-width="2" />
                                <input v-model.trim="filters.q" type="search" :placeholder="$t('Search dates')" :aria-label="$t('Search dates')" class="w-full min-w-0 border-0 bg-transparent p-0 text-xs text-text placeholder:text-text-subtle focus:ring-0" />
                            </label>
                            <button v-if="canEditComponent && unreleased.length > 0" type="button" class="ui-button-add-small" @click="confirming = { events: unreleased, withdrawing: false }">
                                <IconTicket class="size-3.5" stroke-width="1.75" />{{ $t('Release all {count}', { count: unreleased.length }) }}
                            </button>
                        </div>

                        <div v-if="activeChips.length" class="flex flex-wrap items-center gap-2 border-b border-border-hairline bg-surface px-4 py-2">
                            <span class="text-xs tabular-nums text-text-subtle">{{ $t('{shown} of {total} dates', { shown: visible.length, total: payload.events.length }) }}</span>
                            <FilterChip v-for="chip in activeChips" :key="chip.key" :label="chip.label" @remove="chip.remove()" />
                            <button type="button" class="text-xs text-text-subtle hover:text-text" @click="resetFilters">{{ $t('Reset filters') }}</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1140px] table-fixed border-collapse text-[13px]">
                            <colgroup>
                                <col v-if="canEditComponent" class="w-[44px]" /><col class="w-[170px]" /><col /><col class="w-[190px]" /><col class="w-[90px]" /><col class="w-[240px]" /><col class="w-[150px]" /><col class="w-[260px]" />
                            </colgroup>
                            <thead>
                                <tr class="font-lexend text-xs font-medium text-text-subtle">
                                    <th v-if="canEditComponent" class="pl-4 pr-1 py-3">
                                        <SelectBox v-if="visible.length" :checked="allVisibleSelected" :indeterminate="!allVisibleSelected && selectedEvents.length > 0" :label="$t('Select all dates')" @change="toggleMany(visible, $event)" />
                                    </th>
                                    <th class="px-5 py-3 text-left font-medium">{{ $t('Date') }}</th>
                                    <th class="px-5 py-3 text-left font-medium">{{ $t('Name') }}</th>
                                    <th class="px-5 py-3 text-left font-medium">{{ $t('Room') }}</th>
                                    <th class="px-5 py-3 text-right font-medium">{{ $t('Places') }}</th>
                                    <th class="px-5 py-3 text-left font-medium">{{ $t('Price classes') }}</th>
                                    <th class="px-5 py-3 text-left font-medium">{{ $t('Status') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody v-if="visible.length === 0">
                                <tr class="border-t border-border-hairline">
                                    <td :colspan="canEditComponent ? 8 : 7" class="px-5 py-12 text-center">
                                        <IconCalendarOff class="mx-auto size-8 text-text-subtle" stroke-width="1.5" />
                                        <p class="font-lexend mt-3 text-[15px] font-semibold text-text">{{ $t('No dates match the filters') }}</p>
                                        <p class="mt-1 text-sm text-text-muted">{{ $t('Reset a filter to see all {count} dates again.', { count: payload.events.length }) }}</p>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-for="group in groups" :key="group.key">
                                <tr v-if="group.seriesId" class="border-t border-border-hairline bg-surface-sunken">
                                    <td v-if="canEditComponent" class="pl-4 pr-1 py-2">
                                        <SelectBox :checked="group.events.every(isSelected)" :indeterminate="!group.events.every(isSelected) && group.events.some(isSelected)" :label="$t('Select the whole series')" @change="toggleMany(group.events, $event)" />
                                    </td>
                                    <td colspan="7" class="px-5 py-2">
                                        <span class="flex items-center gap-2 font-lexend text-[11px] font-semibold uppercase tracking-[.02em] text-text-subtle">
                                            <IconRepeat class="size-3.5" />{{ $t('Series · {count} dates', { count: group.events.length }) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-for="event in group.events" :key="event.id" class="border-t border-border-hairline" :class="isSelected(event) ? 'bg-accent-50/60' : ''">
                                    <td v-if="canEditComponent" class="pl-4 pr-1 py-4 align-top">
                                        <SelectBox :checked="isSelected(event)" :label="$t('Select {name}', { name: `${event.name} · ${formatDay(event.start, locale)}` })" @change="toggleMany([event], $event)" />
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap tabular-nums align-top">
                                        <span class="block leading-6 text-text">{{ formatDay(event.start, locale) }}</span>
                                        <span class="block text-xs text-text-subtle">{{ event.allDay ? $t('All day') : `${formatTime(event.start)} – ${formatTime(event.end)}` }}</span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="flex items-center gap-2 min-w-0 leading-6">
                                            <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: event.eventType?.hexCode ?? '#999' }"></span>
                                            <span class="truncate text-text">{{ event.name }}</span>
                                            <TicketingSaleMarks :event="event" :production="payload.production" :reductions="payload.reductions" />
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span v-if="!event.room" class="block leading-6 text-text-subtle">{{ $t('No room') }}</span>
                                        <span v-else class="flex items-center gap-2 min-w-0 leading-6">
                                            <span class="truncate">{{ event.room.name }}</span>
                                            <IconCheck v-if="event.venue" class="size-3.5 shrink-0 text-success" :title="$t('In Artwork-Tickets')" />
                                            <BaseChip v-else variant="warning" class="whitespace-nowrap">{{ $t('Room not synced') }}</BaseChip>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right tabular-nums align-top leading-6">
                                        <span v-if="capacityOf(event) !== null">{{ capacityOf(event) }}</span>
                                        <span v-else class="text-text-subtle">–</span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span v-if="classesOf(event).length === 0" class="block leading-6 text-text-subtle">–</span>
                                        <ul v-else class="flex flex-col">
                                            <li v-for="cls in classesOf(event)" :key="cls.zone_key ?? cls.name" class="flex items-baseline justify-between gap-3 whitespace-nowrap leading-6">
                                                <span class="truncate">{{ cls.name }} <span class="text-xs text-text-subtle tabular-nums">· {{ cls.quota }}</span></span>
                                                <span class="tabular-nums text-text">{{ formatEuro(cls.price_cents) }}</span>
                                            </li>
                                        </ul>
                                    </td>
                                    <td class="px-5 py-4 align-top whitespace-nowrap">
                                        <span class="flex h-6 items-center gap-1.5">
                                            <BaseChip v-if="isReleased(event)" variant="success" :title="releasedTitle(event)">{{ $t('On sale') }}</BaseChip>
                                            <BaseChip v-else-if="isPast(event)" variant="neutral">{{ $t('Already over') }}</BaseChip>
                                            <BaseChip v-else variant="neutral">{{ $t('Not released') }}</BaseChip>
                                            <span v-if="event.release?.syncError" role="img" :title="`${$t('Not up to date in tickets')}: ${event.release.syncError}`" :aria-label="$t('Not up to date in tickets')">
                                                <IconAlertTriangle class="size-4 shrink-0 text-warning" />
                                            </span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="-my-1 flex items-center justify-end gap-2">
                                            <button v-if="isReleased(event)" type="button" class="ui-button h-8 px-2" :title="$t('Ticket details')" :aria-label="$t('Ticket details')" @click="viewingSales = event">
                                                <IconUsers class="size-[18px] shrink-0" stroke-width="1.75" />
                                            </button>
                                            <template v-if="canEditComponent">
                                                <button type="button" class="ui-button h-8 px-2" :title="$t('Edit sale')" :aria-label="$t('Edit sale')" @click="editing = [event]">
                                                    <IconPencil class="size-[18px] shrink-0" stroke-width="1.75" />
                                                </button>
                                                <button v-if="isReleased(event)" type="button" class="ui-button h-8 w-[140px] whitespace-nowrap" @click="confirming = { events: [event], withdrawing: true }">
                                                    <IconTicketOff class="size-[18px] shrink-0" stroke-width="1.75" />{{ $t('Withdraw') }}
                                                </button>
                                                <button v-else type="button" class="ui-button-add h-8 w-[140px] whitespace-nowrap" :disabled="!isReleasable(event)" :title="releaseBlocker(event) ? $t(releaseBlocker(event)) : ''" @click="confirming = { events: [event], withdrawing: false }">
                                                    <IconTicket class="size-[18px] shrink-0" stroke-width="1.75" />{{ $t('Release') }}
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center gap-4 rounded-b-lg border-t border-border-hairline bg-surface-sunken px-5 py-2.5 text-xs tabular-nums text-text-subtle">
                        <span>{{ $t('{count} dates', { count: visible.length }) }}</span>
                        <span>{{ $t('{count} on sale', { count: visible.filter(isReleased).length }) }}</span>
                        <span>{{ $t('{count} places', { count: totalPlaces }) }}</span>
                    </div>
                </div>
            </template>
        </div>

        <TicketingDateModal v-if="editing" :project-id="project.id" :events="editing" :all-events="payload.events" :production="payload.production" :reductions="payload.reductions"
                            @close="editing = null" @saved="applyPayload" />
        <TicketingSalesModal v-if="viewingSales" :event-id="viewingSales.id" :description="`${viewingSales.name} · ${formatDay(viewingSales.start, locale)}`" @close="viewingSales = null" />
        <TicketingReleaseModal v-if="confirming" :project-id="project.id" :events="confirming.events" :all-events="payload.events" :withdrawing="confirming.withdrawing"
                               :production="payload.production" :reductions="payload.reductions"
                               @close="confirming = null" @done="applyPayload" />
    </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import dayjs from 'dayjs'
import { IconAlertTriangle, IconBuildingStore, IconCalendarOff, IconCheck, IconExternalLink, IconLoader2, IconPencil, IconPlugConnected, IconPlugConnectedX, IconRepeat, IconSearch, IconTicket, IconTicketOff, IconUsers } from '@tabler/icons-vue'
import { can, is } from 'laravel-permission-to-vuejs'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import FilterChip from '@/Artwork/Filter/FilterChip.vue'
import DateRangeControl from '@/Artwork/DateRange/DateRangeControl.vue'
import TicketingDateModal from '@/Pages/Projects/Tab/Components/Ticketing/TicketingDateModal.vue'
import TicketingFilterMenu from '@/Pages/Projects/Tab/Components/Ticketing/TicketingFilterMenu.vue'
import TicketingReleaseModal from '@/Pages/Projects/Tab/Components/Ticketing/TicketingReleaseModal.vue'
import TicketingSaleMarks from '@/Pages/Projects/Tab/Components/Ticketing/TicketingSaleMarks.vue'
import TicketingSalesModal from '@/Pages/Projects/Tab/Components/Ticketing/TicketingSalesModal.vue'
import TicketingProductionCard from '@/Pages/Projects/Tab/Components/Ticketing/TicketingProductionCard.vue'
import { capacityOf, classesOf, formatDay, formatEuro, formatTime, isPast, isReleasable, isReleased, releaseBlocker } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

const props = defineProps({
    project: { type: Object, required: true },
    canEditComponent: { type: Boolean, default: true },
})

const { t, locale } = useI18n()

const payload = ref(null)
const loading = ref(true)
const loadError = ref('')
/** The dates a modal is open for: a list, so single and batch share one path. */
const editing = ref(null)
const confirming = ref(null)
const viewingSales = ref(null)
/** Selected date ids; replaced as a whole on every change so computed values notice. */
const selected = ref(new Set())
/** range: [startIso, endIso] or null for the whole span of the project's dates. */
const filters = ref({ status: 'all', rooms: [], range: null, series: 'all', q: '' })

const canManageTicketing = computed(() => is('artwork admin') || can('manage ticketing'))
const canManageEventTypes = computed(() => is('artwork admin') || can('change event settings'))

/* A bare checkbox for table cells and menus, same look as BaseCheckbox without its label column. */
const SelectBox = defineComponent({
    props: { checked: Boolean, indeterminate: Boolean, label: { type: String, required: true } },
    emits: ['change'],
    setup(p, { emit }) {
        return () => h('span', { class: 'group grid size-4 shrink-0 grid-cols-1' }, [
            h('input', {
                type: 'checkbox', class: 'aw-checklist-input cursor-pointer', checked: p.checked, '.indeterminate': p.indeterminate, 'aria-label': p.label,
                onChange: (event) => emit('change', event.target.checked),
            }),
            h('svg', { class: 'pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white', viewBox: '0 0 14 14', fill: 'none' }, [
                h('path', { class: 'opacity-0 group-has-checked:opacity-100', d: 'M3 8L6 11L11 3.5', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
                h('path', { class: 'opacity-0 group-has-indeterminate:opacity-100', d: 'M3 7H11', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }),
            ]),
        ])
    },
})

const EmptyState = defineComponent({
    props: { icon: { type: Object, required: true }, title: String, text: String },
    setup(p, { slots }) {
        return () => h('div', { class: 'rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-5 py-8 text-center' }, [
            h(p.icon, { class: 'mx-auto size-8 text-text-subtle', 'stroke-width': 1.5 }),
            h('h2', { class: 'font-lexend mt-3 text-base font-semibold text-text' }, p.title),
            h('p', { class: 'mx-auto mt-1 max-w-[520px] text-sm leading-[22px] text-text-muted' }, p.text),
            slots.default?.(),
        ])
    },
})

const events = computed(() => payload.value?.events ?? [])

/* ---- Filters ------------------------------------------------------------ */

const roomKey = (event) => (event.room ? String(event.room.id) : 'none')
const dayOf = (event) => dayjs(event.start).format('YYYY-MM-DD')

/** Distinct values with how many dates carry each, in first-seen (= date) order. */
function countBy(keyOf, labelOf) {
    const seen = new Map()
    for (const event of events.value) {
        const key = keyOf(event)
        if (!seen.has(key)) seen.set(key, { key, label: labelOf(event), count: 0 })
        seen.get(key).count += 1
    }
    return [...seen.values()]
}

const roomOptions = computed(() => countBy(roomKey, (event) => event.room?.name ?? t('No room')))
/** First to last date of the project: what the range control shows while no range is set. */
const fullSpan = computed(() => [dayOf(events.value[0]), dayOf(events.value[events.value.length - 1])])
/* Remounts the control when the range is cleared, so its pill falls back to the full span. */
const rangeKey = computed(() => (filters.value.range ? 'range' : `all-${fullSpan.value.join('_')}`))
const hasSeries = computed(() => events.value.some((event) => event.seriesId))

const statusOptions = computed(() => [
    { value: 'all', label: t('All'), count: events.value.length },
    { value: 'released', label: t('On sale'), count: events.value.filter(isReleased).length },
    { value: 'draft', label: t('Not released'), count: events.value.filter((event) => !isReleased(event)).length },
])
const seriesOptions = computed(() => [
    { value: 'all', label: t('All dates') },
    { value: 'series', label: t('Only series dates') },
    { value: 'single', label: t('Only single dates') },
])

function toggleFilter(name, key, on) {
    const list = filters.value[name]
    filters.value[name] = on ? [...new Set([...list, key])] : list.filter((item) => item !== key)
}

function resetFilters() {
    filters.value = { status: 'all', rooms: [], range: null, series: 'all', q: '' }
}

const visible = computed(() => {
    const { status, rooms, range, series, q } = filters.value
    const needle = q.toLowerCase()
    return events.value.filter((event) =>
        (status === 'all' || isReleased(event) === (status === 'released'))
        && (rooms.length === 0 || rooms.includes(roomKey(event)))
        && (range === null || (dayOf(event) >= range[0] && dayOf(event) <= range[1]))
        && (series === 'all' || Boolean(event.seriesId) === (series === 'series'))
        && (needle === '' || [event.name, event.room?.name, formatDay(event.start, locale.value)].some((text) => text?.toLowerCase().includes(needle))))
})

/* The chips under the toolbar: one per set filter, each knowing how to undo itself. */
const activeChips = computed(() => {
    const chips = []
    const { status, rooms, range, series, q } = filters.value
    if (status !== 'all') chips.push({ key: 'status', label: statusOptions.value.find((option) => option.value === status).label, remove: () => { filters.value.status = 'all' } })
    for (const key of rooms) chips.push({ key: `room-${key}`, label: `${t('Room')}: ${roomOptions.value.find((room) => room.key === key)?.label ?? key}`, remove: () => toggleFilter('rooms', key, false) })
    if (range) chips.push({ key: 'range', label: `${formatDay(range[0], locale.value)} – ${formatDay(range[1], locale.value)}`, remove: () => { filters.value.range = null } })
    if (series !== 'all') chips.push({ key: 'series', label: seriesOptions.value.find((option) => option.value === series).label, remove: () => { filters.value.series = 'all' } })
    if (q !== '') chips.push({ key: 'q', label: `„${q}“`, remove: () => { filters.value.q = '' } })
    return chips
})

/* Series stay together; single dates each form a group of their own, in date order. */
const groups = computed(() => {
    const byKey = new Map()
    for (const event of visible.value) {
        const key = event.seriesId ? `series-${event.seriesId}` : `event-${event.id}`
        if (!byKey.has(key)) byKey.set(key, { key, seriesId: event.seriesId, events: [] })
        byKey.get(key).events.push(event)
    }
    return [...byKey.values()]
})

const totalPlaces = computed(() => visible.value.reduce((sum, event) => sum + (capacityOf(event) ?? 0), 0))

/* ---- Selection ---------------------------------------------------------- */

const unreleased = computed(() => visible.value.filter((event) => !isReleased(event) && isReleasable(event)))
const selectedEvents = computed(() => events.value.filter((event) => selected.value.has(event.id)))
const selectedReleased = computed(() => selectedEvents.value.filter(isReleased))
const selectedReleasable = computed(() => selectedEvents.value.filter((event) => !isReleased(event) && isReleasable(event)))
const allVisibleSelected = computed(() => visible.value.length > 0 && visible.value.every(isSelected))

function isSelected(event) {
    return selected.value.has(event.id)
}

function toggleMany(list, on) {
    const next = new Set(selected.value)
    for (const event of list) {
        if (on) next.add(event.id)
        else next.delete(event.id)
    }
    selected.value = next
}

function releasedTitle(event) {
    const parts = [event.release?.releasedAt ? dayjs(event.release.releasedAt).format('DD.MM.YYYY HH:mm') : null, event.release?.releasedBy].filter(Boolean)
    return parts.join(' · ')
}

/* Every write answers with the fresh state of the whole component. */
function applyPayload(data) {
    payload.value = data
    editing.value = null
    confirming.value = null
    viewingSales.value = null
    selected.value = new Set()
}

onMounted(async () => {
    try {
        const { data } = await axios.get(route('projects.tabs.ticketing', { project: props.project.id }))
        payload.value = data
    } catch (error) {
        loadError.value = error?.response?.data?.message || t('The ticketing data could not be loaded.')
    } finally {
        loading.value = false
    }
})
</script>
