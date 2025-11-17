<template>
    <ArtworkBaseModal
        v-if="show"
        @close="() => close(false)"
        :title="$t('Qualification assignment')"
        :description="$t('Shift') + ' ' + getCurrentShiftCount() + '/' + getMaxShiftCount()"
    >
        <div class="mx-4">
            <!-- Header mit Avatar & Frage -->
            <div class="mb-4 xsLight">
                {{ $t('In which qualification should') }}
                <img
                    class="inline h-6 w-6 object-cover rounded-full ring-2 ring-white shadow"
                    :src="user?.profile_photo_url"
                    :alt="'Profilfoto ' + (user?.display_name || '')"
                />
                {{ $t('{0} be used in the following layer?', user?.display_name) }}
            </div>

            <!-- Progress & Meta -->
            <div class="space-y-3">


                <div
                    v-if="currentShiftToAssign"
                    class="xsLight my-2 grid gap-2 sm:flex sm:items-center sm:gap-3"
                >
                  <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs">
                    <span class="font-medium">{{ $t('Shift') }}</span>
                    <span>•</span>
                    <span>{{ currentShiftToAssign.shift.craft.name }}</span>
                    <span class="opacity-60">({{ currentShiftToAssign.shift.craft.abbreviation }})</span>
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
                    <BaseUIButton
                        v-for="slot in currentShiftToAssign?.availableSlots || []"
                        :key="slot.id"
                        :label="$t('Insert as {0}', [slot.name])"
                        :icon="slot.icon"
                        is-add-button
                        @click="handleShift(currentShiftToAssign!.shift.id, slot.id)"
                    />
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
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

type Craft = {
    id: number
    name: string
    abbreviation: string
}

type Shift = {
    id: number
    start: string
    end: string
    craft: Craft
}

type AvailableSlot = {
    id: number
    name: string
    icon?: string
}

type ShiftToAssign = {
    shift: Shift
    availableSlots: AvailableSlot[]
}

type User = {
    profile_photo_url?: string
    display_name: string
}

const props = defineProps<{
    show: boolean
    user: User
    shifts: ShiftToAssign[]
}>()

const emit = defineEmits<{
    (e: 'close', closedForAssignment: boolean, shiftsToAssign: { shiftId: number; shiftQualificationId?: number }[]): void
}>()

// --- State ---
const state = reactive({
    currentShiftToAssignIndex: 0,
    shiftsToAssign: [] as { shiftId: number; shiftQualificationId?: number }[],
})

// --- Computed ---
const getMaxShiftCount = () => props.shifts?.length ?? 0
const getCurrentShiftCount = () => Math.min(state.currentShiftToAssignIndex + 1, Math.max(getMaxShiftCount(), 1))

const currentShiftToAssign = computed<ShiftToAssign | undefined>(() => {
    if (!props.shifts || props.shifts.length === 0) return undefined
    return props.shifts[state.currentShiftToAssignIndex]
})

const isLastShiftToAssign = () => getCurrentShiftCount() === getMaxShiftCount()

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

const handleShift = (shiftId: number, shiftQualificationId: number) => {
    state.shiftsToAssign.push({ shiftId, shiftQualificationId })
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
