<template>
    <ArtworkBaseModal
        v-if="show"
        @close="() => close(false)"
        :title="$t('Assign function')"
        :description="modalDescription"
    >
        <div class="mx-4">
            <!-- Header mit Avatar & Frage -->
            <div class="mb-4 text-sm/5 font-bold text-text-subtle">
                <img
                    class="inline h-6 w-6 object-cover rounded-full ring-2 ring-white shadow mr-1"
                    :src="user?.profile_photo_url"
                    :alt="'Profilfoto ' + (user?.display_name || '')"
                />
                {{ $t('In which function should {0} work in this shift?', [user?.display_name]) }}
            </div>

            <!-- Progress & Meta -->
            <div class="space-y-3">


                <div
                    v-if="currentShiftToAssign"
                    class="text-sm/5 font-bold text-text-subtle my-2 grid gap-2 sm:flex sm:items-center sm:gap-3"
                >
                  <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs">
                    <span class="font-medium">{{ $t('Shift') }}</span>
                    <span>•</span>
                    <span>{{ (currentShiftToAssign.shift.craft ?? resolveCraft(currentShiftToAssign.shift.craftId))?.name }}</span>
                    <span class="opacity-60">({{ (currentShiftToAssign.shift.craft ?? resolveCraft(currentShiftToAssign.shift.craftId))?.abbreviation }})</span>
                  </span>

                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs">
                        <PropertyIcon name="IconClock" class="h-4 w-4" />
                    <span>{{ currentShiftToAssign.shift.start }}</span>
                    <span>–</span>
                    <span>{{ currentShiftToAssign.shift.end }}</span>
                  </span>
                </div>
            </div>

            <!-- Slots / Aktionen -->
            <div class="mt-4 flex flex-col">
                <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-3">
                    <div
                        v-for="slot in currentShiftToAssign?.availableSlots || []"
                        :key="`${slot.id}-${slot.isOverbooked ? 'overbooked' : 'regular'}`"
                        class="flex flex-col gap-1"
                    >
                        <BaseUIButton
                            class="w-full"
                            :label="$t('Insert as {0}', [slot.name]) + (slot.isOverbooked ? ' (' + $t('Overbook') + ')' : '')"
                            :icon="slot.icon"
                            is-add-button
                            :class="{ '!border-warning !border-dashed': slot.isOverbooked }"
                            @click="handleShift(currentShiftToAssign!.shift.id, slot.id, !!slot.isOverbooked)"
                        />
                        <!-- Überbuchen: Platz ist bereits voll besetzt — Info-Tooltip erklärt die Folge -->
                        <div v-if="slot.isOverbooked" class="flex items-center gap-1 text-[11px] text-warning">
                            <ToolTipComponent
                                icon="IconInfoCircle"
                                icon-size="w-3.5 h-3.5"
                                classes-button="mt-0"
                                direction="top"
                                :tooltip-text="$t('All planned places for this function are already staffed. Overbooking adds the person on top of the planned places; overbooked people are marked in the duty roster and count in addition to the regular places.')"
                            />
                            <span>{{ $t('Place already fully staffed') }}</span>
                        </div>
                    </div>
                </div>

                <div class="w-full mt-4">
                    <BaseUIButton
                        class="w-full"
                        :label="$t('Skip assignment')"
                        is-cancel-button
                        @click="skipShift"
                    />
                </div>

                <!-- Fallback, wenn keine Shifts vorhanden -->
                <div
                    v-if="!currentShiftToAssign"
                    class="mt-6 rounded-xl border border-dashed p-6 text-center text-sm opacity-70"
                    role="status"
                >
                    {{ $t('No shifts to assign.') }}
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, defineProps, defineEmits, reactive, toRefs, watch } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";
import {useTranslation} from "@/Composeables/Translation.js";

type Craft = {
    id: number
    name: string
    abbreviation: string
}

type Shift = {
    id: number
    start: string
    end: string
    craft?: Craft
    craftId?: number
    startDate?: string
    dayLabel?: string
    roomId?: number
    room?: { id?: number; name?: string }
}

type RoomLike = { id?: number; roomId?: number; name?: string; roomName?: string }

type AvailableSlot = {
    id: number
    name: string
    icon?: string
    isOverbooked?: boolean
}

