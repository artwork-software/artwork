<template>
    <ArtworkBaseModal
        :title="$t('Export shift plan')"
        :description="exportKind.id === 'weekly'
            ? $t('Create the weekly plan as a PDF: one calendar week with shifts, days off and absences, plus a non-binding preview of the following week.')
            : $t('Create a monthly overview of the shift plan as a PDF. One month is exported per page.')"
        @close="$emit('close')"
    >
        <div class="p-4 space-y-5">
            <!-- Art des Exports: Monatsübersicht oder Wochenplan -->
            <ArtworkBaseListbox
                :model-value="exportKind"
                @update:model-value="exportKind = $event"
                :items="exportKinds"
                by="id"
                option-label="name"
                use-translations
                :label="$t('Kind of export')"
                :enable-search="false"
            />

            <!-- Wochenplan: ein Tag der gewünschten Kalenderwoche -->
            <div v-if="exportKind.id === 'weekly'">
                <BaseInput
                    id="usp-export-week"
                    type="date"
                    v-model="weekDate"
                    :label="$t('Day in the desired calendar week')"
                />
                <p class="mt-1 text-xs text-text-subtle">
                    {{ $t('The plan covers Monday to Sunday of this week.') }}
                </p>
            </div>

            <!-- Monatsbereich -->
            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput
                    id="usp-export-start-month"
                    type="month"
                    v-model="startMonth"
                    :label="$t('Start month')"
                />
                <BaseInput
                    id="usp-export-end-month"
                    type="month"
                    v-model="endMonth"
                    :label="$t('End month')"
                />
            </div>

            <!-- Papierformat -->
            <ArtworkBaseListbox
                :model-value="selectedPaperSize"
                @update:model-value="selectedPaperSize = $event"
                :items="paperSizes"
                by="id"
                option-label="name"
                :label="$t('Paper size')"
                :enable-search="false"
            />

            <p v-if="exportKind.id !== 'weekly'" class="text-xs text-text-subtle">
                {{ $t('The export is always created in landscape format (Outlook-style month calendar).') }}
            </p>

            <!-- Aktionen -->
            <div class="flex items-center justify-end gap-x-3 pt-2">
                <button
                    type="button"
                    class="text-sm text-text-muted hover:text-text"
                    @click="$emit('close')"
                >
                    {{ $t('Cancel') }}
                </button>
                <BaseUIButton
                    @click="createPdf"
                    :label="$t('Export PDF')"
                    icon="IconFileExport"
                    :disabled="processing || (exportKind.id === 'weekly' && !weekDate)"
                    is-add-button
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    userToEditId: { type: Number, required: true },
    type: { type: String, default: 'user' },
    dateValue: { type: Array, required: true } // [start, end] YYYY-MM-DD
})

const emit = defineEmits(['close'])

const page = usePage()

// Name der exportierten Person (Page-Prop je nach Seite: User, Freelancer oder
// Dienstleister) – für den Dateinamen.
const userName = (() => {
    const nameOf = (u) => {
        if (!u) return ''
        return (
            u.full_name ||
            [u.first_name, u.last_name].filter(Boolean).join(' ').trim() ||
            u.provider_name ||
            u.name ||
            ''
        )
    }
    return (
        nameOf(page.props?.user_to_edit) ||
        nameOf(page.props?.freelancer) ||
        nameOf(page.props?.serviceProvider) ||
        ''
    )
})()

// "2026-05" -> "Mai 26"
const monthLabel = (ym) => {
    const [y, m] = String(ym).split('-').map(Number)
    if (!y || !m) return ''
    const name = new Date(y, m - 1, 1).toLocaleString('de-DE', { month: 'long' })
    return `${name} ${String(y).slice(-2)}`
}

