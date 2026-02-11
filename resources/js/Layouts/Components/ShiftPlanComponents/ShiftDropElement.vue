<template>
    <div class="w-full group/shift duration-300 ease-in-out cursor-pointer">
        <div
            :class="[
              'px-1',
              (highlightMode && hasAnyHighlightSelection && !matchesAnyHighlight) ? 'opacity-30' : '',
              (highlightMode && matchesAnyHighlight) ? 'bg-pink-500 ring-2 ring-pink-500 ring-offset-1 ring-offset-white !text-white' : '',
              (highlightMode && isThisShiftHighlighted) ? 'ring-2 ring-pink-500 ring-offset-1 ring-offset-white' : '',

              multiEditMode ? 'text-[10px]' : 'text-[11px]'
            ]"
            class="flex items-center xsLight text-shiftText subpixel-antialiased"
            @dragover="onDragOver"
            @drop="onDrop"
            @click="handleClickEvent"
        >
            <div v-if="multiEditMode && userForMultiEdit && checkIfUserIsInCraft">
                <input :checked="userForMultiEdit.shift_ids.includes(shift.id)"
                       @change="(e: any) => handleShiftAndEventForMultiEdit(e.target.checked, shift, event)"
                       type="checkbox"
                       :value="shift.id"
                       class="input-checklist mr-1"/>
            </div>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-start justify-between gap-x-1.5 w-full">
                    <div>

                        <div v-if="shift.shiftGroup && usePage().props.auth.user.calendar_settings.show_shift_group_tag" class="text-[8px]">({{ shift.shiftGroup.name }})</div>
                        <div class="text-[11px] flex items-center gap-x-1.5 w-full">
                            <PropertyIcon name="IconLock" class="text-right h-3 w-3 !text-black" stroke-width="2" v-if="shift.isCommitted" />
                            <ToolTipComponent
                                v-if="shift.inWorkflow && !shift.isCommitted"
                                icon="IconGitPullRequest"
                                icon-size="w-3 h-3 !text-black"
                                :stroke="2"
                                :tooltip-text="$t('This shift is currently in a workflow.')"
                                direction="top"
                                black-icon
                            />
                            <span>
                                {{ shift.craft.abbreviation }}
                                {{ shift.start }} - {{ shift.end }}
                            </span>
                        </div>
                    </div>

                    <div v-if="!showRoom" class="ml-0.5 flex items-center justify-end" :class="multiEditMode ? 'text-[10px]' : 'text-[10px]'">
                        ({{ computedUsedWorkerCount }}/{{ computedMaxWorkerCount }})
                        <span class="inline-block w-2.5 h-2.5 rounded-full ml-1"
                              :class="{
                                'bg-red-500': computedUsedWorkerCount === 0 && computedMaxWorkerCount !== 0,
                                'bg-yellow-500': computedUsedWorkerCount !== 0 && computedUsedWorkerCount < computedMaxWorkerCount,
                                'bg-green-500': computedUsedWorkerCount === computedMaxWorkerCount
                              }">
                        </span>
                    </div>

                    <div v-else-if="room" class="truncate">
                        , {{ room?.name }}
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full px-1" v-if="usePage().props.auth.user.calendar_settings.show_qualifications">
            <div class="w-full flex flex-row flex-wrap text-[10px] text-zinc-400">
                <div
                    v-for="(row) in computedShiftsQualificationsWithWorkerCount"
                    :key="row.shift_qualification_id"
                    class="flex items-center"
                >
                    {{ row.workerCount }}/{{ row.maxWorkerCount }}
                    <PropertyIcon
                        stroke-width="1"
                        class="text-black size-3 mx-1"
                        :name="getShiftQualificationById(row.shift_qualification_id)?.icon"
                    />
                </div>
            </div>
            <!-- Globale Qualifikationen anzeigen (analog Daily-View): Anzahl erfüllt/benötigt + Icon -->
            <div class="w-full flex flex-row flex-wrap text-[10px] text-zinc-400 mt-0.5">
                <div
                    v-for="gq in demandedGlobalQualifications"
                    :key="'gq-' + gq.id"
                    class="flex items-center mr-2"
                >
                    {{ countAssignedForGlobalQualification(gq.id) }}/{{ getGlobalQuantity(gq) }}
                    <PropertyIcon
                        stroke-width="1"
                        class="text-black size-3 mx-1"
                        :name="findGlobalQualification(gq.id)?.icon"
                    />
                </div>
            </div>

        </div>

        <div v-if="usePage().props.auth.user.calendar_settings.shift_notes" class="px-1 xsLight">
            {{ shift.description }}
        </div>
    </div>

    <ChooseUserSeriesShift
        v-if="showChooseUserSeriesShiftModal"
        @close-modal="showChooseUserSeriesShiftModal = false"
        @returnBuffer="setSeriesShiftData"
    />

    <MultipleShiftQualificationSlotsAvailable
        v-if="showMultipleShiftQualificationSlotsAvailableModal"
        :show="showMultipleShiftQualificationSlotsAvailableModal"
        :available-shift-qualification-slots="showMultipleShiftQualificationSlotsAvailableModalSlots"
        :dropped-user="showMultipleShiftQualificationSlotsAvailableModalDroppedUser"
        @close="closeMultipleShiftQualificationSlotsAvailableModal"
    />
