<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, toRef } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useLegalBreak} from "@/Composeables/useLegalBreak";
// Artwork / UI
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseModalButton from '@/Artwork/Buttons/ArtworkBaseModalButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'
import SelectComponent from '@/Components/Inputs/SelectComponent.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'

// Icons (Tabler)
import { IconSearch, IconX } from '@tabler/icons-vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const { t: $t } = useI18n()



// Helper: normalize time strings to 'HH:MM' (trim seconds or parse from datetime)
function toHHMM(val: any): string | null {
    if (val === null || typeof val === 'undefined') return null
    let s = String(val).trim()
    if (!s) return null
    const tIndex = s.indexOf('T')
    if (tIndex !== -1) s = s.slice(tIndex + 1)
    const m = s.match(/(\d{1,2}):(\d{2})/)
    if (m) {
        const hh = m[1].padStart(2, '0')
        const mm = m[2]
        return `${hh}:${mm}`
    }
    return null
}

// Props
const props = defineProps({
    event: Object,
    crafts: Array,
    shift: Object,
    edit: Boolean,
    buffer: Object,
    currentUserCrafts: Array,
    shiftQualifications: Array,
    shiftTimePresets: Array,
    room: [String, Number, Object],
    day: String,
    shiftPlanModal: Boolean,
    multiAddMode: Boolean,
    roomsAndDatesForMultiEdit: [Array, Object],
})

// Emits
const emit = defineEmits(['closed'])

// Page
const page = usePage()

// ----- State -----
const open = ref(true)
const showComfirmDeleteModal = ref(false)

// Zeitvorgaben (Start/Ende/Pause)
const showTimePresetBox = ref(page.props?.auth?.user?.is_time_preset_open ?? false)
const showTimeSearchbar = ref(false)
const searchTimePreset = ref('')

// Schichtvorlagen (Komplett)
const showShiftPresetBox = ref(false)
const showGlobalQualificationBox = ref(false)
const showShiftSearchbar = ref(false)
const searchShiftPreset = ref('');

const globalQualificationsComputed = computed(() => {
    const all = page?.props?.globalQualifications ?? []
    const shiftQualis = props.shift?.globalQualifications ?? []

    return all.map(gq => {
        // Suche nach passender Quali aus dem Shift
        const match = shiftQualis.find(sq => sq.id === gq.id)
        return {
            ...gq,
            quantity: match?.pivot?.quantity ?? gq?.pivot?.quantity ?? null,
        }
    })
})

const globalQualifications = ref(globalQualificationsComputed.value)

const selectedProject = ref(props.shift?.project ? props.shift?.project : null);

const selectedCraft = ref(props.shift ? props.shift.craft : null)

const validationMessages = reactive({
    warnings: { shift_start: [], shift_end: [], break_length: [], craft: [] },
    errors: { shift_start: [], shift_end: [], break_length: [], craft: [] },
})

const shiftForm = useForm({
    id: props.shift ? props.shift.id : null,
    start_date: props.shift ? props.shift.formatted_dates.frontend_start : null,
    end_date: props.shift ? props.shift.formatted_dates.frontend_end : null,
    start: props.shift ? toHHMM(props.shift.start) : null,
    end: props.shift ? toHHMM(props.shift.end) : null,
    break_minutes: props.shift ? props.shift.break_minutes : null,
    craft_id: props.shift ? props.shift.craft?.id : null,
    description: props.shift ? props.shift.description : '',
    event_id: props.event ? props.event.id : null,
    changeAll: false,
    seriesId: null,
    changes_start: null,
    changes_end: null,
    shiftsQualifications: [],
    automaticMode: true,
    room_id: props.room ? props.room : null,
    day: props.day ? props.day : null,
    globalQualifications: [],
    roomsAndDatesForMultiEdit: props.roomsAndDatesForMultiEdit ? props.roomsAndDatesForMultiEdit : null,
    updateOrCreateInShiftPlan: props.shiftPlanModal,
    project_id: props.shift && props.shift.project ? props.shift.project.id : (props.event && props.event.project ? props.event.project.id : null),
})

const initialShiftSnapshot = ref<null | {
    start: string | null,
    end: string | null,
    break_minutes: number | null,
    craft: any | null,
    description: string,
    qualifications: Array<{ id: number, value: number | '' }>
}>(null)

const { breakMinutes } = useLegalBreak(
    toRef(shiftForm, 'start'),
    toRef(shiftForm, 'end'),
    {
        allowCrossMidnight: true,
        roundToMinutes: 1,
    }
)

// ----- computedShiftQualifications als ref (stabil für v-model) -----

const initComputedShiftQualifications = computed(() => {
    return getInitialQualificationValue()
})

function getInitialQualificationValue() {
    const list = (selectedCraft.value?.qualifications || []).map((shiftQualification) => {
        // auf Edit: vorhandenen Wert übernehmen
        const found = props.edit
            ? (props.shift?.shifts_qualifications || []).find(
                s => s.shift_qualification_id === shiftQualification.id
            )
            : undefined

        return {
            id: shiftQualification.id,
            name: shiftQualification.name,
            available: true,
            value: typeof found !== 'undefined' ? found.value : '',
            warning: null,
            error: null,
        }
    })

    return list
}

