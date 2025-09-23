<template>
    <div
        :id="`shift-container-${event.id}-${shift.id}`"
        :class="[
      'w-56 flex flex-col relative rounded-xl border border-zinc-200 bg-white/90 shadow-sm ring-1 ring-black/5 overflow-hidden',
      highlight
    ]"
    >
        <!-- Header -->
        <div
            class="relative h-[40px] flex items-center justify-between px-3 text-white text-xs"
            :class="headerBgClass"
        >
            <div class="flex items-center gap-2">
                <!-- Craft-Abkürzung -->
                <span class="font-semibold tracking-wide">
          {{ shift.craft.abbreviation }}
        </span>

                <!-- Kapazitäts-Chip -->
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 font-medium ring-1 ring-inset ring-white/30"
                    :title="$t('Assigned / Required')"
                >
          <span class="tabular-nums">{{ usedCount }}</span>/<span class="tabular-nums">{{ maxCount }}</span>
        </span>
            </div>

            <div class="flex items-center gap-1">
                <!-- Warn-/Urlaub-Icon -->
                <div v-if="shift.infringement || anyoneHasVacation" class="inline-flex items-center rounded-full bg-red-500/20 px-2 py-1 ring-1 ring-inset ring-white/30">
                    <IconExclamationCircle class="h-4 w-4" stroke-width="1.5" />
                </div>

                <!-- Voll-Check -->
                <div v-else-if="isFull" class="inline-flex items-center rounded-full bg-white/15 px-2 py-1 ring-1 ring-inset ring-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10.414" height="8.032" viewBox="0 0 10.414 8.032" aria-hidden="true">
                        <path d="M-1151.25,4789.2l3.089,3.088,5.911-5.911" transform="translate(1151.957 -4785.674)" fill="none" stroke="#fcfcfb" stroke-width="2"/>
                    </svg>
                </div>

                <!-- Menü -->
                <BaseMenu
                    white-menu-background
                    v-if="can('can plan shifts') || isAdmin"
                    dots-size="h-5 w-5 text-white"
                >
                    <BaseMenuItem white-menu-background title="Edit" :icon="IconEdit" @click="editShift" />
                    <BaseMenuItem white-menu-background title="Clear" :icon="IconCircleX" @click="clearShiftUsers(shift)" />
                    <BaseMenuItem white-menu-background title="Delete" :icon="IconTrash" @click="deleteShift(shift.id)" />
                </BaseMenu>
            </div>

            <!-- Kapazitäts-Progress (fein, dezent) -->
            <div class="pointer-events-none absolute -bottom-1 left-0 right-0 h-1 bg-zinc-800/40">
                <div
                    class="h-1 transition-all"
                    :class="[progressWidth === '100%' ? 'bg-green-400' : progressWidth > '50%' ? 'bg-yellow-400 rounded-r-xl' : progressWidth > '0%' ? 'bg-orange-400 rounded-r-xl' : 'bg-zinc-400']"
                    :style="{ width: progressWidth }"
                ></div>
            </div>
        </div>

        <!-- Body -->
        <div class="mt-1 rounded-b-xl bg-zinc-50/60 px-1.5 py-2 overflow-y-auto h-full w-full">
            <!-- Zeiten -->
            <div
                v-if="!editTimes"
                class="text-xs mb-1 hover:bg-white cursor-pointer px-1.5 py-1 rounded-lg w-fit transition"
                @click="openEditInLineTimes"
                :title="$t('Edit times')"
            >
                <span>{{ shift.start }} - {{ shift.end }}</span>
            </div>

            <div v-else class="relative">
                <button
                    class="absolute right-1.5 top-1.5 text-zinc-500 hover:text-red-600 transition"
                    @click="resetForm"
                    :aria-label="$t('Cancel')"
                >
                    <component :is="IconX" class="h-4 w-4" />
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2" :id="`container-${shift.id}`">
                    <BaseInput
                        type="time"
                        v-model="updateTimeForm.start"
                        :label="$t('Start-Time')"
                        :id="`shift-start${shift.id}`"
                        required
                        is-small
                        @focusout="checkFocus"
                        @change="validateShiftDates"
                        classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                    />
                    <BaseInput
                        type="time"
                        v-model="updateTimeForm.end"
                        :label="$t('End-Time')"
                        :id="`shift-end${shift.id}`"
                        required
                        is-small
                        @focusout="checkFocus"
                        @change="validateShiftDates"
                        classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                    />
                </div>

                <!-- Validierung: Start/Ende -->
                <div v-if="hasTimeMessages" class="mt-2 space-y-1">
                    <div v-if="validationMessages.warnings.shift_start.length" class="flex flex-col">
            <span v-for="(warning, i) in validationMessages.warnings.shift_start" :key="'ws'+i" class="text-xs text-orange-500">
              {{ warning }}
            </span>
                    </div>
                    <div v-if="validationMessages.errors.shift_start.length" class="flex flex-col">
            <span v-for="(error, i) in validationMessages.errors.shift_start" :key="'es'+i" class="text-xs errorText">
              {{ error }}
            </span>
                    </div>

                    <div v-if="validationMessages.warnings.shift_end.length" class="flex flex-col">
            <span v-for="(warning, i) in validationMessages.warnings.shift_end" :key="'we'+i" class="text-xs text-orange-500">
              {{ warning }}
            </span>
                    </div>
                    <div v-if="validationMessages.errors.shift_end.length" class="flex flex-col">
            <span v-for="(error, i) in validationMessages.errors.shift_end" :key="'ee'+i" class="text-xs errorText">
              {{ error }}
            </span>
                    </div>
                </div>
            </div>
            <!-- Pause -->
            <div
                v-if="!editBreakTime && shift.break_minutes"
                class="text-xs mb-1 hover:bg-white cursor-pointer px-1.5 py-1 rounded-lg w-fit transition"
                @click="editBreakTime = true"
                :title="$t('Edit break')"
            >
                <span>{{ shift.break_formatted }}</span>

            </div>

            <div v-else-if="editBreakTime" class="relative pt-1">
                <button
                    class="absolute right-1.5 top-1.5 text-zinc-500 hover:text-red-600 transition"
                    @click="resetForm"
                    :aria-label="$t('Cancel')"
                >
                    <component :is="IconX" class="h-4 w-4" />
                </button>

                <BaseInput
                    type="number"
                    v-model="updateTimeForm.break_minutes"
                    :label="$t('Length of break in minutes*')"
                    :id="'break-time-' + this.shift.id"
                    required
                    is-small
                    @focusout="saveTimeChanges"
                    @change="validateShiftBreak"
                    :disabled="!canEditComponent"
                    classes="h-8 peer-placeholder-shown:top-[8px] text-xs -top-5"
                />

                <!-- Validierung: Pause -->
                <div v-if="hasBreakMessages" class="mt-2 space-y-1">
                    <div v-if="validationMessages.warnings.break_length.length" class="flex flex-col">
            <span v-for="(warning, i) in validationMessages.warnings.break_length" :key="'wb'+i" class="text-xs text-orange-500">
              {{ warning }}
            </span>
                    </div>
                    <div v-if="validationMessages.errors.break_length.length" class="flex flex-col">
            <span v-for="(error, i) in validationMessages.errors.break_length" :key="'eb'+i" class="text-xs errorText">
              {{ error }}
            </span>
                    </div>
                </div>
            </div>

            <!-- Notiz -->
            <ShiftNoteComponent :shift="shift" />

            <!-- Gebuchte Nutzer -->
            <div v-for="user in shift.users" :key="'u'+user.id">
                <ShiftBookedElementComponent
                    :user="user"
                    :type="0"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="computedShiftQualificationDropElements"
                    :crafts="crafts"
                    :can-edit-component="canEditComponent"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
            </div>

            <!-- Freelancer -->
            <div v-for="freelancer in shift.freelancer" :key="'f'+freelancer.id">
                <ShiftBookedElementComponent
                    :user="freelancer"
                    :type="1"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="computedShiftQualificationDropElements"
                    :crafts="crafts"
                    :can-edit-component="canEditComponent"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
            </div>

            <!-- Dienstleister -->
            <div v-for="serviceProvider in shift.service_provider" :key="'p'+serviceProvider.id">
                <ShiftBookedElementComponent
                    :user="serviceProvider"
                    :type="2"
                    :shift="shift"
                    :event="event"
                    :shift-qualifications="shiftQualifications"
                    :craft-id="shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :all-shift-qualification-drop-elements="computedShiftQualificationDropElements"
                    :crafts="crafts"
                    :can-edit-component="canEditComponent"
                    @dropFeedback="dropFeedback"
                    :craft-with-entities="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                />
            </div>

            <!-- Drop-Ziele für Qualifikationen -->
            <div v-for="dropElement in computedShiftQualificationDropElements" :key="`de-${dropElement.shift_qualification_id}`">
                <ShiftsQualificationsDropElement
                    v-for="count in dropElement.requiredDropElementsCount"
                    :key="`de-${dropElement.shift_qualification_id}-${count}`"
                    :craft-id="shift.craft.id"
                    :shift-id="shift.id"
                    :shift-user-ids="shiftUserIds"
                    :event-is-series="event.is_series"
                    :shift-qualification="getShiftQualificationById(dropElement.shift_qualification_id)"
                    :all-shift-qualification-drop-elements="computedShiftQualificationDropElements"
                    :shift-crafts-with-users="getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable"
                    :crafts="crafts"
                    :can-edit-component="canEditComponent"
                    @dropFeedback="dropFeedback"
                />
            </div>

            <!-- Add Qualification -->
            <div class="my-3 mx-0.5" v-if="canEditComponent">
                <component
                    :is="IconCirclePlus"
                    @click="showAddShiftQualificationModal = true"
                    class="h-5 w-5 xsLight cursor-pointer hover:text-artwork-buttons-hover transition-colors"
                    stroke-width="1.5"
                />
            </div>
        </div>
    </div>

    <!-- Modals -->
    <AddShiftModal
        v-if="openEditShiftModal"
        :shift="shift"
        :event="event"
        :crafts="crafts"
        @closed="closeAddShiftModal()"
        :currentUserCrafts="currentUserCrafts"
        :edit="true"
        :shift-qualifications="shiftQualifications"
        :shift-time-presets="shiftTimePresets"
        :shift-plan-modal="false"
    />

    <AddShiftQualificationToShiftModel
        v-if="showAddShiftQualificationModal"
        @close="showAddShiftQualificationModal = false"
        :shift="shift"
        :shift-qualifications="shiftQualifications"
    />
