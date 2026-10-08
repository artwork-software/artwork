<template>
    <ArtworkBaseModal :title="$t('Edit sale')" :description="single ? formatWhen(single, locale) : $t('{count} dates selected', { count: events.length })" modal-size="sm:max-w-2xl" @close="$emit('close')">
        <div class="flex flex-col gap-4">
            <!-- Which dates the form applies to: one by name, several as a list. -->
            <p v-if="single" class="text-[13px] leading-5 text-text-subtle">
                <span class="font-medium text-text">{{ single.name }}</span> ·
                {{ !single.venue ? $t('The room is not synced with Artwork-Tickets; the price classes have to be entered by hand.')
                    : seated ? $t('The room has a seating plan: its price classes and places are fixed. Only the prices can change for this date.')
                        : $t('Prefilled from the room. Change places or prices for this date, take a price class off it, or add one only this date has.') }}
            </p>
            <div v-else class="rounded-md border border-border-subtle bg-surface-sunken px-3.5 py-3">
                <p class="font-lexend text-[11px] font-semibold uppercase tracking-[.02em] text-text-subtle">{{ $t('Applies to {count} dates', { count: events.length }) }}</p>
                <ul class="mt-1.5 flex flex-wrap gap-1.5">
                    <li v-for="event in events" :key="event.id" class="inline-flex items-center gap-1.5 rounded-md border border-border-subtle bg-surface px-2 py-0.5 text-xs tabular-nums text-text">
                        <span class="size-1.5 rounded-full" :style="{ backgroundColor: event.eventType?.hexCode ?? '#999' }"></span>{{ formatWhen(event, locale) }}
                    </li>
                </ul>
                <p v-if="!uniform" class="mt-2 text-xs leading-5 text-text-muted">{{ $t('The selected dates sell differently right now: prices, description or reductions. Prefilled from the first date; what you save applies to all of them.') }}</p>
                <p v-else-if="mixedPlan" class="mt-2 text-xs leading-5 text-danger">{{ $t('The selected dates are in different rooms, one of them with a seating plan. Edit them room by room.') }}</p>
                <p v-else-if="!sharedVenue" class="mt-2 text-xs leading-5 text-text-muted">{{ $t('The selected dates are in different rooms, so there are no room defaults to fall back on.') }}</p>
            </div>

            <div class="grid grid-cols-[minmax(0,1fr)_96px_140px_28px] gap-2.5 font-lexend text-xs font-medium text-[#3F424A]">
                <span>{{ $t('Price class') }}</span><span>{{ $t('Places') }}</span><span>{{ $t('Price') }}</span><span></span>
            </div>
            <div class="flex flex-col gap-2">
                <div v-for="(cls, index) in classes" :key="cls.zone_key ?? `custom-${index}`" class="grid grid-cols-[minmax(0,1fr)_96px_140px_28px] items-start gap-2.5">
                    <div class="min-w-0">
                        <p v-if="seated" class="flex h-8 items-center truncate text-[13px] font-medium text-text">{{ cls.name }}</p>
                        <BaseInput v-else :id="`ticketing-class-${index}-name`" v-model="cls.name" :label="$t('Price class')" :show-label="false" :placeholder="$t('e.g. Premium')" required is-small />
                        <p v-if="differsFromRoom(cls)" class="mt-1 text-xs text-text-subtle tabular-nums">{{ $t('Room default: {places} places · {price}', { places: defaultFor(cls.zone_key).quota, price: formatEuro(defaultFor(cls.zone_key).price_cents) }) }}</p>
                    </div>
                    <p v-if="seated" class="flex h-8 items-center justify-end text-[13px] tabular-nums text-text-subtle" :title="$t('From the seating plan')">{{ cls.quotaInput }}</p>
                    <BaseInput v-else :id="`ticketing-class-${index}-quota`" v-model="cls.quotaInput" type="number" :min="0" :label="$t('Places')" :show-label="false" required is-small class="tabular-nums" />
                    <div class="flex h-8 overflow-hidden rounded-md border border-border bg-surface">
                        <input v-model="cls.priceInput" type="number" :min="0" step="0.01" required :aria-label="$t('Price')" class="flex-1 min-w-0 border-0 px-2.5 text-[13px] tabular-nums focus:ring-0" />
                        <span class="flex items-center px-2 bg-surface-sunken border-l border-border-subtle text-xs text-text-subtle">€</span>
                    </div>
                    <span v-if="seated"></span>
                    <button v-else type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface disabled:opacity-40 disabled:hover:bg-transparent"
                            :disabled="classes.length === 1" :aria-label="$t('Remove')" :title="$t('Not sold on these dates')" @click="classes.splice(index, 1)">
                        <IconTrash class="size-[15px]" />
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <button v-if="!seated" type="button" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-accent-600 hover:underline" @click="classes.push(customClass())">
                    <IconPlus class="size-3.5" stroke-width="2.5" />{{ $t('Add price class') }}
                </button>
                <button v-for="missing in seated ? [] : missingRoomClasses" :key="missing.zone_key" type="button" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-accent-600 hover:underline" @click="classes.push(toRow(missing))">
                    <IconPlus class="size-3.5" stroke-width="2.5" />{{ missing.name }}
                </button>
                <button v-if="sharedVenue" type="button" class="ml-auto text-[13px] text-text-subtle hover:text-text" @click="resetToRoom">{{ $t('Reset to room') }}</button>
            </div>

            <p class="text-xs text-text-subtle">{{ $t('{count} places in total.', { count: totalPlaces }) }}</p>

            <section class="flex flex-col gap-3 border-t border-border-hairline pt-4">
                <BaseCheckbox id="ticketing-date-own-text" :model-value="ownText" :label="$t('Own description in the shop')"
                              :description="$t('Otherwise the shop shows the description of the production.')" @update:model-value="setOwnText" />
                <template v-if="ownText">
                    <div class="flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                        <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                        <span>{{ single ? $t('This text replaces the description of the production for this date in the shop.') : $t('This text replaces the description of the production for these {count} dates in the shop.', { count: events.length }) }}</span>
                    </div>
                    <BaseRichTextEditor id="ticketing-date-description" v-model="description" :label="$t('Description for the shop')" :placeholder="$t('What the audience should know.')" />
                </template>
            </section>

            <section v-if="reductions.length" class="border-t border-border-hairline pt-4">
                <span class="font-lexend mb-1.5 block text-xs font-medium text-[#3F424A]">{{ $t('Reductions') }}</span>
                <TicketingReductionChips v-model="offeredIds" :reductions="reductions" :label="$t('Reductions')" />
                <p class="mt-2 text-xs leading-[18px] text-text-subtle">
                    <template v-if="ownReductions">
                        {{ $t('Differs from the production.') }}
                        <button type="button" class="font-medium text-accent-600 hover:underline" @click="offeredIds = [...granted]">{{ $t('Reset to production') }}</button>
                    </template>
                    <template v-else>{{ $t('As granted for the production. Switch a reduction off or on to depart from it.') }}</template>
                </p>
            </section>

            <div v-if="releasedCount > 0" class="flex items-start gap-2.5 rounded-md border border-info-border bg-info-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconInfoCircle class="size-4 shrink-0 mt-0.5 text-info" />
                <span>{{ single ? $t('This date is on sale. Saving updates it in Artwork-Tickets right away; tickets already sold keep their price.') : $t('{count} of these dates are on sale. Saving updates them in Artwork-Tickets right away; tickets already sold keep their price.', { count: releasedCount }) }}</span>
            </div>

            <p v-if="error" class="text-sm text-danger">{{ error }}</p>
        </div>

        <!-- The series question takes the footer's place, so it is clearly part of this form. -->
        <template #footer>
            <SeriesScopeChoice v-if="askScope" class="w-full" :count="seriesEvents.length" @cancel="askScope = false" @all="submit(seriesEvents)" @single="submit(events)" />
            <template v-else>
                <button type="button" class="ui-button" :disabled="saving" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button type="button" class="ui-button-add" :disabled="saving || !valid" @click="save">
                    {{ saving ? $t('Saving…') : single ? $t('Save') : $t('Save for {count} dates', { count: events.length }) }}
                </button>
            </template>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconInfoCircle, IconPlus, IconTrash } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseRichTextEditor from '@/Artwork/Inputs/BaseRichTextEditor.vue'