const computedShiftQualifications = ref(initComputedShiftQualifications.value ?? [])

// beim ersten Initialisieren sichern
function captureInitialShiftSnapshot() {
    if (initialShiftSnapshot.value) return
    initialShiftSnapshot.value = {
        start: shiftForm.start,
        end: shiftForm.end,
        break_minutes: shiftForm.break_minutes,
        craft: selectedCraft.value,
        description: shiftForm.description,
        qualifications: computedShiftQualifications.value.map(q => ({ id: q.id, value: q.value }))
    }
}

// call direkt nach dem Init der Qualifikationen
watch(
    () => computedShiftQualifications.value,
    () => captureInitialShiftSnapshot(),
    { immediate: true, deep: true }
)

// watch on selectecCraft to update computedShiftQualifications
watch(selectedCraft, () => {
    computedShiftQualifications.value = getInitialQualificationValue()
}, { immediate: true, deep: true })


watch(breakMinutes, (v) => {
    // nur überschreiben, wenn Start/Ende gefüllt sind
    if (shiftForm.start && shiftForm.end) {
        shiftForm.break_minutes = v
    }
}, { immediate: true, deep: true })

onMounted(() => {
    if (props.edit)
    {
        computedShiftQualifications.value = getInitialQualificationValue() // nur beim ersten Mount
        validate()
    }
})

// ----- Computeds -----
const selectableCrafts = computed(() => {
    const crafts = []
    if (selectedCraft.value) {
        let selectedCraftIncluded = false
        ;(props.currentUserCrafts || []).forEach((userCraft) => {
            if (userCraft.id === selectedCraft.value.id) selectedCraftIncluded = true
        })
        if (!selectedCraftIncluded) crafts.push(selectedCraft.value)
    }
    return crafts.concat(props.currentUserCrafts || [])
})

const filteredShiftTimePresets = computed(() => {
    const q = (searchTimePreset.value || '').toLowerCase()
    return (props.shiftTimePresets || []).filter((p) =>
        (p.name || '').toLowerCase().includes(q)
    )
})

// Schichtvorlagen aus Page Props
const singleShiftPresets = computed(() => {
    const arr = page.props?.singleShiftPresets ?? []
    return Array.isArray(arr) ? arr : []
})
const filteredSingleShiftPresets = computed(() => {
    const q = (searchShiftPreset.value || '').toLowerCase().trim()
    if (!q) return singleShiftPresets.value
    return singleShiftPresets.value.filter((p) => {
        const name = (p?.name ?? '').toLowerCase()
        const desc = (p?.description ?? '').toLowerCase()
        const craft = (props.crafts?.find(c => c.id === p?.craft_id)?.name ?? '').toLowerCase()
        return name.includes(q) || desc.includes(q) || craft.includes(q)
    })
})

// Aktiv-Status für Reset-Buttons
const hasActiveShiftPreset = computed(() =>
    singleShiftPresets.value?.some(p => p?.active) || false
)
const hasActiveTimePreset = computed(() =>
    (props.shiftTimePresets || []).some(p => p?.active)
)


// ----- Methods (funktional + UI) -----
function deleteShift() {
    router.delete(route('shifts.destroy', { shift: props.shift.id}), {
        onSuccess: () => closeModal(true),
        onFinish: () => closeModal(true),
    })
}

// kompakte Stats für Schichtvorlagen (Total-Menge & Anzahl Qualis)
function qualStats(preset: any) {
    const arr = normalizePresetQualifications(preset)
    const total = arr.reduce((s: number, q: any) => s + (q?.pivot?.quantity ?? q?.quantity ?? 0), 0)
    return { total, count: arr.length }
}

// Übernahme einer Schichtvorlage in die Felder (inkl. 0-Werte)
function takeShiftPreset(preset: any) {
    if (!preset) return

    // Zeiten + Pause (trim seconds to HH:MM)
    const st = toHHMM(preset.start_time)
    const et = toHHMM(preset.end_time)
    shiftForm.start = st ?? shiftForm.start
    shiftForm.end = et ?? shiftForm.end
    shiftForm.break_minutes = typeof preset.break_duration === 'number'
        ? preset.break_duration
        : (shiftForm.break_minutes ?? 0)

    // Gewerk
    const targetCraft = props.crafts?.find(c => c.id === preset.craft_id) ?? null
    if (targetCraft) selectedCraft.value = targetCraft

    // Beschreibung
    shiftForm.description = preset.description ?? ''

    // Qualifikationen mappen (0 erlaubt)
    const map = new Map<number, number>()
    for (const q of normalizePresetQualifications(preset)) {
        const id = typeof q?.id === 'number' ? q.id : q?.shift_qualification_id
        if (id == null) continue
        const qty = (typeof q?.pivot?.quantity === 'number') ? q.pivot.quantity
            : (typeof q?.quantity === 'number') ? q.quantity
                : 0
        map.set(Number(id), Math.max(0, qty))
    }
    computedShiftQualifications.value.forEach(q => {
        const newVal = map.get(q.id)
        q.value = typeof newVal === 'number' ? newVal : 0
        q.warning = null
        q.error = null
    })

    // aktiv markieren
    singleShiftPresets.value.forEach((p: any) => { p.active = (p.id === preset.id) })
}