</template>

<script setup lang="ts">
import {computed, reactive, ref, watch, getCurrentInstance, provide, inject} from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

import ChooseUserSeriesShift from '@/Pages/Projects/Components/ChooseUserSeriesShift.vue'
import MultipleShiftQualificationSlotsAvailable from '@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue'
// Mixins weiterverwenden (liefert z.B. $can / hasAdminRole o.ä.)
import IconLib from '@/Mixins/IconLib.vue'
import Permissions from '@/Mixins/Permissions.vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

// In <script setup> können Optionen inkl. Mixins gesetzt werden
defineOptions({
    mixins: [IconLib, Permissions]
})

/* ---------------- Props & Emits ---------------- */
const props = defineProps<{
    shift: any
    showRoom?: boolean
    craftId?: number
    event?: any
    room?: any
    maxCount?: number
    currentCount?: number
    freeEmployeeCount?: number
    freeMasterCount?: number
    highlightMode?: boolean
    highlightedId?: number | null
    highlightedType?: 0 | 1 | 2 | null
    multiEditMode?: boolean
    userForMultiEdit?: any
    highlightedShiftId?: number | string | null
    shiftQualifications: Array<{ id: number; icon?: any }>
}>()

const emit = defineEmits<{
    (e: 'dropFeedback', msg: string): void
    (e: 'desiresReload', droppedId: number | string, type: 0 | 1 | 2, seriesShiftData?: any): void
    (e: 'handleShiftAndEventForMultiEdit', checked: boolean, shift: any, event: any): void
    (e: 'clickOnEdit', shift: any): void
    (e: 'highlightShiftUsers', shift: any): void
    (e: 'hoverShiftUsers', shift: any | null): void
}>()

/* ---------------- Lokaler State ---------------- */
const showChooseUserSeriesShiftModal = ref(false)
const buffer = reactive({
    onlyThisDay: false,
    start: null as string | null,
    end: null as string | null,
    dayOfWeek: null as number | null
})

const showMultipleShiftQualificationSlotsAvailableModal = ref(false)
const showMultipleShiftQualificationSlotsAvailableModalSlots = ref<any[] | null>(null)
const showMultipleShiftQualificationSlotsAvailableModalDroppedUser = ref<any | null>(null)


const droppedUser = ref<any | null>(null)
const seriesShiftData = ref<any | null>(null)

/* ---------------- Helpers ---------------- */
const page = usePage()
const { proxy } = getCurrentInstance() || {}

/* ---------------- Computed ---------------- */
// Meta-Infos zu globalen Qualifikationen (Icon/Name)
const globalQualificationsMeta = computed(() => {
    const list = page?.props?.globalQualifications ?? []
    return Array.isArray(list) ? list : Object.values(list || {})
})

const findGlobalQualification = (id: number) =>
    globalQualificationsMeta.value.find((q: any) => q.id === id)