import SeriesScopeChoice from '@/Layouts/Components/SeriesScopeChoice.vue'
import TicketingReductionChips from '@/Pages/Projects/Tab/Components/Ticketing/TicketingReductionChips.vue'
import { classesOf, defaultClassesOf, formatEuro, formatWhen, fromCents, grantedIdsOf, isReleased, offeredIdsOf, seriesOf, toCents } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

const props = defineProps({
    projectId: { type: Number, required: true },
    /** The dates the form applies to; one opens the series question, several save as a batch. */
    events: { type: Array, required: true },
    /** All dates of the component, to find the rest of a series. */
    allEvents: { type: Array, required: true },
    /** The production's shop settings: its text and the reductions it grants, what a date departs from. */
    production: { type: Object, required: true },
    /** The house reductions. */
    reductions: { type: Array, required: true },
})

const emit = defineEmits(['close', 'saved'])

const { locale } = useI18n()

const single = computed(() => (props.events.length === 1 ? props.events[0] : null))
const seriesEvents = computed(() => (single.value ? seriesOf(single.value, props.allEvents) : []))
const releasedCount = computed(() => props.events.filter(isReleased).length)

/* Room defaults only mean something when every date sits in the same synced room. */
const sharedVenue = computed(() => {
    const first = props.events[0].venue
    return first && props.events.every((event) => event.venue?.id === first.id) ? first : null
})
/** What the modal edits of one date, comparable across dates. */
function saleOf(event) {
    return JSON.stringify([classesOf(event), event.release?.description ?? null, [...offeredIdsOf(event, props.production, props.reductions)].sort()])
}
const uniform = computed(() => props.events.every((event) => saleOf(event) === saleOf(props.events[0])))