// „Auswahl zurücksetzen“: optional auch Felder auf Snapshot zurücksetzen
function resetShiftPresetSelection({ alsoFields = false }: { alsoFields?: boolean } = {}) {
    singleShiftPresets.value.forEach((p: any) => { p.active = false })
    if (alsoFields && initialShiftSnapshot.value) {
        const s = initialShiftSnapshot.value
        shiftForm.start = s.start
        shiftForm.end = s.end
        shiftForm.break_minutes = s.break_minutes
        selectedCraft.value = s.craft
        shiftForm.description = s.description
        // Qualis zurücksetzen
        const map = new Map(s.qualifications.map(q => [q.id, q.value]))
        computedShiftQualifications.value.forEach(q => {
            q.value = map.get(q.id) ?? ''
            q.warning = null
            q.error = null
        })
    }
}


function takeTimePreset(preset) {
    shiftForm.start = toHHMM(preset.start_time)
    shiftForm.end = toHHMM(preset.end_time)
    shiftForm.break_minutes = preset.break_time
    ;(props.shiftTimePresets || []).forEach((p) => { p.active = p.id === preset.id })
}

function closeModal(bool){
    ;(props.shiftTimePresets || []).forEach((p) => { p.active = false })
    singleShiftPresets.value.forEach(p => { p.active = false })
    emit('closed', bool)
}

function appendComputedShiftQualificationsToShiftForm() {
    computedShiftQualifications.value.forEach((q) => {
        if (canComputedShiftQualificationBeShown(q)) {
            shiftForm.shiftsQualifications.push({
                shift_qualification_id: q.id,
                value: q.value
            })
        }
    })
}

function onQualificationChange(qualification) {
    if (qualification.value < 0) qualification.value = 0
    validateShiftsQualification(qualification)
}

function canComputedShiftQualificationBeShown(computedShiftQualification) {
    return computedShiftQualification.available ||
        (props.edit && shiftContainsComputedShiftQualification(computedShiftQualification))
}

function shiftContainsComputedShiftQualification(computedShiftQualification) {
    return typeof (props.shift?.shifts_qualifications || []).find(
        (sq) => sq.shift_qualification_id === computedShiftQualification.id
    ) !== 'undefined'
}

function validateShiftDates() {
    validationMessages.warnings.shift_start = []
    validationMessages.warnings.shift_end = []
    validationMessages.errors.shift_start = []
    validationMessages.errors.shift_end = []

    const shiftStartDateTime = new Date(`${shiftForm.start_date}T${shiftForm.start}`)
    const shiftEndDateTime = new Date(`${shiftForm.end_date}T${shiftForm.end}`)
    let hasErrors = false

    if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 600) {
        validationMessages.warnings.shift_start.push($t('The shift is over 10 hours long!'))
    }

    if (!props.shiftPlanModal) {
        const eventStartDateTime = new Date(props.event?.start_time)
        const eventEndDateTime = new Date(props.event?.end_time)
        if (shiftStartDateTime < eventStartDateTime) {
            validationMessages.warnings.shift_start.push($t('The shift starts before the event starts!'))
        }
        if (shiftStartDateTime > eventEndDateTime) {
            validationMessages.warnings.shift_start.push($t('The shift starts after the event ends!'))
        }
    }

    if (!shiftForm.automaticMode) {
        if (!shiftForm.start || !shiftForm.start_date) {
            validationMessages.errors.shift_start.push($t('Please enter a start time and date.'))
            hasErrors = true
        }
        if (!shiftForm.end || !shiftForm.end_date) {
            validationMessages.errors.shift_end.push($t('Please enter an end time and date.'))
            hasErrors = true
        }
    } else {
        validationMessages.errors.shift_start = []
        validationMessages.errors.shift_end = []
    }

    return hasErrors
}

function validateShiftBreak() {
    validationMessages.warnings.break_length = []
    validationMessages.errors.break_length = []

    const shiftStartDateTime = new Date(`${shiftForm.start_date}T${shiftForm.start}`)
    const shiftEndDateTime = new Date(`${shiftForm.end_date}T${shiftForm.end}`)
    let hasErrors = false

    if (((shiftEndDateTime - shiftStartDateTime) / 60000) > 360 && shiftForm.break_minutes < 30) {
        validationMessages.warnings.break_length.push($t('The break is shorter than required by law!'))
    }

    if (shiftForm.break_minutes === null || shiftForm.break_minutes === '') {
        validationMessages.errors.break_length.push($t('Please enter a break time.'))
        hasErrors = true
    }

    return hasErrors
}