// Ungültige Dateinamen-Zeichen entfernen
const sanitizeFilename = (s) => s.replace(/[\\/:*?"<>|]+/g, '').replace(/\s+/g, ' ').trim()

// Vorausgewählter Monat = der aktuell im Einsatzplan angezeigte Monat (Start des Zeitraums).
// Start und Ende identisch, damit ein direkter Klick genau diesen Monat exportiert.
const initialMonth = (() => {
    const start = Array.isArray(props.dateValue) && props.dateValue[0]
        ? String(props.dateValue[0])
        : null
    if (start && start.length >= 7) return start.substring(0, 7)
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
})()

const paperSizes = [
    { id: 'a4', name: 'A4' },
    { id: 'a3', name: 'A3' }
]
const selectedPaperSize = ref(paperSizes[0])

// Monatsübersicht (bisher) oder Wochenplan (eine Kalenderwoche plus Vorschau auf die Folgewoche)
const exportKinds = [
    { id: 'monthly', name: 'Monthly overview' },
    { id: 'weekly', name: 'Weekly plan' }
]
const exportKind = ref(exportKinds[0])

// Vorausgewählte Woche = Start des im Einsatzplan angezeigten Zeitraums, sonst heute
const weekDate = ref((() => {
    const start = Array.isArray(props.dateValue) && props.dateValue[0] ? String(props.dateValue[0]) : null
    if (start && /^\d{4}-\d{2}-\d{2}/.test(start)) return start.substring(0, 10)
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
})())

// "2026-03-11" -> "KW 11" (ISO-Woche)
const isoWeekLabel = (ymd) => {
    const [y, m, d] = String(ymd).split('-').map(Number)
    if (!y || !m || !d) return ''
    const date = new Date(Date.UTC(y, m - 1, d))
    const day = date.getUTCDay() || 7
    date.setUTCDate(date.getUTCDate() + 4 - day)
    const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1))
    const week = Math.ceil(((date - yearStart) / 86400000 + 1) / 7)
    return `KW ${week} ${date.getUTCFullYear()}`
}

const startMonth = ref(initialMonth)
const endMonth = ref(initialMonth)
const processing = ref(false)

// Bewusst KEIN Inertia-Router: ein reiner Datei-Download über axios (Blob) vermeidet den
// 409-Conflict, mit dem Inertia::location() auf Inertia-Requests antwortet. Der Controller
// liefert für einen Nicht-Inertia-Request ein normales 302→Download.
const createPdf = async () => {
    if (processing.value) return

    // Reihenfolge absichern, falls Endmonat vor Startmonat liegt
    let start = startMonth.value
    let end = endMonth.value || startMonth.value
    if (end < start) {
        [start, end] = [end, start]
    }

    const weekly = exportKind.value.id === 'weekly'

    processing.value = true
    try {
        const response = weekly
            ? await axios.post(
                route('user.shiftplan.export.weekly-pdf', props.userToEditId),
                {
                    type: props.type,
                    model_id: props.userToEditId,
                    week: weekDate.value,
                    paperSize: selectedPaperSize.value.id,
                    paperOrientation: 'landscape'
                },
                { responseType: 'blob' }
            )
            : await axios.post(
                route('user.shiftplan.export.monthly-pdf', props.userToEditId),
                {
                    type: props.type,
                    model_id: props.userToEditId,
                    startMonth: start,
                    endMonth: end,
                    paperSize: selectedPaperSize.value.id,
                    paperOrientation: 'landscape'
                },
                { responseType: 'blob' }
            )

        // Dateiname z.B. "Einsatzplan Mai 26 Max Schmidt vom 08.06.pdf"
        const now = new Date()
        const dd = String(now.getDate()).padStart(2, '0')
        const mm = String(now.getMonth() + 1).padStart(2, '0')
        const monthPart = start === end ? monthLabel(start) : `${monthLabel(start)} - ${monthLabel(end)}`
        const namePart = userName ? ` ${userName}` : ''
        const filename = sanitizeFilename(weekly
            ? `Wochenplan ${isoWeekLabel(weekDate.value)}${namePart} vom ${dd}.${mm}`
            : `Einsatzplan ${monthPart}${namePart} vom ${dd}.${mm}`) + '.pdf'

        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
        const link = document.createElement('a')
        link.href = url
        link.download = filename
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        emit('close')
    } catch (error) {
        console.error('Export fehlgeschlagen:', error)
    } finally {
        processing.value = false
    }
}
</script>