type ShiftToAssign = {
    shift: Shift
    availableSlots: AvailableSlot[]
}

type User = {
    profile_photo_url?: string
    display_name: string
}

const props = withDefaults(defineProps<{
    show: boolean
    user: User
    shifts: ShiftToAssign[]
    /** Räume des Plans (roomId/roomName oder id/name) zur Auflösung des Raumnamens im Titel */
    rooms?: RoomLike[]
}>(), {
    rooms: () => [],
})

const $t = useTranslation()

const emit = defineEmits<{
    (e: 'close', closedForAssignment: boolean, shiftsToAssign: { shiftId: number; shiftQualificationId?: number; isOverbooked?: boolean }[]): void
}>()

const { resolveCraft } = useShiftPlanLookups();

// --- State ---
const state = reactive({
    currentShiftToAssignIndex: 0,
    shiftsToAssign: [] as { shiftId: number; shiftQualificationId?: number; isOverbooked?: boolean }[],
})

// --- Computed ---
const getMaxShiftCount = () => props.shifts?.length ?? 0
const getCurrentShiftCount = () => Math.min(state.currentShiftToAssignIndex + 1, Math.max(getMaxShiftCount(), 1))

const currentShiftToAssign = computed<ShiftToAssign | undefined>(() => {
    if (!props.shifts || props.shifts.length === 0) return undefined
    return props.shifts[state.currentShiftToAssignIndex]
})

const isLastShiftToAssign = () => getCurrentShiftCount() === getMaxShiftCount()

/** YYYY-MM-DD → DD.MM.YYYY (Konvention: nie ISO in der Oberfläche) */
const formatDate = (raw?: string): string => {
    if (!raw) return ''
    const m = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})/)
    return m ? `${m[3]}.${m[2]}.${m[1]}` : String(raw)
}

const resolveRoomName = (shift?: Shift): string => {
    if (!shift) return ''
    if (shift.room?.name) return shift.room.name
    const roomId = shift.roomId ?? shift.room?.id
    if (roomId == null) return ''
    const room = (props.rooms || []).find((r) => Number(r.roomId ?? r.id) === Number(roomId))
    return room?.roomName ?? room?.name ?? ''
}

const toHHMM = (value?: string): string => (value ? String(value).slice(0, 5) : '')

/** Untertitel: „Schicht 1/2 · 06.09.2026 · Große Bühne · 10:00–18:00" */
const modalDescription = computed(() => {
    const shift = currentShiftToAssign.value?.shift
    const parts = [`${$t('Shift')} ${getCurrentShiftCount()}/${getMaxShiftCount()}`]
    if (shift) {
        const date = formatDate(shift.startDate) || shift.dayLabel || ''
        if (date) parts.push(date)
        const room = resolveRoomName(shift)
        if (room) parts.push(room)
        if (shift.start && shift.end) parts.push(`${toHHMM(shift.start)}–${toHHMM(shift.end)}`)
    }
    return parts.join(' · ')
})

const progressPercent = computed(() => {
    const max = getMaxShiftCount()
    if (max === 0) return 0
    return Math.round(((state.currentShiftToAssignIndex + 1) / max) * 100)
})

// --- Methods ---
const nextShift = () => {
    if (state.currentShiftToAssignIndex < getMaxShiftCount() - 1) {
        state.currentShiftToAssignIndex++
    }
}

const handleShift = (shiftId: number, shiftQualificationId: number, isOverbooked: boolean = false) => {
    state.shiftsToAssign.push({ shiftId, shiftQualificationId, isOverbooked })
    if (isLastShiftToAssign()) {
        close(true)
        return
    }
    nextShift()
}

const skipShift = () => {
    // nichts hinzufügen, nur weiter
    if (isLastShiftToAssign()) {
        close(true)
        return
    }
    nextShift()
}

const close = (closedForAssignment: boolean) => {
    emit('close', closedForAssignment, state.shiftsToAssign)
}

// Defensive: wenn sich die Shifts-Prop ändert, Index & Auswahl zurücksetzen
watch(
    () => props.shifts,
    () => {
        state.currentShiftToAssignIndex = 0
        state.shiftsToAssign = []
    }
)
</script>

<style scoped>
/* Kleine, unaufdringliche Verbesserungen */
</style>
