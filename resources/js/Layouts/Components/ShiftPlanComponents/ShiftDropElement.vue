<template>
    <div class="w-full group/shift bg-background-gray hover:bg-gray-50 duration-300 ease-in-out cursor-pointer">
        <div
            :class="[!highlightMode || !isIdHighlighted(highlightedId, highlightedType) ? 'opacity-30 px-1' : 'bg-pink-500 !text-white px-1', multiEditMode ?'text-[10px]' : '']"
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
                        <div class="text-[11px]">
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
                    <component :is="IconLock" class="text-right h-3 w-3" v-if="shift.isCommitted" />
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
import { computed, reactive, ref, watch, getCurrentInstance } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

// UI / Components
import { CheckIcon } from '@heroicons/vue/outline'
import { IconLock } from '@tabler/icons-vue'
import VueMathjax from 'vue-mathjax-next'
import ChooseUserSeriesShift from '@/Pages/Projects/Components/ChooseUserSeriesShift.vue'
import ShiftQualificationIconCollection from '@/Layouts/Components/ShiftQualificationIconCollection.vue'
import MultipleShiftQualificationSlotsAvailable from '@/Pages/Projects/Components/MultipleShiftQualificationSlotsAvailable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'

// Mixins weiterverwenden (liefert z.B. $can / hasAdminRole o.ä.)
import IconLib from '@/Mixins/IconLib.vue'
import Permissions from '@/Mixins/Permissions.vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

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
    shiftQualifications: Array<{ id: number; icon?: any }>
}>()

const emit = defineEmits<{
    (e: 'dropFeedback', msg: string): void
    (e: 'desiresReload', droppedId: number | string, type: 0 | 1 | 2, seriesShiftData?: any): void
    (e: 'handleShiftAndEventForMultiEdit', checked: boolean, shift: any, event: any): void
    (e: 'clickOnEdit', shift: any): void
}>()

/* ---------------- Lokaler State ---------------- */
const showChooseUserSeriesShiftModal = ref(false)
const buffer = reactive({
    onlyThisDay: false,
    start: null as string | null,
    end: null as string | null,
    dayOfWeek: null as number | null
})

const selectedUser = ref<any>(null)
const dropFeedback = ref<string | null>(null)
const showQualificationRowExpander = ref(false)

const showMultipleShiftQualificationSlotsAvailableModal = ref(false)
const showMultipleShiftQualificationSlotsAvailableModalSlots = ref<any[] | null>(null)
const showMultipleShiftQualificationSlotsAvailableModalDroppedUser = ref<any | null>(null)

const droppedUser = ref<any | null>(null)
const seriesShiftData = ref<any | null>(null)

/* ---------------- Helpers ---------------- */
const page = usePage()
const { proxy } = getCurrentInstance() || {}

/* ---------------- Computed ---------------- */
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


const checkIfUserIsInCraft = computed<boolean>(() => {
    const u = props.userForMultiEdit
    const craftId = props.shift?.craft?.id

    // Fehlende Daten → kein Match
    if (!u || craftId == null) return false

    // Universell anwendbar → immer true
    if (u.craft_are_universally_applicable) return true

    // IDs normalisieren (z. B. string → number) und performant prüfen
    const assigned = new Set((u.assigned_craft_ids ?? []).map(Number))

    // Treffer, wenn explizit zugeteilt ODER Primärcraft identisch
    return assigned.has(Number(craftId)) || u.craftId === craftId
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
    // Zugriff auf $can/hasAdminRole über Mixin (global am proxy)
    const canPlan = typeof proxy?.$can === 'function' ? proxy.$can('can plan shifts') : false
    const isAdmin = typeof (proxy as any)?.hasAdminRole === 'function' ? (proxy as any).hasAdminRole() : false

    if (canPlan || isAdmin) {
        emit('clickOnEdit', props.shift)
    }
}

function onDragOver(event: DragEvent) {
    event.preventDefault()
}

function onDrop(event: DragEvent) {
    event.preventDefault()
    try {
        const data = event.dataTransfer?.getData('application/json')
        droppedUser.value = data ? JSON.parse(data) : null
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

    const craftId = props.shift?.craft?.id ?? props.craftId
    const qualificationsForCraft = (user.shift_qualifications || []).filter((q: any) => q.pivot && q.pivot.craft_id === craftId)

    if (!user.craft_universally_applicable) {
        if (!user.craft_ids || !user.craft_ids.includes(craftId)) {
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
    }

    if (droppedUserAlreadyWorksOnShift(user)) {
        dropFeedbackUserAlreadyWorksOnShift(user.type)
        return
    }
    if (droppedUserHasNoQualifications(user)) {
        dropFeedbackUserHasNoQualifications(user.type)
        return
    }
    if (qualificationsForCraft.length === 0) {
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

    if (qualificationsForCraft.length === 1) {
        assignUser(user, qualificationsForCraft[0].id)
        return
    }

    openMultipleShiftQualificationSlotsAvailableModal(user, qualificationsForCraft)
}

function assignUser(user: any, shiftQualificationId: number) {
    axios.post(
        route('shift.assignUserByType', { shift: props.shift.id }),
        {
            userId: user.id,
            userType: user.type,
            shiftQualificationId,
            seriesShiftData: seriesShiftData.value,
            craft_abbreviation: user.craft_abbreviation
        }
    ).then(() => {
        emit('desiresReload', user.id, user.type, seriesShiftData.value || undefined)
    })
}

function handleShiftAndEventForMultiEdit(checked: boolean, shift: any, event: any) {
    emit('handleShiftAndEventForMultiEdit', checked, shift, event)
}

/* ---------------- Expose in Template (script setup exportiert automatisch) ---------------- */
</script>