</template>

<script setup>
import { ref, computed, onMounted, nextTick, getCurrentInstance } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import {can, is} from 'laravel-permission-to-vuejs'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import ShiftNoteComponent from '@/Layouts/Components/ShiftNoteComponent.vue'
import ShiftBookedElementComponent from '@/Pages/Projects/Components/ShiftBookedElementComponent.vue'
import ShiftsQualificationsDropElement from '@/Pages/Projects/Components/ShiftsQualificationsDropElement.vue'
import AddShiftModal from '@/Pages/Projects/Components/AddShiftModal.vue'
import AddShiftQualificationToShiftModel from '@/Pages/Projects/Components/AddShiftQualificationToShiftModel.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import {IconCirclePlus, IconCircleX, IconEdit, IconTrash, IconX, IconExclamationCircle} from "@tabler/icons-vue";

defineOptions({ name: 'SingleShift' })

const props = defineProps({
    shift: { type: Object, required: true },
    crafts: { type: Array, required: true },
    event: { type: Object, required: true },
    currentUserCrafts: { type: Array, required: true },
    shiftQualifications: { type: Array, required: true },
    shiftTimePresets: { type: Array, required: true },
    canEditComponent: { type: Boolean, default: false }
})
const emit = defineEmits(['dropFeedback', 'wantsFreshPlacements'])

