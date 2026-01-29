<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, toRef, nextTick } from 'vue'
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
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import RoomSearch from '@/Components/SearchBars/RoomSearch.vue'

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
    rooms: { type: [Array, Object], default: () => [] },
    day: String,
    shiftPlanModal: Boolean,
    multiAddMode: Boolean,
    roomsAndDatesForMultiEdit: [Array, Object],
    // Neues Prop: aktuelles Projekt aus dem Projekt-Schichttab
    project: { type: Object, required: false, default: null },
    // Optional direkt übergebene Datenquellen (Fallback zu usePage().props)
    shiftGroups: { type: [Array, Object], required: false, default: () => [] },
    globalQualifications: { type: [Array, Object], required: false, default: () => [] },
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
    // Priorität: Props (vom Aufrufer) > usePage().props
    const fromProp = Array.isArray(props.globalQualifications)
        ? props.globalQualifications
        : (props.globalQualifications ? Object.values(props.globalQualifications as any) : [])
    const all = (fromProp && fromProp.length > 0)
        ? fromProp
        : (page?.props?.globalQualifications ?? [])
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

// Reagiere auf spätes Laden/Änderungen
watch(() => props.globalQualifications, () => {
    globalQualifications.value = globalQualificationsComputed.value
}, { deep: true })

// Projekt vorbelegen: Priorität -> Schicht-Projekt (Edit) > übergebenes Projekt (Projekt-Tab) > Event-Projekt
const selectedProject = ref(
    props.shift?.project
        ? props.shift.project
        : (props.project ?? (props.event?.project ?? null))
);

function normalizeToArray(val: any): any[] {
    if (Array.isArray(val)) return val
    if (val && typeof val === 'object') return Object.values(val)
    return []
}

// Schichtgruppen: Props bevorzugen, sonst usePage().props
const resolveInitialShiftGroups = (): any[] => {
    const fromProp = normalizeToArray(props.shiftGroups)
    if (fromProp.length > 0) return fromProp
    return normalizeToArray(usePage().props.shiftGroups)
}
const shiftGroups = ref<any[]>(resolveInitialShiftGroups())

const selectedShiftGroup = ref<any | null>(
    props.shift?.shiftGroupId
        ? (shiftGroups as any).value?.find
            ? (shiftGroups as any).value.find((sg: any) => sg.id === props.shift?.shiftGroupId) ?? null
            : shiftGroups.find((sg: any) => sg.id === props.shift?.shiftGroupId) ?? null
        : null
)

// Aktualisieren, wenn Props nachgeladen werden
watch(() => props.shiftGroups, (v) => {
    // @ts-ignore
    shiftGroups.value = normalizeToArray(v)
    if (props.shift?.shiftGroupId && !selectedShiftGroup.value) {
        // @ts-ignore
        selectedShiftGroup.value = shiftGroups.value.find((sg: any) => sg.id === props.shift?.shiftGroupId) || null
    }
}, { deep: true })

// Fallback: wenn usePage().props sich füllt
watch(() => usePage().props.shiftGroups, (v: any) => {
    if (!props.shiftGroups || normalizeToArray(props.shiftGroups).length === 0) {
        // @ts-ignore
        shiftGroups.value = normalizeToArray(v)
        if (props.shift?.shiftGroupId && !selectedShiftGroup.value) {
            // @ts-ignore
            selectedShiftGroup.value = shiftGroups.value.find((sg: any) => sg.id === props.shift?.shiftGroupId) || null
        }
    }
}, { deep: true })

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
    project_id: props.shift && props.shift.project
        ? props.shift.project.id
        : (props.event && props.event.project
            ? props.event.project.id
            : (props.project ? props.project.id : null)),
    shift_group_id: props.shift && props.shift.shiftGroupId ? props.shift.shiftGroupId : null,
})

