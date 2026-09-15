<template>
    <div>
        <!-- Stepper -->
        <ol class="flex items-center pb-5 mb-6 border-b border-border-hairline">
            <template v-for="(label, index) in steps" :key="label">
                <li class="flex items-center gap-2.5">
                    <span class="font-lexend flex size-[26px] items-center justify-center rounded-full text-xs font-semibold"
                          :class="index < step ? 'bg-accent-600 text-white'
                              : index === step ? 'bg-accent-50 border-2 border-accent-600 text-accent-600'
                              : 'bg-surface-sunken border border-border-subtle text-text-subtle'">
                        <IconCheck v-if="index < step" class="size-3.5" stroke-width="2.5" />
                        <span v-else>{{ index + 1 }}</span>
                    </span>
                    <span class="font-lexend text-[13px]" :class="index === step ? 'font-semibold text-accent-600' : index < step ? 'font-medium text-text' : 'font-medium text-text-subtle'">{{ label }}</span>
                </li>
                <li v-if="index < steps.length - 1" aria-hidden="true" class="flex-1 h-px mx-3.5" :class="index < step ? 'bg-accent-600' : 'bg-border-subtle'"></li>
            </template>
        </ol>

        <div class="mb-5">
            <h2 class="font-lexend text-lg font-semibold text-text mb-1">{{ steps[step] }}</h2>
            <p class="text-[13px] leading-5 text-text-subtle max-w-[720px]">{{ intros[step] }}</p>
        </div>

        <!-- 1 · House -->
        <section v-if="step === 0" class="max-w-[900px]">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <BaseInput id="tickets-house-name" v-model="form.house.name" :label="$t('House name')" required />
                    <StatusLine :state="nameState" :message="nameMessage" />
                </div>
                <div>
                    <label for="tickets-house-slug" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
                        {{ $t('Address in the ticket shop') }}<span class="text-danger">*</span>
                    </label>
                    <div class="flex h-10 overflow-hidden rounded-md border bg-surface" :class="slugState === 'available' || slugState === 'checking' || slugState === 'idle' ? 'border-border' : 'border-danger-border'">
                        <span class="flex items-center px-2.5 bg-surface-sunken border-r border-border-subtle font-mono text-xs text-text-subtle whitespace-nowrap">{{ shopHost }}/</span>
                        <input id="tickets-house-slug" v-model="form.house.slug" type="text" required
                               class="flex-1 min-w-0 border-0 px-3 text-sm text-text focus:ring-0"
                               @input="slugTouched = true" />
                    </div>
                    <StatusLine :state="slugState" :message="slugMessage" />
                </div>
            </div>
            <div class="mt-5 flex items-start gap-2.5 rounded-md border px-3.5 py-3 text-[13px] leading-5 text-text"
                 :class="ownerState === 'taken' ? 'bg-danger-surface border-danger-border' : 'bg-info-surface border-info-border'">
                <IconAlertTriangle v-if="ownerState === 'taken'" class="size-4 shrink-0 mt-0.5 text-danger" />
                <IconInfoCircle v-else class="size-4 shrink-0 mt-0.5 text-info" />
                <span><strong class="font-medium">{{ userEmail }}</strong> {{ ownerState === 'taken' ? $t('already owns a house in artwork tickets. An account can only found one house; connect with a different account or attach the existing house later.') : $t('becomes the owner of the house and can sign in to artwork tickets with an e-mail code afterwards.') }}</span>
            </div>
        </section>

        <!-- 2 · Rooms -->
        <section v-else-if="step === 1" class="max-w-[1040px]">
            <RoomSyncEditor :rooms="roomDrafts" :countries="countries" />
        </section>

        <!-- 3 · Reductions -->
        <section v-else-if="step === 2" class="max-w-[1040px]">
            <ReductionsEditor :reductions="reductionDrafts">
                <template #hint>{{ $t('Optional. Without reductions everyone pays the price of the price class; you can add them in artwork tickets at any time.') }}</template>
            </ReductionsEditor>
        </section>

        <!-- 4 · Summary -->
        <section v-else class="max-w-[1040px] text-[13px]">
            <div class="grid gap-4 sm:grid-cols-2">
                <SummaryCard :title="$t('House')" @edit="step = 0">
                    <dl class="grid grid-cols-[120px_minmax(0,1fr)] gap-x-3 gap-y-2">
                        <dt class="text-text-subtle">{{ $t('Name') }}</dt><dd>{{ form.house.name }}</dd>
                        <dt class="text-text-subtle">{{ $t('Address') }}</dt><dd class="font-mono text-xs">{{ shopHost }}/{{ form.house.slug }}</dd>
                        <dt class="text-text-subtle">{{ $t('Owner') }}</dt><dd>{{ userEmail }}</dd>
                    </dl>
                </SummaryCard>
                <SummaryCard :title="$t('Reductions')" @edit="step = 2">
                    <span v-if="reductionDrafts.length === 0" class="text-text-subtle">{{ $t('None') }}</span>
                    <ul v-else class="flex flex-col gap-2">
                        <li v-for="(r, ri) in reductionDrafts" :key="ri" class="flex justify-between gap-4">
                            <span>{{ r.name }}</span>
                            <span class="text-text-subtle">{{ reductionSummary(r) }}</span>
                        </li>
                    </ul>
                </SummaryCard>
            </div>
            <div class="mt-4">
                <SummaryCard :title="$t('Rooms · {selected} of {total}', { selected: selectedRooms.length, total: roomDrafts.length })" @edit="step = 1">
                    <span v-if="selectedRooms.length === 0" class="text-text-subtle">{{ $t('None selected') }}</span>
                    <div v-else class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        <div v-for="room in selectedRooms" :key="room.id">
                            <span class="font-lexend block text-[13px] font-medium text-text">{{ room.name }} <span class="font-normal text-text-subtle">· {{ $t('{count} places', { count: zonePlaces(room) }) }}</span></span>
                            <span class="block text-xs text-text-subtle mb-1.5">{{ addressLine(room) || $t('no address') }}</span>
                            <table class="w-full border-collapse">
                                <tr v-for="(zone, zi) in room.zones" :key="zi">
                                    <td class="py-1">{{ zone.name }}</td>
                                    <td class="py-1 text-right text-text-subtle tabular-nums">{{ $t('{count} places', { count: Number(zone.capacity) || 0 }) }}</td>
                                    <td class="py-1 pl-4 text-right tabular-nums" :class="toCents(zone.price) === null ? 'text-text-subtle' : ''">{{ toCents(zone.price) === null ? $t('no default price') : formatEuro(toCents(zone.price)) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <p v-if="skippedRooms.length" class="mt-3 text-xs text-text-subtle">{{ $t('{rooms} stay out.', { rooms: skippedRooms.map((room) => room.name).join(', ') }) }}</p>
                </SummaryCard>
            </div>
            <div class="mt-5 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('This installation can only be connected to one house. Rooms and reductions can be synced again afterwards; house and address are changed in artwork tickets.') }}</span>
            </div>
            <ul v-if="Object.keys(form.errors).length" class="mt-4 rounded-md border border-danger-border bg-danger-surface p-3 text-danger">
                <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
            </ul>
        </section>

        <!-- Footer -->
        <div class="mt-7 flex items-center justify-between border-t border-border-hairline pt-4">
            <button type="button" class="text-[13px] text-text-subtle hover:text-text" :disabled="form.processing" @click="$emit('cancel')">{{ $t('Cancel') }}</button>
            <div class="flex items-center gap-2">
                <button type="button" class="ui-button" :disabled="step === 0 || form.processing" @click="step--">
                    <IconArrowLeft class="size-3.5" />{{ $t('Back') }}
                </button>
                <button v-if="step < steps.length - 1" type="button" class="ui-button-add" :disabled="!stepValid" @click="step++">
                    {{ $t('Continue') }}<IconArrowRight class="size-3.5" />
                </button>
                <button v-else type="button" class="ui-button-add" :disabled="form.processing" @click="submit">
                    <IconPlugConnected class="size-[15px]" />{{ form.processing ? $t('Connecting…') : $t('Connect now') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { IconAlertTriangle, IconArrowLeft, IconArrowRight, IconCheck, IconInfoCircle, IconPlugConnected, IconX } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import RoomSyncEditor from '@/Pages/Settings/Tickets/RoomSyncEditor.vue'
import ReductionsEditor from '@/Pages/Settings/Tickets/ReductionsEditor.vue'
import { formatEuro, reductionPayload, reductionsValid, roomDraft, roomPayload, roomsValid, toCents, zonePlaces } from '@/Pages/Settings/Tickets/drafts.js'

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    houseDefaults: { type: Object, required: true },
    countries: { type: Array, required: true },
    userEmail: { type: String, required: true },
    ticketsUrl: { type: String, default: '' },
})

defineEmits(['cancel'])

const { t } = useI18n()

const steps = [t('House'), t('Rooms'), t('Reductions'), t('Summary')]
const intros = [
    t('This is how the house appears in artwork tickets. Both can be changed there later.'),
    t('Which rooms sell tickets? Each becomes a venue in artwork tickets with its address and the price classes you define here. Rooms can be synced again later.'),
    t('Reductions apply house-wide; which ones a production grants is decided per production. Percent of the ticket price or a fixed amount off.'),
    t('Check what will be created. Only "Connect now" creates anything in artwork tickets.'),
]
const step = ref(0)

const shopHost = computed(() => props.ticketsUrl.replace(/^https?:\/\//, '').replace(/\/$/, ''))

/* One line under a field: icon and colour follow the check's state. */
const StatusLine = defineComponent({
    props: { state: String, message: String },
    setup(p) {
        return () => {
            if (!p.message) return null
            const good = p.state === 'available'
            const pending = p.state === 'checking' || p.state === 'idle'
            const color = good ? 'text-success' : pending ? 'text-text-subtle' : 'text-danger'
            const icon = good ? h(IconCheck, { class: 'size-3.5', 'stroke-width': 2.5 }) : pending ? null : h(IconX, { class: 'size-3.5', 'stroke-width': 2.5 })
            return h('p', { class: ['mt-1.5 flex items-center gap-1.5 text-xs', color] }, [icon, p.message])
        }
    },
})

/* Summary card with its "Edit" link in the header. */
const SummaryCard = defineComponent({
    props: { title: String },
    emits: ['edit'],
    setup(p, { slots, emit }) {
        return () => h('div', { class: 'rounded-lg border border-border-subtle bg-surface' }, [
            h('div', { class: 'flex items-center justify-between px-4 py-3 border-b border-border-hairline' }, [
                h('span', { class: 'font-lexend text-[13px] font-semibold text-text' }, p.title),
                h('button', { type: 'button', class: 'text-xs font-medium text-accent-600 hover:underline', onClick: () => emit('edit') }, t('Edit')),
            ]),
            h('div', { class: 'px-4 py-3.5' }, slots.default?.()),
        ])
    },
})

/* Mirrors the address rules of artwork tickets (umlauts spelled out, a–z, 0–9, hyphen). */
function slugify(value) {
    return value
        .toLowerCase()
        .replaceAll('ä', 'ae').replaceAll('ö', 'oe').replaceAll('ü', 'ue').replaceAll('ß', 'ss')
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
}

const form = useForm({
    house: { name: props.houseDefaults.name, slug: props.houseDefaults.slug },
    rooms: [],
    reductions: [],
})

/* The address follows the name until someone edits it by hand. */
const slugTouched = ref(false)
watch(() => form.house.name, (name) => {
    if (!slugTouched.value) form.house.slug = slugify(name)
})

/* One debounced round trip answers name, address and owner at once. */
const slugState = ref('idle')
const nameState = ref('idle')
const ownerState = ref('idle')
let checkTimer = null

function localSlugProblem(slug) {
    if (!slug) return 'idle'
    if (slug.length < 2 || slugify(slug) !== slug) return 'invalid'
    return null
}

watch(() => [form.house.name, form.house.slug], ([name, slug]) => {
    clearTimeout(checkTimer)
    nameState.value = name.trim().length < 2 ? 'short' : 'checking'
    slugState.value = localSlugProblem(slug) ?? 'checking'
    if (nameState.value !== 'checking' && slugState.value !== 'checking') return
    checkTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get(route('settings.tickets.availability'), { params: { slug, name } })
            if (slugState.value === 'checking') slugState.value = data.slug.available ? 'available' : data.slug.reason
            if (nameState.value === 'checking') nameState.value = data.name.available ? 'available' : 'taken'
            ownerState.value = data.owner.available ? 'available' : 'taken'
        } catch {
            if (slugState.value === 'checking') slugState.value = 'unreachable'
            if (nameState.value === 'checking') nameState.value = 'unreachable'
        }
    }, 400)
}, { immediate: true })

const slugMessage = computed(() => ({
    checking: t('Checking…'),
    available: t('Available'),
    taken: t('This address is already taken.'),
    reserved: t('This address is reserved.'),
    invalid: t('Only lowercase letters, digits and hyphens.'),
    unreachable: t('The address could not be checked.'),
}[slugState.value] ?? ''))

const nameMessage = computed(() => ({
    checking: t('Checking…'),
    available: t('Available'),
    short: t('At least 2 characters.'),
    taken: t('A house with this name already exists in artwork tickets.'),
    unreachable: t('The name could not be checked.'),
}[nameState.value] ?? ''))

const roomDrafts = ref(props.rooms.map((room) => roomDraft(room, props.houseDefaults.address, t('Free seating'))))

const selectedRooms = computed(() => roomDrafts.value.filter((room) => room.selected))
const skippedRooms = computed(() => roomDrafts.value.filter((room) => !room.selected))

function addressLine(room) {
    return [room.street.trim(), [room.postal_code.trim(), room.city.trim()].filter(Boolean).join(' ')].filter(Boolean).join(', ')
}

const reductionDrafts = ref([
    { name: t('Reduced'), kind: 'percent', value: '50', requires_proof: true, default_enabled: true },
])

function reductionSummary(r) {
    const parts = [r.kind === 'percent' ? `${r.value} %` : `${formatEuro(toCents(r.value) ?? 0)} ${t('off')}`]
    if (r.requires_proof) parts.push(t('Proof'))
    if (r.default_enabled) parts.push(t('Default on'))
    return parts.join(' · ')
}

const stepValid = computed(() => {
    if (step.value === 0) return nameState.value === 'available' && slugState.value === 'available' && ownerState.value === 'available'
    if (step.value === 1) return roomsValid(selectedRooms.value)
    if (step.value === 2) return reductionsValid(reductionDrafts.value)
    return true
})

function submit() {
    form.rooms = selectedRooms.value.map(roomPayload)
    form.reductions = reductionDrafts.value.map(reductionPayload)
    form.post(route('settings.tickets.connect'), { preserveScroll: true })
}
</script>