// Geforderte globale Qualifikationen dieser Schicht
const demandedGlobalQualifications = computed(() => {
    const arr = Array.isArray(props.shift?.globalQualifications)
        ? props.shift.globalQualifications
        : Object.values(props.shift?.globalQualifications || {})
    return arr.filter((gq: any) => (gq?.pivot?.quantity ?? gq?.quantity ?? 0) > 0)
})

const getGlobalQuantity = (gq: any) => (gq?.pivot?.quantity ?? gq?.quantity ?? 0)

// Hilfsfunktion: IDs der globalen Qualifikationen einer Person ermitteln (verschiedene Shapes erlauben)
function getPersonGlobalQualificationIds(person: any): number[] {
    const raw = person?.globalQualifications ?? person?.global_qualifications ?? person?.globalQualificationIds ?? person?.global_qualification_ids ?? []
    if (Array.isArray(raw) && raw.every((x: any) => typeof x === 'number')) return raw as number[]
    const arr = Array.isArray(raw) ? raw : Object.values(raw || {})
    let ids = arr
        .map((x: any) => (typeof x === 'number' ? x : x?.id ?? x?.global_qualification_id ?? null))
        .filter((v: any) => typeof v === 'number' && Number.isFinite(v)) as number[]
    if (ids.length === 0 && raw && !Array.isArray(raw) && typeof raw === 'object') {
        ids = Object.keys(raw).map((k) => Number(k)).filter((n) => Number.isFinite(n))
    }
    return ids
}

// Basiszählung: Wie viele aktuell zugewiesene Personen besitzen eine bestimmte GQ?
function countAssignedForGlobalQualificationBase(globalQualificationId: number): number {
    const groups = [props.shift?.users || [], props.shift?.freelancer || [], props.shift?.serviceProviders || []]
    return groups.reduce((acc, list: any[]) => acc + list.filter((p: any) => getPersonGlobalQualificationIds(p).includes(globalQualificationId)).length, 0)
}

// Zählung direkt aus dem Shift-Objekt (ohne Delta-System)
function countAssignedForGlobalQualification(globalQualificationId: number): number {
    return countAssignedForGlobalQualificationBase(globalQualificationId)
}
const computedMaxWorkerCount = computed(() => {
    let maxWorkerCount = 0
    props.shift?.shifts_qualifications?.forEach((sq: any) => {
        maxWorkerCount += sq.value
    })
    return maxWorkerCount
})

const computedUsedWorkerCount = computed(() => {
    return (props.shift.users?.length || 0) +
        (props.shift.freelancer?.length || 0) +
        (props.shift.serviceProviders?.length || 0)
})