// Falls das Projekt-Prop später gesetzt wird (asynchron), synchronisiere Auswahl und Formular
watch(() => props.project, (p) => {
    if (!selectedProject.value && p) {
        selectedProject.value = p
        if (!shiftForm.project_id) {
            shiftForm.project_id = p.id ?? null
        }
    }
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

// ----------------------
// Raum-Auswahl (analog EventComponent)
// ----------------------
const selectedRoom = ref<any | null>(null)
const roomsList = computed<any[]>(() => Array.isArray(props.rooms) ? (props.rooms as any[]) : Object.values(props.rooms || {}))

const checkIfMultiEditEnabled = computed(() => {
    // if props.multiAddMode is true and props.roomsAndDatesForMultiEdit is an array with more than one item return true
    // if props.multiAddMode is false check if selectedRoom is not null
    if (props.multiAddMode) {
        return Array.isArray(props.roomsAndDatesForMultiEdit) && props.roomsAndDatesForMultiEdit.length > 1
    } else {
        return selectedRoom.value !== null
    }
})


function findRoomById(rawId: any) {
    if (rawId === null || typeof rawId === 'undefined') return null
    const rid = Number(rawId)
    if (isNaN(rid)) return null
    const list = roomsList.value || []
    // Räume können id oder roomId verwenden, Name kann name oder roomName sein
    return list.find((r: any) => Number(r?.id) === rid || Number(r?.roomId) === rid) || null
}

function initSelectedRoom() {
    if (selectedRoom.value) return

    // Edit-Flow: Shift-Raum hat Priorität (damit room-Prop nicht „dazwischenfunkt“)
    if (props.edit && props.shift) {
        const shiftRoomObj = (props.shift as any)?.room
        if (shiftRoomObj && typeof shiftRoomObj === 'object') {
            // Wenn der Shift bereits ein Room-Objekt liefert, direkt nutzen (auch wenn rooms-Liste noch leer ist)
            selectedRoom.value = shiftRoomObj
        } else {
            const roomIdFromShift =
                (props.shift as any)?.roomId ??
                (props.shift as any)?.room_id ??
                (props.shift as any)?.room?.id ??
                (props.shift as any)?.room?.roomId

            const found = findRoomById(roomIdFromShift)
            if (found) selectedRoom.value = found
        }
    }

    // Add-Flow / Fallback: room-Prop (z. B. aus Klick auf „Schicht hinzufügen“)
    if (!selectedRoom.value) {
        if (props.room && typeof props.room === 'object') {
            selectedRoom.value = props.room as any
        } else if (props.room !== null && typeof props.room !== 'undefined') {
            // Zahl/String → über rooms finden
            const found = findRoomById(props.room)
            if (found) selectedRoom.value = found
        }
    }

    // Letzter Fallback: wenn wir im Edit sind und nur IDs haben, nochmal versuchen
    if (!selectedRoom.value && props.shift) {
        const roomIdFromShift =
            (props.shift as any)?.roomId ??
            (props.shift as any)?.room_id ??
            (props.shift as any)?.room?.id ??
            (props.shift as any)?.room?.roomId
        const found = findRoomById(roomIdFromShift)
        if (found) selectedRoom.value = found
    }

    // Falls selektierter Raum gesetzt, room_id synchronisieren
    if (selectedRoom.value && !shiftForm.room_id) {
        shiftForm.room_id = selectedRoom.value.id ?? selectedRoom.value.roomId ?? null
    }
}

function onRoomSelected(room: any) {
    selectedRoom.value = room
}

// Sync: wenn selectedRoom wechselt, setze room_id im Formular
watch(selectedRoom, (r) => {
    shiftForm.room_id = r ? (r.id ?? r.roomId ?? null) : null
})

// Reagiere auf Änderungen an rooms/room-Prop (spätes Laden möglich)
watch(() => props.rooms, () => initSelectedRoom(), { deep: true })
watch(() => props.room, (newVal, oldVal) => {
    // Beim Editieren niemals den bereits ermittelten Shift-Raum „wegwerfen“,
    // nur weil das room-Prop (z. B. null/stale) wechselt.
    if (props.edit) {
        initSelectedRoom()
        return
    }

    // Add-Flow: room-Prop soll Auswahl steuern
    if (newVal !== oldVal) {
        selectedRoom.value = null
        initSelectedRoom()
    }
})

watch(() => props.shift, () => {
    // Wenn der Shift im Modal wechselt (Edit), neu initialisieren
    selectedRoom.value = null
    initSelectedRoom()
}, { deep: true })

onMounted(() => initSelectedRoom())

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
    if (shiftForm.start && shiftForm.end && !shiftForm.break_minutes) {
        shiftForm.break_minutes = v
    }

    // if v is greater than 0 and shiftForm.break_minutes is less than v, set shiftForm.break_minutes to v
    if (typeof v === 'number' && v > 0 && (typeof shiftForm.break_minutes !== 'number' || shiftForm.break_minutes < v)) {
        shiftForm.break_minutes = v
    }

}, { immediate: true, deep: true })