const page = usePage()
const { proxy } = getCurrentInstance()

// --- UI State ---
const openEditShiftModal = ref(false)
const showAddShiftQualificationModal = ref(false)
const editTimes = ref(false)
const editBreakTime = ref(false)
const highlight = ref(null)

// --- Validation state ---
const validationMessages = ref({
    warnings: { shift_start: [], shift_end: [], break_length: [], craft: [] },
    errors: { shift_start: [], shift_end: [], break_length: [], craft: [] }
})

// --- Form ---
const updateTimeForm = useForm({
    start: props.shift.start,
    end: props.shift.end,
    break_minutes: props.shift.break_minutes,
    closeEditAfterSave: false
})

// --- Computed helpers ---
const isAdmin = computed(() => {
    return is('artwork admin')
})

const getUsersInCraftOfShiftAndUsersFromCraftsWhereUniversalApplicable = computed(() =>
    props.crafts.filter(c => c.id === props.shift.craft.id || c.universally_applicable)
)

const maxCount = computed(() => {
    let total = 0
    props.shift.shifts_qualifications.forEach(sq => (total += sq.value))
    return total
})
const usedCount = computed(() => props.shift.users.length + props.shift.freelancer.length + props.shift.service_provider.length)
const isFull = computed(() => maxCount.value > 0 && usedCount.value >= maxCount.value)

