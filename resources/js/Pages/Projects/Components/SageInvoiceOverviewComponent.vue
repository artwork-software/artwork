<template>
    <!-- Spezialkomponente: nur bei aktiver Sage-Schnittstelle sichtbar (Umgebung + Schnittstellen-Schalter) -->
    <div v-if="sageApiEnabled" class="w-full">
        <ToolbarHeader
            :icon="IconFileInvoice"
            :title="$t('Sage invoice overview')"
            icon-bg-class="bg-accent-50 text-accent-700"
            :description="rows.length ? `${rows.length} ${$t('Bookings')}` : ''"
        />

        <!-- Laden -->
        <div v-if="isLoading" class="mt-8 space-y-3" aria-busy="true">
            <BaseSkeleton v-for="n in 4" :key="n" variant="line" width="w-full" height="h-8" />
        </div>

        <!-- Fehler -->
        <div v-else-if="loadError" class="mt-8 text-sm text-danger">
            {{ loadError }}
        </div>

        <!-- Fehlende Rechte: nüchterner Hinweis statt leerer Tabelle -->
        <div
            v-else-if="!access.budget || !access.sage"
            class="mt-8 flex items-start gap-3 rounded-lg border border-border bg-surface-sunken px-4 py-3 text-sm text-text-muted"
        >
            <IconLock class="mt-0.5 size-5 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true" />
            <div class="space-y-1">
                <p v-if="!access.budget">
                    {{ $t('You need budget access to this project to see the invoices from Sage here.') }}
                </p>
                <p v-if="!access.sage">
                    {{ $t('You need the permission "{0}" to see the invoices from Sage here.', [$t('View project-related Sage data')]) }}
                </p>
            </div>
        </div>

        <!-- Leer -->
        <div v-else-if="!rows.length" class="mt-8">
            <EmptyState
                icon="IconFileInvoice"
                :title="$t('No Sage bookings')"
                :description="$t('There are no Sage bookings for this project yet.')"
            />
        </div>

        <!-- Tabelle: Sortierung KST → KTO → Belegdatum kommt vom Server; zugeordnete und
             projektbezogen nicht zugeordnete Buchungen stehen gemeinsam drin (Spalte Status) -->
        <div v-else class="mt-8 flow-root">
            <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="relative min-w-full">
                        <thead>
                            <tr>
                                <th
                                    v-for="(col, idx) in columns"
                                    :key="col.key"
                                    scope="col"
                                    :class="[
                                        'sticky top-0 z-10 bg-surface-header border-b border-border',
                                        'py-2 font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-accent-600',
                                        col.align === 'right' ? 'text-right' : 'text-left',
                                        idx === 0 ? 'pl-4 pr-3' : 'px-3',
                                    ]"
                                >
                                    <span class="truncate">{{ $t(col.label) }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-hairline bg-surface">
                            <template v-for="group in groupedRows" :key="group.key">
                                <!-- Gruppenkopf je Kostenstelle -->
                                <tr class="bg-surface-sunken">
                                    <td
                                        :colspan="columns.length"
                                        class="py-1.5 pl-4 pr-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-text-muted"
                                    >
                                        {{ $t('Cost center') }} {{ group.kst || '–' }}
                                    </td>
                                </tr>

                                <template v-for="booking in group.bookings" :key="booking.row_key">
                                    <tr
                                        class="group h-10 cursor-pointer hover:bg-surface-hover focus:outline-none focus-visible:bg-surface-hover"
                                        tabindex="0"
                                        :aria-label="$t('Booking details')"
                                        @click="openBooking(booking)"
                                        @keydown.enter.prevent="openBooking(booking)"
                                    >
                                        <td class="py-2 pl-4 pr-3 text-[13px] whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    v-if="booking.is_collective_booking"
                                                    type="button"
                                                    class="-ml-1 flex size-6 shrink-0 items-center justify-center rounded text-text-subtle hover:bg-surface-hover hover:text-text"
                                                    :aria-expanded="isExpanded(booking.row_key)"
                                                    :aria-label="$t('Collective Booking')"
                                                    @click.stop="toggleExpanded(booking.row_key)"
                                                    @keydown.enter.stop
                                                >
                                                    <IconChevronDown
                                                        class="size-4 transition-transform"
                                                        :class="isExpanded(booking.row_key) ? 'rotate-180' : ''"
                                                        stroke-width="1.5"
                                                        aria-hidden="true"
                                                    />
                                                </button>
                                                <span v-else class="size-6 shrink-0" aria-hidden="true" />
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-text">{{ booking.kreditor || '–' }}</div>
                                                    <div
                                                        v-if="booking.is_collective_booking"
                                                        class="text-[11px] text-text-subtle"
                                                    >
                                                        {{ $t('Collective Booking') }} · {{ booking.find_children?.length ?? 0 }} {{ $t('single booking') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-[13px] whitespace-nowrap">
                                            <div class="tabular-nums text-text">{{ booking.kto || '–' }}</div>
                                            <div v-if="booking.kto_title" class="text-[11px] text-text-subtle">{{ booking.kto_title }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-[13px] tabular-nums whitespace-nowrap text-text">
                                            {{ booking.kst_stelle || '–' }}
                                        </td>
                                        <td class="max-w-[28rem] truncate px-3 py-2 text-[13px] text-text" :title="booking.buchungstext">
                                            {{ booking.buchungstext }}
                                        </td>
                                        <td class="px-3 py-2 text-right text-[13px] tabular-nums whitespace-nowrap text-text">
                                            {{ formatAmount(booking.buchungsbetrag) }}
                                        </td>
                                        <td class="px-3 py-2 text-[13px] tabular-nums whitespace-nowrap text-text">
                                            {{ formatBookingDataDate(booking.belegdatum) }}
                                        </td>
                                        <td class="px-3 py-2 text-[13px] whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                                :class="booking.source === 'assigned'
                                                    ? 'bg-success-surface text-success'
                                                    : 'bg-special-orange-surface text-special-orange'"
                                            >
                                                {{ booking.source === 'assigned' ? $t('Assigned') : $t('Not assigned') }}
                                            </span>
                                        </td>
                                    </tr>

                                    <!-- Einzelbuchungen einer Sammelbuchung, leicht eingerückt -->
                                    <template v-if="booking.is_collective_booking && isExpanded(booking.row_key)">
                                        <tr
                                            v-for="child in booking.find_children"
                                            :key="`child-${booking.row_key}-${child.id}`"
                                            class="h-9 cursor-pointer bg-surface-sunken/60 text-text-muted hover:bg-surface-hover"
                                            @click="openBooking(booking)"
                                        >
                                            <td class="py-1.5 pl-12 pr-3 text-xs whitespace-nowrap">
                                                <div class="truncate">{{ child.kreditor || '–' }}</div>
                                            </td>
                                            <td class="px-3 py-1.5 text-xs whitespace-nowrap">
                                                <div class="tabular-nums">{{ child.kto || '–' }}</div>
                                                <div v-if="child.kto_title" class="text-[11px] text-text-subtle">{{ child.kto_title }}</div>
                                            </td>
                                            <td class="px-3 py-1.5 text-xs tabular-nums whitespace-nowrap">
                                                {{ child.kst_stelle || '–' }}
                                            </td>
                                            <td class="max-w-[28rem] truncate px-3 py-1.5 text-xs" :title="child.buchungstext">
                                                {{ child.buchungstext }}
                                            </td>
                                            <td class="px-3 py-1.5 text-right text-xs tabular-nums whitespace-nowrap">
                                                {{ formatAmount(child.buchungsbetrag) }}
                                            </td>
                                            <td class="px-3 py-1.5 text-xs tabular-nums whitespace-nowrap">
                                                {{ formatBookingDataDate(child.belegdatum) }}
                                            </td>
                                            <td />
                                        </tr>
                                    </template>
                                </template>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-border bg-surface">
                                <td :colspan="columns.length - 3" class="py-2 pl-4 pr-3 text-[13px] font-semibold text-text">
                                    {{ $t('Total') }}
                                </td>
                                <td class="px-3 py-2 text-right text-[13px] font-semibold tabular-nums whitespace-nowrap text-text">
                                    {{ formatAmount(total) }}
                                </td>
                                <td :colspan="2" />
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gleiches Modal wie in der Budgettabelle: Details, Einzelbuchungen, Kommentare, Löschen.
             Nicht zugeordnete Buchungen werden nur lesend gezeigt (keine Kommentare, kein Löschen) und
             blättern nur untereinander, da sie in einer anderen Tabelle liegen. -->
        <SageAssignedDataModal
            v-if="showBookingModal"
            :show="showBookingModal"
            :cell="modalCell"
            :initial-index="modalIndex"
            :read-only="modalSource !== 'assigned'"
            @close="closeBookingModal"
            @budget-updated="loadBookings"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { IconChevronDown, IconFileInvoice, IconLock } from '@tabler/icons-vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import BaseSkeleton from '@/Artwork/Feedback/BaseSkeleton.vue'
import SageAssignedDataModal from '@/Layouts/Components/SageAssignedDataModal.vue'
import { formatBookingDataDate } from '@/Layouts/Components/Budget/bookingDate.js'
import { useTranslation } from '@/Composeables/Translation.js'

defineOptions({ name: 'SageInvoiceOverviewComponent' })

const props = defineProps({
    project: { type: Object, default: null },
    projectId: { type: [Number, String], default: null },
})

const $t = useTranslation()
const page = usePage()

const sageApiEnabled = computed(() => Boolean(page.props.sageApiEnabled))
const resolvedProjectId = computed(() => props.project?.id ?? props.projectId)

const columns = [
    { key: 'kreditor', label: 'Creditor / name' },
    { key: 'kto', label: 'KTO' },
    { key: 'kst_stelle', label: 'KST' },
    { key: 'buchungstext', label: 'Designation' },
    { key: 'buchungsbetrag', label: 'Amount', align: 'right' },
    { key: 'belegdatum', label: 'Document date' },
    { key: 'source', label: 'Status' },
]

const isLoading = ref(false)
const loadError = ref('')
const rows = ref([])
const total = ref(0)
const access = ref({ budget: true, sage: true })
const expanded = ref({})

const showBookingModal = ref(false)
const modalIndex = ref(0)
const modalSource = ref('assigned')
// Das Modal erwartet eine Budget-Zelle mit sage_assigned_data; hier bekommt es alle Zeilen derselben
// Herkunft, sodass Vor/Zurück durch diese Rechnungen blättert und ein Löschen die Tabelle direkt aktualisiert.
const modalRows = computed(() => rows.value.filter((row) => row.source === modalSource.value))
const modalCell = computed(() => ({ sage_assigned_data: modalRows.value }))

// Server liefert bereits nach KST sortiert → Gruppen in Reihenfolge des ersten Auftretens
const groupedRows = computed(() => {
    const groups = []
    const byKst = new Map()
    for (const booking of rows.value) {
        const kst = String(booking.kst_stelle ?? '').trim()
        if (!byKst.has(kst)) {
            const group = { key: kst || '__none__', kst, bookings: [] }
            byKst.set(kst, group)
            groups.push(group)
        }
        byKst.get(kst).bookings.push(booking)
    }
    return groups
})

function isExpanded(id) {
    return Boolean(expanded.value[id])
}

function toggleExpanded(id) {
    expanded.value = { ...expanded.value, [id]: !expanded.value[id] }
}

function formatAmount(value) {
    const number = Number(String(value ?? 0).replace(',', '.'))
    if (Number.isNaN(number)) {
        return String(value ?? '')
    }
    return `${number.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} EUR`
}

function openBooking(booking) {
    modalSource.value = booking.source
    const index = modalRows.value.findIndex((row) => row.row_key === booking.row_key)
    if (index < 0) {
        return
    }
    modalIndex.value = index
    showBookingModal.value = true
}

function closeBookingModal() {
    showBookingModal.value = false
}

async function loadBookings() {
    if (!sageApiEnabled.value || !resolvedProjectId.value) {
        return
    }
    isLoading.value = rows.value.length === 0
    loadError.value = ''
    try {
        const { data } = await axios.get(route('projects.tabs.sage-invoices', { project: resolvedProjectId.value }))
        rows.value = Array.isArray(data?.rows) ? data.rows : []
        total.value = Number(data?.total ?? 0)
        access.value = {
            budget: data?.access?.budget !== false,
            sage: data?.access?.sage !== false,
        }
        // Modal offen und die Liste ist geschrumpft (Löschen) → Index in den gültigen Bereich holen
        if (showBookingModal.value && modalRows.value.length === 0) {
            showBookingModal.value = false
        }
    } catch (error) {
        loadError.value = error?.response?.data?.message || $t('Data could not be loaded.')
    } finally {
        isLoading.value = false
    }
}

onMounted(loadBookings)
</script>