// Automatische Anpassung des Enddatums basierend auf Start-/Endzeit
watch([() => shiftForm.start, () => shiftForm.end], ([startTime, endTime]) => {
    if (!startTime || !endTime || !shiftForm.start_date) return

    // Wenn Endzeit >= Startzeit, dann soll das Enddatum dem Startdatum entsprechen
    // Wenn Endzeit < Startzeit, dann geht die Schicht über Mitternacht und Enddatum = Startdatum + 1 Tag
    if (endTime >= startTime) {
        shiftForm.end_date = shiftForm.start_date
    } else {
        // Endzeit < Startzeit bedeutet Schicht geht über Mitternacht
        const startDate = new Date(shiftForm.start_date)
        startDate.setDate(startDate.getDate() + 1)
        shiftForm.end_date = startDate.toISOString().slice(0, 10)
    }
})

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
async function takeShiftPreset(preset: any) {
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

    // Warten, bis der Watcher auf selectedCraft die computedShiftQualifications
    // neu aufgebaut hat, damit wir danach die Mengen korrekt setzen können.
    await nextTick()

    // Beschreibung
    shiftForm.description = preset.description ?? ''

    // Qualifikationen mappen (0 erlaubt)
    const map = new Map<number, number>()
    for (const q of normalizePresetQualifications(preset)) {
        const id = typeof q?.id === 'number' ? q.id : q?.shift_qualification_id
        if (id == null) continue
        const qty = (typeof (q as any)?.quantity === 'number') ? (q as any).quantity : 0
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

function closeModal(bool = false){
    // Modal sofort ausblenden, um UI-Latenz zu vermeiden
    open.value = false
    ;(props.shiftTimePresets || []).forEach((p) => { p.active = false })
    singleShiftPresets.value.forEach(p => { p.active = false })
    // Parent benachrichtigen (true = erfolgreich gespeichert, false = nur geschlossen)
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
    const shiftStartDateTimeMilliseconds = shiftStartDateTime.getTime();
    const shiftEndDateTimeMilliseconds = shiftEndDateTime.getTime();

    let hasErrors = false

    if (((shiftEndDateTimeMilliseconds - shiftStartDateTimeMilliseconds) / 60000) > 600) {
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
    const shiftStartDateTimeMilliseconds = shiftStartDateTime.getTime();
    const shiftEndDateTimeMilliseconds = shiftEndDateTime.getTime();
    let hasErrors = false

    if (((shiftEndDateTimeMilliseconds - shiftStartDateTimeMilliseconds) / 60000) > 360 && shiftForm.break_minutes < 30) {
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

    // Modal direkt schließen, damit es nicht sichtbar bleibt, während die Liste bereits aktualisiert ist
    // (z. B. wenn im Hintergrund die neue Schicht schon gerendert wurde)
    open.value = false

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

    if (selectedShiftGroup.value) {
        shiftForm.shift_group_id = selectedShiftGroup.value.id
    } else {
        shiftForm.shift_group_id = null
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
            preserveState: true,
            onSuccess: (page) => {
                shiftForm.reset()
                // Im Shift-Plan (Daily View) per WebSockets aktualisieren – kein Reload nötig
                if (!props.shiftPlanModal) {
                    router.reload({ only: ['loadedProjectInformation'], preserveScroll: true })
                } else if (page.props.shift) {
                    // Update the shift in the parent if it was provided
                    emit('closed', true, page.props.shift)
                }
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
                router.reload({ only: ['loadedProjectInformation'], preserveScroll: true })
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
    // Unterstützt beide möglichen Quellen und normalisiert auf { id, quantity }
    const raw = preset?.shifts_qualifications ?? preset?.shift_qualifications ?? []
    if (!Array.isArray(raw)) return []
    return raw.map((q) => {
        // Falls nur die ID übergeben wird (z. B. [1,2,3])
        if (typeof q === 'number') {
            return { id: q, quantity: 0 }
        }

        // Falls Objekt — ID und Menge robust ermitteln
        if (q && typeof q === 'object') {
            const id = typeof q.id === 'number' ? q.id : (q.shift_qualification_id ?? Number(q.id))
            const quantity =
                (q.pivot && typeof q.pivot.quantity === 'number' ? q.pivot.quantity : undefined) ??
                (typeof q.quantity === 'number' ? q.quantity : 0)
            return { id: Number(id), quantity: Math.max(0, Number(quantity)) }
        }

        // Fallback
        const num = Number(q)
        return { id: isNaN(num) ? 0 : num, quantity: 0 }
    })
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
                                <PropertyIcon name="IconX" class="cursor-pointer h-5 w-5 text-gray-500 hover:text-gray-700" @click="closeShiftSearchbar" />
                            </div>
                        </div>
                        <div v-else>
                            <button type="button" class="ui-button !text-xs" @click="showShiftSearchbar = true">
                                <PropertyIcon name="IconSearch" class="h-4 w-4" />
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
                                <PropertyIcon name="IconX" class="cursor-pointer h-5 w-5 text-gray-500 hover:text-gray-700" @click="closeTimeSearchbar" />
                            </div>
                        </div>
                        <div v-else>
                            <button type="button" class="ui-button !text-xs" @click="showTimeSearchbar = true">
                                <PropertyIcon name="IconSearch" class="h-4 w-4" />
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
                    <!-- Room -->

                        <div class="grid grid-cols-1 my-4 gap-2" v-if="!multiAddMode">
                            <RoomSearch v-if="!selectedRoom" :label="$t('Search for Rooms')" @room-selected="onRoomSelected" />
                            <div v-else
                                 class="flex items-center gap-1.5 rounded-md border border-zinc-200 bg-zinc-50 px-2.5 py-4">
                                <span class="truncate">{{ selectedRoom?.name ?? selectedRoom?.roomName }}</span>
                                <button class="ml-0.5 text-zinc-400 transition hover:text-rose-600" @click="selectedRoom = null" type="button">
                                    <PropertyIcon name="IconX" class="size-4" />
                                </button>
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
                        <!-- Craft -->
                        <div class="sm:col-span-2">
                            <SelectComponent
                                id="addShiftShiftGroupSelectComponent"
                                :label="$t('Shift Group')"
                                :default="$t('Please select...')"
                                v-model="selectedShiftGroup"
                                :options="shiftGroups"
                                selected-property-to-display="name"
                                :getter-for-options-to-display="(option) => option.name"
                            />
                            <div class="flex items-center justify-end">
                                <button type="button" @click="selectedShiftGroup = null" class="text-xs text-zinc-500 hover:text-blue-500 mt-0.5 duration-200 ease-in-out cursor-pointer">{{ $t('Remove Shift group') }}</button>
                            </div>
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
                        :disabled="shiftForm.processing || !shiftForm.start || !shiftForm.end || !selectedCraft || !checkIfMultiEditEnabled"
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