const anyVacation = computed(() =>
    props.shift.users.some(u => u?.formatted_vacation_days?.includes(props.shift.event_start_day))
)
const anyoneHasVacation = anyVacation // alias for template

const headerBgClass = computed(() => {
    if (props.shift.infringement || anyoneHasVacation.value) return 'bg-red-500'
    return isFull.value ? 'bg-green-500' : 'bg-zinc-500'
})

const progressWidth = computed(() => {
    if (maxCount.value <= 0) return '0%'
    const pct = Math.min(100, Math.round((usedCount.value / maxCount.value) * 100))
    return `${pct}%`
})

const computedShiftQualificationDropElements = computed(() => {
    const res = []
    props.shift.shifts_qualifications.forEach(sq => {
        let required = sq.value
        const id = sq.shift_qualification_id
        required -= props.shift.users.filter(u => u.pivot.shift_qualification_id === id).length
        required -= props.shift.freelancer.filter(f => f.pivot.shift_qualification_id === id).length
        required -= props.shift.service_provider.filter(p => p.pivot.shift_qualification_id === id).length
        if (required > 0) res.push({ shift_qualification_id: id, requiredDropElementsCount: required })
    })
    return res
})

const shiftUserIds = computed(() => ({
    userIds: props.shift.users.map(u => u.id),
    freelancerIds: props.shift.freelancer?.map(f => f.id) ?? [],
    providerIds: props.shift.service_provider?.map(p => p.id) ?? []
}))

const hasTimeMessages = computed(() =>
    validationMessages.value.warnings.shift_start.length ||
    validationMessages.value.errors.shift_start.length ||
    validationMessages.value.warnings.shift_end.length ||
    validationMessages.value.errors.shift_end.length
)

const hasBreakMessages = computed(() =>
    validationMessages.value.warnings.break_length.length || validationMessages.value.errors.break_length.length
)

// --- Lifecycle ---
onMounted(() => {
    const urlShiftId = parseInt(page?.props?.urlParameters?.shiftId)
    if (urlShiftId === props.shift.id) {
        highlight.value = 'outline outline-2 outline-orange-300/60'
        setTimeout(() => (highlight.value = null), 5000)
    }
})

// --- Methods ---
function checkFocus(event) {
    nextTick(() => {
        const startField = document.getElementById(`shift-start${props.shift.id}`)
        const endField = document.getElementById(`shift-end${props.shift.id}`)
        if (event?.relatedTarget === startField || event?.relatedTarget === endField) return
        saveTimeChanges()
    })
}

function saveTimeChanges() {
    if (validateShiftBreak() || validateShiftDates()) return
    if (updateTimeForm.isDirty) {
        updateTimeForm.patch(route('event.shift.update.updateTime', { shift: props.shift.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                editTimes.value = false
                editBreakTime.value = false
                wantsFreshPlacements()
            }
        })
    }
}

function resetForm() {
    editTimes.value = false
    editBreakTime.value = false
    updateTimeForm.reset()
}