function validateShiftCraft() {
    validationMessages.warnings.craft = []
    validationMessages.errors.craft = []
    if (selectedCraft.value === null) {
        validationMessages.errors.craft.push($t('Please select a trade.'))
        return true
    }
    return false
}

function validateShiftsQualification(computedShiftsQualification) {
    computedShiftsQualification.warning = null
    computedShiftsQualification.error = null
    if (computedShiftsQualification.value < 0) {
        computedShiftsQualification.error = $t("Value can't be lower than 0.")
        return true
    }
    return false
}

function validateShiftsQualifications() {
    let hasErrors = false
    computedShiftQualifications.value.forEach((q) => {
        if (validateShiftsQualification(q)) hasErrors = true
    })
    return hasErrors
}

function validate() {
    const hasShiftDateError = validateShiftDates()
    const hasShiftBreakError = validateShiftBreak()
    const hasShiftCraftError = validateShiftCraft()
    const hasShiftsQualificationsError = validateShiftsQualifications()
    return hasShiftDateError || hasShiftBreakError || hasShiftCraftError || hasShiftsQualificationsError
}

function saveShift() {
    if (validate()) return

    if (!props.shiftPlanModal && props.event?.is_series) {
        if (!props.buffer?.onlyThisDay) {
            shiftForm.changeAll = true
            shiftForm.seriesId = props.event.series_id
            shiftForm.changes_start = props.buffer?.start
            shiftForm.changes_end = props.buffer?.end
        }
    }

    // if Break null or '' set it to 0
    if (shiftForm.break_minutes === null || shiftForm.break_minutes === '') {
        shiftForm.break_minutes = 0
    }

    // if selected Project add id to shiftForm
    if (selectedProject.value) {
        shiftForm.project_id = selectedProject.value.id
    } else {
        shiftForm.project_id = null
    }

    shiftForm.craft_id = selectedCraft.value?.id
    shiftForm.shiftsQualifications = []
    shiftForm.globalQualifications = []

    globalQualifications.value.forEach( (qualification) => {
        if(qualification.quantity > 0) {
            shiftForm.globalQualifications.push({
                global_qualification_id: qualification.id,
                quantity: qualification.quantity,
            });
        }
    })

    appendComputedShiftQualificationsToShiftForm()

    if (props.multiAddMode) {
        shiftForm.post(route('event.shift.store.multi.add'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { shiftForm.reset(); closeModal(true) },
            onError: (e) => console.log(e),
            onFinish: () => { shiftForm.reset(); closeModal(true) },
        })
        return
    }

    if (props.shiftPlanModal && !shiftForm.id) {
        shiftForm.post(route('event.shift.store.without.event'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { shiftForm.reset(); closeModal(true) },
            onError: (e) => console.log(e),
            onFinish: () => { shiftForm.reset(); closeModal(true) },
        })
        return
    }

    if (shiftForm.id) {
        shiftForm.patch(route('event.shift.update', props.shift.id), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                shiftForm.reset()
                router.reload({ only: ['loadedProjectInformation'] })
                closeModal(true)
            },
            onError: (e) => console.log(e),
            onFinish: () => { shiftForm.reset(); closeModal(true) },
        })
    } else {
        shiftForm.post(route('event.shift.store', props.event.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                shiftForm.reset()
                router.reload({ only: ['loadedProjectInformation'] })
                closeModal(true)
            },
            onError: (e) => console.log(e),
            onFinish: () => { shiftForm.reset(); closeModal(true) },
        })
    }
}

// ---- Hilfen & UI-Actions ----
function craftAbbrById(id) {
    return props.currentUserCrafts?.find(c => c.id === id)?.abbreviation
        ?? props.crafts?.find(c => c.id === id)?.abbreviation
        ?? ''
}
function closeTimeSearchbar() {
    showTimeSearchbar.value = false
    searchTimePreset.value = ''
}
function closeShiftSearchbar() {
    showShiftSearchbar.value = false
    searchShiftPreset.value = ''
}
function normalizePresetQualifications(preset) {
    const raw = preset?.shifts_qualifications ?? preset?.shift_qualifications ?? []
    return Array.isArray(raw) ? raw : []
}

function resetTimePresetSelection() {
    (props.shiftTimePresets || []).forEach(p => { p.active = false })
}
function toggleTimePresetBox() {
    router.patch(
        route('user.shift-time-preset.toggle'),
        { is_time_preset_open: !showTimePresetBox.value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => { showTimePresetBox.value = !showTimePresetBox.value },
            onError: (e) => console.log(e),
        }
    )
}

function toggleGlobalQualificationBox() {
    showGlobalQualificationBox.value = !showGlobalQualificationBox.value
}