/* Rows keep the inputs as strings; the payload turns them into cents and integers. */
function toRow(cls) {
    return { zone_key: cls.zone_key, name: cls.name, quotaInput: String(cls.quota ?? 0), priceInput: fromCents(cls.price_cents ?? 0) }
}

function customClass() {
    return { zone_key: null, name: '', quotaInput: '', priceInput: '' }
}

/* A seating plan fixes the room's classes and their places; a date only prices them. */
const seated = computed(() => sharedVenue.value?.seated === true)
const mixedPlan = computed(() => !sharedVenue.value && props.events.some((event) => event.venue?.seated))
const roomClasses = computed(() => (sharedVenue.value ? defaultClassesOf(props.events[0]) : []))

/** The saved classes; on a seating plan the room's, carrying the date's saved prices. */
function initialRows() {
    const saved = classesOf(props.events[0])
    if (!seated.value) return saved.map(toRow)
    return roomClasses.value.map((room) => toRow({ ...room, price_cents: saved.find((cls) => cls.zone_key === room.zone_key)?.price_cents ?? room.price_cents }))
}

const classes = ref(initialRows())
const ownText = ref(Boolean(props.events[0].release?.description))
const description = ref(props.events[0].release?.description ?? '')
const granted = grantedIdsOf(props.production, props.reductions)
const offeredIds = ref(offeredIdsOf(props.events[0], props.production, props.reductions))
const saving = ref(false)
const error = ref('')
const askScope = ref(false)

const missingRoomClasses = computed(() => roomClasses.value.filter((room) => !classes.value.some((cls) => cls.zone_key === room.zone_key)))
const totalPlaces = computed(() => classes.value.reduce((sum, cls) => sum + (Number(cls.quotaInput) || 0), 0))

const valid = computed(() => !mixedPlan.value && classes.value.length > 0 && classes.value.every((cls) =>
    cls.name.trim() !== '' && toCents(cls.priceInput) !== null && cls.quotaInput !== '' && Number(cls.quotaInput) >= 0))

function defaultFor(zoneKey) {
    return roomClasses.value.find((room) => room.zone_key === zoneKey) ?? null
}

/* The room's default is only worth a line once the date departs from it. */
function differsFromRoom(cls) {
    const room = defaultFor(cls.zone_key)
    return room !== null && ((!seated.value && Number(cls.quotaInput) !== room.quota) || toCents(cls.priceInput) !== room.price_cents)
}

function resetToRoom() {
    classes.value = roomClasses.value.map(toRow)
}

/* An own text starts from the production's, which is usually only to be adapted. */
function setOwnText(on) {
    ownText.value = on
    if (on && description.value.trim() === '') {
        description.value = props.production.description || props.production.fallback.description || ''
    }
}

/* Only the departures are stored, so a date follows later changes to the production elsewhere. */
const departures = computed(() => props.reductions
    .filter((reduction) => offeredIds.value.includes(reduction.id) !== granted.includes(reduction.id))
    .map((reduction) => ({ id: reduction.id, offered: offeredIds.value.includes(reduction.id) })))
const ownReductions = computed(() => departures.value.length > 0)

/* A single date of a series asks first: the whole series, or just this one, which then leaves it. */
function save() {
    if (seriesEvents.value.length > 1) {
        askScope.value = true
        return
    }
    submit(props.events)
}

async function submit(targets) {
    askScope.value = false
    saving.value = true
    error.value = ''
    try {
        const { data } = await axios.put(route('projects.tabs.ticketing.draft', { project: props.projectId }), {
            event_ids: targets.map((event) => event.id),
            classes: classes.value.map((cls) => ({
                zone_key: cls.zone_key,
                name: cls.name.trim(),
                price_cents: toCents(cls.priceInput),
                quota: Number(cls.quotaInput),
            })),
            description: ownText.value && description.value.trim() !== '' ? description.value : null,
            reductions: departures.value,
        })
        emit('saved', data)
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || Object.values(requestError?.response?.data?.errors ?? {}).flat()[0] || requestError.message
    } finally {
        saving.value = false
    }
}
</script>