function validateShiftDates() {
    validationMessages.value.warnings.shift_start = []
    validationMessages.value.warnings.shift_end = []
    validationMessages.value.errors.shift_start = []
    validationMessages.value.errors.shift_end = []

    const eventStart = new Date(props.event.start_time)
    const eventEnd = new Date(props.event.end_time)
    const shiftStart = new Date(`${props.shift.start_date}T${updateTimeForm.start}`)
    const shiftEnd = new Date(`${props.shift.end_date}T${updateTimeForm.end}`)

    let hasErrors = false

    if (shiftStart < eventStart) {
        validationMessages.value.warnings.shift_start.push(
            window?.$t ? window.$t('The shift starts before the event starts!') : 'The shift starts before the event starts!'
        )
    }
    if (shiftStart > eventEnd) {
        validationMessages.value.warnings.shift_start.push(
            window?.$t ? window.$t('The shift starts after the event ends!') : 'The shift starts after the event ends!'
        )
    }
    if ((shiftEnd - shiftStart) / 60000 > 600) {
        validationMessages.value.warnings.shift_start.push(
            window?.$t ? window.$t('The shift is over 10 hours long!') : 'The shift is over 10 hours long!'
        )
    }
    if (shiftStart > shiftEnd) {
        validationMessages.value.warnings.shift_end.push(
            window?.$t ? window.$t('The shift ends before it starts!') : 'The shift ends before it starts!'
        )
        validationMessages.value.warnings.shift_end.push(
            window?.$t ? window.$t('The end time must be after the start time.') : 'The end time must be after the start time.'
        )
    }
    if (shiftEnd < eventStart) {
        validationMessages.value.warnings.shift_end.push(
            window?.$t ? window.$t('The shift ends before the event starts!') : 'The shift ends before the event starts!'
        )
    }

    return hasErrors
}

function validateShiftBreak() {
    validationMessages.value.warnings.break_length = []
    validationMessages.value.errors.break_length = []

    const shiftStart = new Date(`${props.shift.start_date}T${updateTimeForm.start}`)
    const shiftEnd = new Date(`${props.shift.end_date}T${updateTimeForm.end}`)

    let hasErrors = false

    if ((shiftEnd - shiftStart) / 60000 > 360 && Number(updateTimeForm.break_minutes) < 30) {
        validationMessages.value.warnings.break_length.push(
            window?.$t ? window.$t('The break is shorter than required by law!') : 'The break is shorter than required by law!'
        )
    }
    if (updateTimeForm.break_minutes === null || updateTimeForm.break_minutes === '') {
        validationMessages.value.errors.break_length.push(
            window?.$t ? window.$t('Please enter a break time.') : 'Please enter a break time.'
        )
        hasErrors = true
    }

    return hasErrors
}

function openEditInLineTimes() {
    // ODER-Logik: darf bearbeiten, wenn can ODER admin
    const canPlan = can('can plan shifts');
    const isAdmin = is('artwork admin');

    if (!(canPlan || isAdmin)) {
        console.log('No permission to edit shift times');
        return;
    }

    editTimes.value = true;
    nextTick(() => {
        const container = document.getElementById(`container-${props.shift.id}`);
        // Listener nur einmal anhängen, verhindert Leaks
        container?.addEventListener('focusout', checkFocus, { once: true });
    });
}

function wantsFreshPlacements() {
    emit('wantsFreshPlacements')
}

function closeAddShiftModal() {
    wantsFreshPlacements()
    openEditShiftModal.value = false
}

function getShiftQualificationById(id) {
    return props.shiftQualifications.find(sq => sq.id === id)
}

function dropFeedback(feedback) {
    emit('dropFeedback', feedback)
}

function clearShiftUsers(shift) {
    if (shift.users.length || shift.freelancer.length || shift.service_provider.length) {
        router.delete(route('shift.removeAllUsers', { shift: shift.id }), {
            data: { chooseData: { onlyThisDay: false } },
            preserveScroll: true
        })
    }
}

function deleteShift(shift_id) {
    router.delete(route('shifts.destroy', { shift: shift_id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => wantsFreshPlacements()
    })
}

function editShift() {
    openEditShiftModal.value = true
}
</script>

<style scoped>
.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