const lockOrUnlockShift = (commit = false) => {
    router.post(route('shift.change.commit.status', props.shift.id), {
        commit: commit
    }, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <ArtworkBaseModal
        v-if="open"
        full-modal
        title="Organize shift"
        description="Determine how long your shift lasts and how many people should work in your shift."
        @close="closeModal"
    >
        <form @submit.prevent="saveShift" class="relative z-40 artwork">
            <div class="space-y-6">
                <!-- REPLACE: Sektion Schichtvorlagen -->
                <section class="rounded-2xl ring-1 ring-gray-200/70 bg-white/70 p-0 shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $t('shift templates') }}</h3>
                        <div class="flex items-center gap-2">
                          <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700">
                            {{ filteredSingleShiftPresets.length }}
                          </span>
                            <button type="button" class="ui-button !text-xs" @click="showShiftPresetBox = !showShiftPresetBox">
                                {{ showShiftPresetBox ? $t('Hide') : $t('Show') }}
                            </button>
                            <button
                                v-if="hasActiveShiftPreset"
                                type="button"
                                class="ui-button !text-xs"
                                @click="resetShiftPresetSelection({ alsoFields: true })"
                            >
                                {{ $t('Reset') }}
                            </button>
                        </div>
                    </div>

                    <!-- Suche (nur wenn offen) -->
                    <div v-if="showShiftPresetBox" class="px-4 sm:px-5 pb-2">
                        <div class="w-full sm:w-80" v-if="showShiftSearchbar">
                            <div class="flex items-center gap-2">
                                <BaseInput
                                    is-small
                                    id="shift-preset-search"
                                    v-model="searchShiftPreset"
                                    :label="$t('Browse shift templates')"
                                />
                                <IconX class="cursor-pointer h-5 w-5 text-gray-500 hover:text-gray-700" @click="closeShiftSearchbar" />
                            </div>
                        </div>
                        <div v-else>
                            <button type="button" class="ui-button !text-xs" @click="showShiftSearchbar = true">
                                <IconSearch class="h-4 w-4" />
                                <span>{{ $t('Search') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Inhalt (scrollbar) -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div v-if="showShiftPresetBox" class="px-4 sm:px-5 pb-4">
                            <div v-if="filteredSingleShiftPresets?.length > 0" class="max-h-[300px] overflow-y-auto p-3">
                                <!-- kompakteres, luftiges Grid (verhindert Überlauf) -->
                                <div class="grid gap-3"
                                     style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
                                    <button
                                        v-for="preset in filteredSingleShiftPresets"
                                        :key="'shift-preset-'+preset.id"
                                        type="button"
                                        @click="takeShiftPreset(preset)"
                                        class="text-left"
                                    >
                                        <div
                                            class="rounded-xl bg-white ring-1 ring-gray-200 p-3 hover:shadow-md transition-all duration-200"
                                            :class="[preset.active ? 'ring-2 !ring-blue-500 shadow-md' : '']"
                                        >
                                            <!-- Kopf: Name + Total -->
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0 truncate text-[13px] font-semibold text-gray-900">
                                                    {{ preset.name }}
                                                </div>
                                                <span
                                                    v-if="(preset.shifts_qualifications?.length || preset.shift_qualifications?.length)"
                                                    class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-700"
                                                >
                                                  {{ qualStats(preset).total }}
                                                </span>
                                            </div>

                                            <!-- Zeiten + Pause (Badges) -->
                                            <div class="mt-1 flex items-center gap-1.5 text-[12px] text-gray-700">
                                                <span class="shrink-0 rounded border border-gray-200 px-1.5 py-0.5">{{ toHHMM(preset.start_time) }}</span>
                                                <span class="shrink-0 text-gray-400">→</span>
                                                <span class="shrink-0 rounded border border-gray-200 px-1.5 py-0.5">{{ toHHMM(preset.end_time) }}</span>
                                                <span class="ml-auto shrink-0 rounded bg-gray-50 px-1.5 py-0.5">
                                                  {{ preset.break_duration ?? 0 }} {{ $t('min') }}
                                                </span>
                                            </div>

                                            <!-- Gewerk + Anzahl Qualis -->
                                            <div class="mt-1 flex items-center justify-between gap-2">
                                                <span class="shrink-0 rounded border border-gray-200 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700">
                                                  {{ craftAbbrById(preset.craft_id) || '—' }}
                                                </span>
                                                                                <span class="truncate text-[11px] text-gray-500">
                                                  {{ qualStats(preset).count }} {{ $t('Qualis') }}
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-else class="py-6">
                                <AlertComponent
                                    type="info"
                                    show-icon
                                    icon-size="w-4 h-4"
                                    :text="$t('No shift templates found.')"
                                    class="mx-auto w-fit"
                                />
                            </div>
                        </div>
                    </transition>
                </section>

                <!-- Sektion: Zeitvorgaben -->
                <section class="rounded-2xl ring-1 ring-gray-200/70 bg-white/70 p-0 shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $t('Time presets') }}</h3>
                        <div class="flex items-center gap-2">
                              <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700">
                                {{ filteredShiftTimePresets.length }}
                              </span>
                            <button type="button" class="ui-button !text-xs" @click="toggleTimePresetBox()">
                                {{ showTimePresetBox ? $t('Hide') : $t('Show') }}
                            </button>
                            <button
                                v-if="hasActiveTimePreset"
                                type="button"
                                class="ui-button !text-xs"
                                @click="resetTimePresetSelection"
                            >
                                {{ $t('Reset') }}
                            </button>
                        </div>
                    </div>

                    <!-- Suche (nur wenn offen) -->
                    <div v-if="showTimePresetBox" class="px-4 sm:px-5 pb-2">
                        <div class="w-full sm:w-80" v-if="showTimeSearchbar">
                            <div class="flex items-center gap-2">
                                <BaseInput
                                    is-small
                                    id="time-preset-search"
                                    v-model="searchTimePreset"
                                    :label="$t('Search time specifications')"
                                />
                                <IconX class="cursor-pointer h-5 w-5 text-gray-500 hover:text-gray-700" @click="closeTimeSearchbar" />
                            </div>
                        </div>
                        <div v-else>
                            <button type="button" class="ui-button !text-xs" @click="showTimeSearchbar = true">
                                <IconSearch class="h-4 w-4" />
                                <span>{{ $t('Search') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Inhalt (scrollbar) -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div v-if="showTimePresetBox" class="px-4 sm:px-5 pb-4">
                            <div v-if="filteredShiftTimePresets?.length > 0" class="max-h-[240px] md:max-h-[280px] overflow-y-auto pr-1">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2.5">
                                    <div
                                        v-for="preset in filteredShiftTimePresets"
                                        :key="'time-preset-'+preset.id"
                                        @click="takeTimePreset(preset)"
                                        class="cursor-pointer group"
                                    >
                                        <div
                                            class="rounded-lg bg-white ring-1 ring-gray-200 p-2.5 hover:shadow-sm transition-all duration-150 group-hover:-translate-y-0.5"
                                            :class="[preset.active ? 'ring-2 ring-emerald-500 shadow-sm' : '']"
                                        >
                                            <div class="text-[12px] font-semibold truncate text-gray-900">
                                                {{ preset.name }}
                                            </div>
                                            <div class="mt-1 text-[11px] text-gray-600 flex items-center gap-1.5">
                                                <span class="rounded border border-gray-200 px-1.5 py-0.5">{{ toHHMM(preset.start_time) }}</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="rounded border border-gray-200 px-1.5 py-0.5">{{ toHHMM(preset.end_time) }}</span>
                                                <span class="ml-auto rounded bg-gray-50 px-1.5 py-0.5">
                                                  {{ preset.break_time }} {{ $t('min') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-6">
                                <AlertComponent
                                    type="info"
                                    show-icon
                                    icon-size="w-4 h-4"
                                    :text="$t('No presets found.')"
                                    class="mx-auto w-fit"
                                />
                            </div>
                        </div>
                    </transition>
                </section>

                <!-- Sektion: Zeitvorgaben -->
                <section class="rounded-2xl ring-1 ring-gray-200/70 bg-white/70 p-0 shadow-sm overflow-hidden">
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 p-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $t('Global qualifications') }}</h3>
                        <div class="flex items-center gap-2">
                              <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-700">
                                {{ globalQualifications?.length }}
                              </span>
                            <button type="button" class="ui-button !text-xs" @click="toggleGlobalQualificationBox()">
                                {{ showGlobalQualificationBox ? $t('Hide') : $t('Show') }}
                            </button>
                        </div>
                    </div>

                    <!-- Inhalt (scrollbar) -->
                    <transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div v-if="showGlobalQualificationBox" class="px-4 sm:px-5 pb-4">
                            <div v-if="globalQualifications?.length > 0" class="max-h-[240px] md:max-h-[280px] overflow-y-auto pr-1 py-1">
                                <div class="space-y-3 divide-y divide-zinc-200 divide-dashed">
                                    <div
                                        v-for="globalQualification in globalQualifications"
                                        :key="'globalQualification-' + globalQualification.id"
                                        class="group pb-3"
                                    >
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 gap-2.5">
                                            <div class="flex items-center gap-x-2">
                                                <PropertyIcon :name="globalQualification.icon" class="w-4 h-4" />
                                                <div class="antialiased text-sm">
                                                    {{ globalQualification.name }}
                                                </div>
                                            </div>
                                            <div>
                                                <BaseInput
                                                    v-model="globalQualification.quantity"
                                                    type="number"
                                                    :id="'globalQualificationValue-' + globalQualification.id"
                                                    :label="$t('Quantity')"
                                                    is-small
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-6">
                                <AlertComponent
                                    type="info"
                                    show-icon
                                    icon-size="w-4 h-4"
                                    :text="$t('No presets found.')"
                                    class="mx-auto w-fit"
                                />
                            </div>
                        </div>
                    </transition>
                </section>

                <!-- Sektion: Basisdaten -->
                <section class="rounded-2xl ring-1 ring-gray-200/70 bg-white/70 p-4 sm:p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $t('basic data') }}</h3>
                            <p class="text-xs text-gray-500">
                                {{ $t('Fine-tune times, breaks, trade, qualifications, and description.') }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Start -->
                        <div class="flex gap-3">
                            <BaseInput
                                v-if="!shiftForm.automaticMode"
                                type="date"
                                v-model="shiftForm.start_date"
                                :label="$t('Shift start date')"
                                id="start_date"
                                :required="!shiftForm.automaticMode"
                                @change="validateShiftDates()"
                            />
                            <BaseInput
                                v-model="shiftForm.start"
                                :label="$t('Start time')"
                                type="time"
                                id="start"
                                required
                                :class="[!shiftForm.automaticMode ? 'max-w-[10rem]' : 'w-full']"
                                @change="validateShiftDates()"
                            />
                        </div>

                        <!-- End -->
                        <div class="flex gap-3">
                            <BaseInput
                                v-if="!shiftForm.automaticMode"
                                type="date"
                                v-model="shiftForm.end_date"
                                :label="$t('Shift end date')"
                                id="end_date"
                                :required="!shiftForm.automaticMode"
                                @change="validateShiftDates()"
                            />
                            <BaseInput
                                v-model="shiftForm.end"
                                :label="$t('End time')"
                                type="time"
                                id="end"
                                required
                                :class="[!shiftForm.automaticMode ? 'max-w-[10rem]' : 'w-full']"
                                @change="validateShiftDates()"
                            />
                        </div>

                        <!-- Warnings/Errors: Start & End -->
                        <transition
                            enter-active-class="transition duration-200"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="validationMessages.warnings.shift_start.length || validationMessages.errors.shift_start.length || validationMessages.warnings.shift_end.length || validationMessages.errors.shift_end.length"
                                class="sm:col-span-2 space-y-2"
                                aria-live="polite"
                            >
                                <div v-if="validationMessages.warnings.shift_start.length" class="rounded-md bg-amber-50 ring-1 ring-amber-200 px-3 py-2">
                                    <ul class="text-xs text-amber-800 list-disc list-inside">
                                        <li v-for="(w, i) in validationMessages.warnings.shift_start" :key="'ws-'+i">{{ w }}</li>
                                    </ul>
                                </div>
                                <div v-if="validationMessages.errors.shift_start.length" class="rounded-md bg-rose-50 ring-1 ring-rose-200 px-3 py-2">
                                    <ul class="text-xs text-rose-800 list-disc list-inside">
                                        <li v-for="(e, i) in validationMessages.errors.shift_start" :key="'es-'+i">{{ e }}</li>
                                    </ul>
                                </div>

                                <div v-if="validationMessages.warnings.shift_end.length" class="rounded-md bg-amber-50 ring-1 ring-amber-200 px-3 py-2">
                                    <ul class="text-xs text-amber-800 list-disc list-inside">
                                        <li v-for="(w, i) in validationMessages.warnings.shift_end" :key="'we-'+i">{{ w }}</li>
                                    </ul>
                                </div>
                                <div v-if="validationMessages.errors.shift_end.length" class="rounded-md bg-rose-50 ring-1 ring-rose-200 px-3 py-2">
                                    <ul class="text-xs text-rose-800 list-disc list-inside">
                                        <li v-for="(e, i) in validationMessages.errors.shift_end" :key="'ee-'+i">{{ e }}</li>
                                    </ul>
                                </div>
                            </div>
                        </transition>

                        <!-- Break length -->
                        <div class="sm:col-span-2">
                            <BaseInput
                                type="number"
                                id="shift-break-minutes-input"
                                :label="$t('Length of break in minutes*')"
                                v-model="shiftForm.break_minutes"
                                :min="0"
                                :max="1000"
                                required
                                @change="validateShiftBreak()"
                            />
                        </div>

                        <!-- Craft -->
                        <div class="sm:col-span-2">
                            <SelectComponent
                                id="addShiftCraftSelectComponent"
                                :label="$t('Craft') + '*'"
                                v-model="selectedCraft"
                                :options="selectableCrafts"
                                selected-property-to-display="name"
                                :getter-for-options-to-display="(option) => option.name + ' ' + option.abbreviation"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <div v-if="!selectedProject">

                                <ProjectSearch id="2" label="Search project"  @project-selected="selectedProject = $event" />

                                <LastedProjects
                                    :limit="10"
                                    @select="selectedProject = $event"/>
                            </div>

                            <div v-else>
                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg bg-gray-50 border border-gray-200">
                                    <div class="min-w-0 flex items-center gap-3">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-semibold">
                                            {{ selectedProject.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">
                                                {{ selectedProject.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="ui-button !text-xs" @click="selectedProject = null">
                                        {{ $t('Change') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Break/Craft Hinweise -->
                        <transition
                            enter-active-class="transition duration-200"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div v-if="validationMessages.warnings.break_length.length || validationMessages.errors.break_length.length || validationMessages.warnings.craft.length || validationMessages.errors.craft.length" class="sm:col-span-2 space-y-2" aria-live="polite">
                                <div v-if="validationMessages.warnings.break_length.length" class="rounded-md bg-amber-50 ring-1 ring-amber-200 px-3 py-2">
                                    <ul class="text-xs text-amber-800 list-disc list-inside">
                                        <li v-for="(w, i) in validationMessages.warnings.break_length" :key="'wb-'+i">{{ w }}</li>
                                    </ul>
                                </div>
                                <div v-if="validationMessages.errors.break_length.length" class="rounded-md bg-rose-50 ring-1 ring-rose-200 px-3 py-2">
                                    <ul class="text-xs text-rose-800 list-disc list-inside">
                                        <li v-for="(e, i) in validationMessages.errors.break_length" :key="'eb-'+i">{{ e }}</li>
                                    </ul>
                                </div>

                                <div v-if="validationMessages.warnings.craft.length" class="rounded-md bg-amber-50 ring-1 ring-amber-200 px-3 py-2">
                                    <ul class="text-xs text-amber-800 list-disc list-inside">
                                        <li v-for="(w, i) in validationMessages.warnings.craft" :key="'wc-'+i">{{ w }}</li>
                                    </ul>
                                </div>
                                <div v-if="validationMessages.errors.craft.length" class="rounded-md bg-rose-50 ring-1 ring-rose-200 px-3 py-2">
                                    <ul class="text-xs text-rose-800 list-disc list-inside">
                                        <li v-for="(e, i) in validationMessages.errors.craft" :key="'ec-'+i">{{ e }}</li>
                                    </ul>
                                </div>
                            </div>
                        </transition>

                        <!-- Qualifications -->
                        <div class="sm:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <transition-group
                                name="list"
                                enter-active-class="transition duration-200"
                                enter-from-class="opacity-0 translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 translate-y-1"
                            >
                                <div
                                    v-for="(computedShiftQualification, index) in computedShiftQualifications"
                                    :key="'sq-card-'+computedShiftQualification.id"
                                    v-show="canComputedShiftQualificationBeShown(computedShiftQualification)"
                                >
                                    <BaseInput
                                        v-if="canComputedShiftQualificationBeShown(computedShiftQualification)"
                                        type="number"
                                        v-model.number="computedShiftQualification.value"
                                        :id="'shift-qualification-' + index"
                                        :name="'shift-qualification-' + index"
                                        :min="0"
                                        :max="1000"
                                        :label="$t('Amount {0}', [computedShiftQualification.name])"
                                        @change="onQualificationChange(computedShiftQualification)"
                                        without-translation
                                    />
                                    <div v-if="computedShiftQualification.warning || computedShiftQualification.error" class="mt-2 space-y-2">
                                        <div v-if="computedShiftQualification.warning" class="rounded-md bg-amber-50 ring-1 ring-amber-200 px-3 py-1.5 text-xs text-amber-800">
                                            {{ computedShiftQualification.warning }}
                                        </div>
                                        <div v-if="computedShiftQualification.error" class="rounded-md bg-rose-50 ring-1 ring-rose-200 px-3 py-1.5 text-xs text-rose-800">
                                            {{ computedShiftQualification.error }}
                                        </div>
                                    </div>
                                </div>
                            </transition-group>
                        </div>

                        <!-- Description -->
                        <div class="sm:col-span-2">
                            <BaseTextarea
                                v-model="shiftForm.description"
                                :label="$t('Is there any important information about this shift?')"
                                rows="4"
                                name="comment"
                                id="comment"
                                maxlength="250"
                            />
                            <div class="text-xs text-end mt-1 text-artwork-buttons-context">
                                {{ shiftForm.description?.length ?? 0 }} / 250
                            </div>
                        </div>
                    </div>
                </section>

                <div v-if="shift?.is_committed" class="flex items-start justify-between gap-3 rounded-2xl ring-1 ring-gray-200/70 bg-white/70 p-4 sm:p-5 shadow-sm">
                    <AlertComponent
                        type="error"
                        show-icon
                        icon-size="w-4 h-4"
                        :text="$t('This shift is already committed.')"
                        class="mb-6"
                    />

                    <div class="">
                        <button class="ui-button !w-fit" @click="lockOrUnlockShift(false)" type="button">
                            {{ $t('Canceling a fixed term') }}
                        </button>
                    </div>
                </div>

            </div>

            <!-- Sticky Footer -->
            <div class="sticky bottom-0 left-0 right-0 z-50 mt-5">
                <div class="py-3 bg-white/90 backdrop-blur flex items-center" :class="!props.shift?.roomId ? 'justify-center' : 'justify-between'">

                    <BaseUIButton
                        :label="$t('Save')"
                        type="submit"
                        is-add-button
                        :disabled="shiftForm.processing || !shiftForm.start || !shiftForm.end || !selectedCraft"
                    />

                    <BaseUIButton
                        v-if="props.shift?.roomId"
                        type="button"
                        @click="showComfirmDeleteModal = true"
                        is-delete-button
                        :label="$t('Delete shift without Event')"
                    />
                </div>
            </div>
        </form>

        <!-- Delete confirm -->
        <ConfirmDeleteModal
            v-if="showComfirmDeleteModal"
            @closed="showComfirmDeleteModal = false"
            @delete="deleteShift"
            :description="$t('Do you really want to delete this shift?')"
            :title="$t('Delete shift')"
        />
    </ArtworkBaseModal>
</template>