const computedShiftsQualificationsWithWorkerCount = computed(() => {
    const rows: Array<{ shift_qualification_id: number, maxWorkerCount: number, workerCount: number }> = []
    props.shift?.shifts_qualifications?.forEach((sq: any) => {
        if (sq.value === null || sq.value === 0) return

        let assigned = 0

        props.shift.users?.forEach((u: any) => {
            if (u.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++
        })
        props.shift.freelancer?.forEach((f: any) => {
            if (f.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++
        })
        props.shift.serviceProviders?.forEach((p: any) => {
            if (p.pivot?.shift_qualification_id === sq.shift_qualification_id) assigned++
        })

        rows.push({
            shift_qualification_id: sq.shift_qualification_id,
            maxWorkerCount: sq.value,
            workerCount: assigned
        })
    })
    return rows
})

const shiftUserIds = computed(() => {
    const ids = {
        userIds: [] as Array<number | string>,
        freelancerIds: [] as Array<number | string>,
        providerIds: [] as Array<number | string>
    }
    props.shift.users?.forEach((u: any) => ids.userIds.push(u.id))
    props.shift.freelancer?.forEach((f: any) => ids.freelancerIds.push(f.id))
    props.shift.serviceProviders?.forEach((p: any) => ids.providerIds.push(p.id))
    return ids
})


const checkIfUserIsInCraft = computed(() => {
    const u = props.userForMultiEdit
    const craftId = props.shift?.craft?.id
    if (!u || craftId == null) return false
    if (isUniversallyApplicablePerson(u)) return true

    const assigned = new Set((u.assigned_craft_ids ?? u.craft_ids ?? []).map(Number))
    if (assigned.has(Number(craftId)) || Number(u.craftId) === Number(craftId)) return true

    // Prüfe ob Person über ein universelles Craft eine passende Qualifikation für diese Schicht hat
    const userQualis = Array.isArray(u?.shift_qualifications) ? u.shift_qualifications : Object.values(u?.shift_qualifications || {})
    return userQualis.some((uq: any) => universalCraftIds.value.has(Number(uq?.pivot?.craft_id)))
})

/* ---------------- Watcher ---------------- */
watch(() => props.multiEditMode, (val) => {
    if (!val && props.shift) {
        // Mutation des geschachtelten Prop-Objekts ist hier beabsichtigt
        props.shift.isCheckedForMultiEdit = false
    }
})

watch(() => props.userForMultiEdit, (val) => {
    if (props.shift) {
        props.shift.isCheckedForMultiEdit = val ? val.shift_ids?.includes(props.shift.id) : false
    }
}, { deep: true })

/* ---------------- Methoden (als Funktionen) ---------------- */
function isIdHighlighted(highlightedId?: number | null, highlightedType?: 0 | 1 | 2 | null) {
    const typeMap: Record<number, 'userIds' | 'freelancerIds' | 'providerIds'> = {
        0: 'userIds',
        1: 'freelancerIds',
        2: 'providerIds'
    }
    if (highlightedId == null || highlightedType == null) return false
    const key = typeMap[highlightedType]
    return shiftUserIds.value[key].includes(highlightedId)
}

function handleClickEvent() {
    if (props.multiEditMode) return


    if (props.highlightMode) {
        emit('highlightShiftUsers', props.shift)
        return
    }

    // Zugriff auf $can/hasAdminRole über Mixin (global am proxy)
    const canPlan = typeof proxy?.$can === 'function' ? proxy.$can('can plan shifts') : false
    const isAdmin = typeof (proxy as any)?.hasAdminRole === 'function' ? (proxy as any).hasAdminRole() : false

    if (canPlan || isAdmin) {
        emit('clickOnEdit', props.shift)
    }
}

function getTargetCraftIdFromShift(): number | null {
    const id =
        props.shift?.craft?.id ??
        props.shift?.craft_id ??
        props.shift?.craftId ??
        props.craftId ??
        null
    return id == null ? null : Number(id)
}

function getPersonCraftId(person: any): number | null {
    const id = person?.craft_id ?? person?.craftId ?? person?.craft?.id ?? null
    return id == null ? null : Number(id)
}

// Injected crafts from parent ShiftPlan (provide/inject)
const injectedCrafts = inject<any>('shiftPlanCrafts', ref([]))

const craftsById = computed<Record<number, any>>(() => {
    // Bevorzuge injected crafts (von ShiftPlan.vue), dann page props als Fallback
    const injected = injectedCrafts?.value ?? injectedCrafts ?? []
    const raw = (Array.isArray(injected) && injected.length > 0)
        ? injected
        : ((page?.props as any)?.crafts ??
           (page?.props as any)?.allCrafts ??
           (page?.props as any)?.craftList ??
           [])
    const arr = Array.isArray(raw) ? raw : Object.values(raw || {})
    const map: Record<number, any> = {}
    arr.forEach((c: any) => {
        if (c?.id != null) map[Number(c.id)] = c
    })
    return map
})

function isUniversallyApplicablePerson(person: any): boolean {
    if (!person) return false

    // akzeptiere beide Naming-Varianten + ggf. nested craft
    if (person.craft_universally_applicable === true) return true
    if (person.craft_are_universally_applicable === true) return true
    if (person.craft?.universally_applicable === true) return true

    // manchmal kommen booleans als 0/1
    if (person.craft_universally_applicable === 1) return true
    if (person.craft_are_universally_applicable === 1) return true
    if (person.craft?.universally_applicable === 1) return true

    // Fallback: craftId lookup (wenn crafts in page props vorhanden)
    const cid = getPersonCraftId(person)
    if (cid != null && craftsById.value[cid] != null) {
        return !!craftsById.value[cid]?.universally_applicable
    }

    return false
}

function personCanWorkOnCraft(person: any, targetCraftId: number): boolean {
    if (isUniversallyApplicablePerson(person)) return true

    const ids = new Set<number>([
        ...(person.craft_ids ?? []),
        ...(person.assigned_craft_ids ?? []),
    ].map(Number).filter((n) => Number.isFinite(n)))

    const primary = getPersonCraftId(person)
    if (ids.has(Number(targetCraftId)) || (primary != null && primary === Number(targetCraftId))) return true

    // Prüfe ob Person über ein universelles Craft eine passende Qualifikation hat
    const userQualis = Array.isArray(person?.shift_qualifications)
        ? person.shift_qualifications
        : Object.values(person?.shift_qualifications || {})
    return userQualis.some((uq: any) => universalCraftIds.value.has(Number(uq?.pivot?.craft_id)))
}

function requiredQualificationIdsForShift(): number[] {
    const sq = Array.isArray(props.shift?.shifts_qualifications)
        ? props.shift.shifts_qualifications
        : Object.values(props.shift?.shifts_qualifications || {})

    // nur Qualis mit echten Slots (>0)
    return sq
        .filter((x: any) => (x?.value ?? 0) > 0)
        .map((x: any) => Number(x.shift_qualification_id))
        .filter((n: any) => Number.isFinite(n))
}

// IDs aller universell einsetzbaren Crafts
const universalCraftIds = computed<Set<number>>(() => {
    const arr = Object.values(craftsById.value)
    const ids = new Set<number>()
    arr.forEach((c: any) => {
        if (c?.universally_applicable) ids.add(Number(c.id))
    })
    return ids
})

function matchingUserQualisForShift(person: any, targetCraftId: number): any[] {
    const requiredIds = new Set(requiredQualificationIdsForShift())
    const userQualis = Array.isArray(person?.shift_qualifications)
        ? person.shift_qualifications
        : Object.values(person?.shift_qualifications || {})

    // Bevorzuge direkte Qualifikationen (pivot.craft_id === targetCraftId)
    const directMatches = userQualis.filter((uq: any) => {
        const uqId = Number(uq?.id)
        if (!requiredIds.has(uqId)) return false
        const uqCraftId = uq?.pivot?.craft_id
        if (uqCraftId == null) return true
        return Number(uqCraftId) === Number(targetCraftId)
    })

    if (directMatches.length > 0) return directMatches

    // Fallback: Qualifikationen über universell einsetzbare Crafts
    return userQualis.filter((uq: any) => {
        const uqId = Number(uq?.id)
        if (!requiredIds.has(uqId)) return false
        const uqCraftId = uq?.pivot?.craft_id
        if (uqCraftId == null) return false
        return universalCraftIds.value.has(Number(uqCraftId))
    })
}

function onDragOver(event: DragEvent) {
    event.preventDefault()
}

function onDrop(event: DragEvent) {
    event.preventDefault()

    const raw =
        event.dataTransfer?.getData('application/json') ||
        event.dataTransfer?.getData('text/plain') ||
        ''

    try {
        droppedUser.value = raw ? JSON.parse(raw) : null
    } catch {
        droppedUser.value = null
    }

    if (props.event?.is_series) {
        showChooseUserSeriesShiftModal.value = true
        return
    }
    saveUser()
}

function getShiftQualificationById(id: number) {
    return props.shiftQualifications.find(q => q.id === id)
}

function setSeriesShiftData(data: any) {
    showChooseUserSeriesShiftModal.value = false
    seriesShiftData.value = data
    saveUser()
}

function droppedUserHasQualificationForCraft(user: any) {
    if (user?.craft_universally_applicable) return true
    if (!user?.shift_qualifications?.length) return false
    const cid = props.shift?.craft?.id ?? props.craftId
    return user.shift_qualifications.some((q: any) => q.pivot && (q.pivot.craft_id === cid))
}

function droppedUserAlreadyWorksOnShift(user: any) {
    if (!user) return false
    if (user.type === 0) return shiftUserIds.value.userIds.includes(user.id)
    if (user.type === 1) return shiftUserIds.value.freelancerIds.includes(user.id)
    if (user.type === 2) return shiftUserIds.value.providerIds.includes(user.id)
    return false
}

function dropFeedbackUserAlreadyWorksOnShift(userType: 0 | 1 | 2) {
    const map: Record<0 | 1 | 2, string> = {
        0: (proxy as any)?.$t?.('Employee') ?? 'Employee',
        1: (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer',
        2: (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'
    }
    emit('dropFeedback', (proxy as any)?.$t?.('{0} already assigned to a shift.', [map[userType]]) ?? `${map[userType]} already assigned to a shift.`)
}

function droppedUserHasNoQualifications(user: any) {
    return !user?.shift_qualifications?.length
}

function dropFeedbackUserHasNoQualifications(userType: 0 | 1 | 2) {
    const map: Record<0 | 1 | 2, string> = {
        0: (proxy as any)?.$t?.('Employee') ?? 'Employee',
        1: (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer',
        2: (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'
    }
    emit('dropFeedback', (proxy as any)?.$t?.('{0} has no craft function and therefore cannot be assigned.', [map[userType]]) ?? `${map[userType]} has no craft function and therefore cannot be assigned.`)
}

function dropFeedbackNoSlotsForQualification(userType: 0 | 1 | 2) {
    const map: Record<0 | 1 | 2, string> = {
        0: (proxy as any)?.$t?.('Employee') ?? 'Employee',
        1: (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer',
        2: (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'
    }
    emit('dropFeedback', (proxy as any)?.$t?.('There is no position that can be filled by {0} with the available craft function.',[map[userType]]) ?? `There is no position that can be filled by ${map[userType]} with the available craft function.`)
}

function openMultipleShiftQualificationSlotsAvailableModal(user: any, slots: any[]) {
    showMultipleShiftQualificationSlotsAvailableModalDroppedUser.value = user
    showMultipleShiftQualificationSlotsAvailableModalSlots.value = slots
    showMultipleShiftQualificationSlotsAvailableModal.value = true
}

function closeMultipleShiftQualificationSlotsAvailableModal(user?: any, selectedShiftQualificationId?: number) {
    showMultipleShiftQualificationSlotsAvailableModal.value = false
    showMultipleShiftQualificationSlotsAvailableModalSlots.value = null
    showMultipleShiftQualificationSlotsAvailableModalDroppedUser.value = null

    if (user && selectedShiftQualificationId) {
        assignUser(user, selectedShiftQualificationId)
    }
}

function saveUser() {
    const user = droppedUser.value
    if (!user) return

    const targetCraftId = getTargetCraftIdFromShift()
    if (targetCraftId == null) return

    // 1) Craft-Zulassung (universal => immer ok)
    if (!personCanWorkOnCraft(user, targetCraftId)) {
        const label = user.type === 0
            ? (proxy as any)?.$t?.('Employee') ?? 'Employee'
            : user.type === 1
                ? (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer'
                : (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'

        emit('dropFeedback',
            (proxy as any)?.$t?.('{0} cannot be assigned to shifts of this craft.', [label]) ??
            `${label} cannot be assigned to shifts of this craft.`
        )
        return
    }

    // 2) Already assigned?
    if (droppedUserAlreadyWorksOnShift(user)) {
        dropFeedbackUserAlreadyWorksOnShift(user.type)
        return
    }

    // 3) Slots/Qualifikationen prüfen
    const requiredIds = requiredQualificationIdsForShift()

    // wenn die Schicht keine Slots hat (alle 0 / leer), bleibt das Verhalten wie bisher:
    // -> keine offene Funktion
    if (requiredIds.length === 0) {
        dropFeedbackNoSlotsForQualification(user.type)
        return
    }

    // User muss passende Quali haben (bei universal: craft-unabhängig matchen!)
    const matches = matchingUserQualisForShift(user, targetCraftId)

    if (!matches.length) {
        const label = user.type === 0
            ? (proxy as any)?.$t?.('Employee') ?? 'Employee'
            : user.type === 1
                ? (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer'
                : (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'

        emit('dropFeedback',
            (proxy as any)?.$t?.('There is no position that can be filled by {0} with the available craft function.', [label]) ??
            `There is no position that can be filled by ${label} with the available craft function.`
        )
        return
    }

    // 4) freie Slots je Quali prüfen
    const qualificationsWithAvailableSlots = matches.filter((q: any) => {
        const qualificationData = computedShiftsQualificationsWithWorkerCount.value.find(
            (sq: any) => sq.shift_qualification_id === q.id
        )
        if (!qualificationData || qualificationData.maxWorkerCount === 0) return false
        return qualificationData.workerCount < qualificationData.maxWorkerCount
    })

    if (qualificationsWithAvailableSlots.length === 0) {
        const label = user.type === 0
            ? (proxy as any)?.$t?.('Employee') ?? 'Employee'
            : user.type === 1
                ? (proxy as any)?.$t?.('Freelancer') ?? 'Freelancer'
                : (proxy as any)?.$t?.('ServiceProvider') ?? 'ServiceProvider'

        emit('dropFeedback',
            (proxy as any)?.$t?.('{0} has no open function in the shift and therefore cannot be assigned.', [label]) ??
            `${label} has no open function in the shift and therefore cannot be assigned.`
        )
        return
    }

    if (qualificationsWithAvailableSlots.length === 1) {
        assignUser(user, qualificationsWithAvailableSlots[0].id)
        return
    }

    openMultipleShiftQualificationSlotsAvailableModal(user, qualificationsWithAvailableSlots)
}


function resolveCraftAbbreviationForAssignment(user: any, shiftQualificationId: number): string {
    const targetCraftId = getTargetCraftIdFromShift()
    const userQualis = Array.isArray(user?.shift_qualifications)
        ? user.shift_qualifications
        : Object.values(user?.shift_qualifications || {})

    // Prüfe ob die Qualifikation direkt zum Schicht-Craft gehört
    const directMatch = userQualis.find((uq: any) =>
        Number(uq?.id) === shiftQualificationId &&
        Number(uq?.pivot?.craft_id) === Number(targetCraftId)
    )
    if (directMatch) return user.craft_abbreviation ?? ''

    // Sonst: Qualifikation kommt über ein universelles Craft
    const universalMatch = userQualis.find((uq: any) =>
        Number(uq?.id) === shiftQualificationId &&
        universalCraftIds.value.has(Number(uq?.pivot?.craft_id))
    )
    if (universalMatch) {
        const uCraft = craftsById.value[Number(universalMatch.pivot.craft_id)]
        return uCraft?.abbreviation ?? user.craft_abbreviation ?? ''
    }

    return user.craft_abbreviation ?? ''
}

function assignUser(user: any, shiftQualificationId: number) {
    const craftAbbr = resolveCraftAbbreviationForAssignment(user, shiftQualificationId)
    axios.post(
        route('shift.assignUserByType', { shift: props.shift.id }),
        {
            userId: user.id,
            userType: user.type,
            shiftQualificationId,
            seriesShiftData: seriesShiftData.value,
            craft_abbreviation: craftAbbr
        }
    ).then(() => {
        emit('desiresReload', user.id, user.type, seriesShiftData.value || undefined)
    }).catch(() => {
        emit('dropFeedback',
            (proxy as any)?.$t?.('Saving failed') ?? 'Saving failed'
        )
    })
}


function handleShiftAndEventForMultiEdit(checked: boolean, shift: any, event: any) {
    emit('handleShiftAndEventForMultiEdit', checked, shift, event)
}

const hasUserHighlightSelection = computed(() => {
    return !!props.highlightMode && props.highlightedId != null && props.highlightedType != null
})

const hasShiftHighlightSelection = computed(() => {
    return !!props.highlightMode && props.highlightedShiftId != null
})

const hasAnyHighlightSelection = computed(() => {
    return hasUserHighlightSelection.value || hasShiftHighlightSelection.value
})

const isThisShiftHighlighted = computed(() => {
    return !!props.highlightMode && props.highlightedShiftId != null && props.shift?.id === props.highlightedShiftId
})

const matchesUserHighlight = computed(() => {
    if (!hasUserHighlightSelection.value) return false
    return isIdHighlighted(props.highlightedId, props.highlightedType)
})

const matchesAnyHighlight = computed(() => {
    return isThisShiftHighlighted.value || matchesUserHighlight.value
})
</script>
