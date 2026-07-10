<template>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden transition hover:shadow-md">
        <!-- Farb-Akzent / Headerzeile -->
        <div
            class="flex items-start justify-between gap-2 px-3 py-2"
            :style="{
        backgroundColor: eventType ? backgroundColorWithOpacity(eventType?.hex_code, percentage) : (resolvedCraft?.color ? `${resolvedCraft.color}40` : '#e8e8e8'),
        color: eventType ? getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType?.hex_code, percentage)) : getTextColorBasedOnBackground(resolvedCraft?.color ? `${resolvedCraft.color}40` : '#e8e8e8')
      }"
        >
            <a
                v-if="project && eventType && canAccessProject"
                :href="project?.id ? route('projects.tab', { project: project.id, projectTab: firstProjectShiftTabId }) : '#'"
                class="min-w-0 break-words text-sm font-semibold hover:opacity-90 transition"
            >
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </a>
            <span v-else-if="project && eventType" class="min-w-0 break-words text-sm font-semibold">
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </span>

            <span v-else class="min-w-0 break-words text-sm font-semibold">
                {{ getCraftAndFunctionLabel() }} - <span>
                    <a
                        v-if="canAccessProject"
                        :href="project?.id ? route('projects.tab', { project: project.id, projectTab: firstProjectShiftTabId }) : '#'"
                        class="break-words text-sm font-semibold hover:opacity-90 transition"
                    >{{ project?.name }}</a>
                    <span v-else>{{ project?.name }}</span>
                </span>
            </span>

            <div class="ml-auto flex items-center gap-2 shrink-0">
                <PropertyIcon
                    name="IconLock"
                    v-if="shift.is_committed"
                    stroke-width="1.5"
                    class="h-5 w-5 opacity-90"
                    v-tooltip.bottom="{ value: $t('Committed'), class: 'aw-tooltip' }"
                />
                <PropertyIcon
                    name="IconGitPullRequest"
                    v-else-if="shift.in_workflow"
                    stroke-width="1.5"
                    class="h-5 w-5 opacity-90"
                    v-tooltip.bottom="{ value: $t('Requested'), class: 'aw-tooltip' }"
                />
                <button
                    v-if="project"
                    type="button"
                    class="rounded-md/50 p-1 hover:bg-white/10 rounded-lg transition"
                    @click="toggleProjectTimePeriodAndRedirect"
                    :aria-label="$t('Open project time period')"
                >
                    <IconCalendarMonth class="h-5 w-5" />
                </button>
                <button
                    v-if="userToEditId === usePage().props.auth.user.id && type === 'user'"
                    type="button"
                    class="rounded-md/50 p-1 hover:bg-white/10 rounded-lg transition"
                    @click="showRequestWorkTimeChangeModal = true"
                    :aria-label="$t('Request work time change')"
                >
                    <PropertyIcon name="IconClockEdit" class="h-5 w-5" stroke-width="1.5" />
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="px-3 py-3 space-y-3">
            <!-- Zeit & Raum -->
            <div class="flex flex-col border-b border-zinc-200 pb-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-zinc-900">
                      {{ getDisplayTime() }}
                      <span class="text-zinc-500">·</span>
                      <span class="text-zinc-700">{{ shift?.room?.name ?? shift?.event?.room?.name }}</span>
                    </span>
                </div>
                <div v-if="hasIndivTime" class="text-[10px] text-zinc-500 mt-0.5">
                    {{ $t('individual user time, original shift time: {start} - {end}', { start: shift.start, end: shift.end }) }}
                </div>
            </div>

            <!-- Kolleg*innen -->
            <div class="border-b border-zinc-200 pb-2">
                <template v-if="hasColleaguesOnShift(shift)">
                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 mb-1">
                        {{ $t('Colleagues') }}
                    </div>
                    <ul class="flex flex-wrap gap-1.5">
                        <template v-for="worker in (shift.workers || [])" :key="worker.type + '-' + worker.id">
                            <li
                                v-if="worker.type !== type || worker.id !== userToEditId"
                                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-800"
                            >
                                <UserPopoverTooltip
                                    :user="worker"
                                    height="5"
                                    width="5"
                                    :use-slot-instead-of-icon="true"
                                    :dont-translate-popover-position="true"
                                >
                                    <template v-if="worker.type === 'service_provider'">{{ worker.provider_name }}</template>
                                    <template v-else>{{ worker.first_name }}, {{ worker.last_name }}</template>
                                </UserPopoverTooltip>
                            </li>
                        </template>
                    </ul>
                </template>

                <span v-else class="text-sm font-medium text-zinc-600">
          {{ $t('No colleagues') }}
        </span>
            </div>

            <!-- Schichtbeschreibung (nur anzeigen, nicht bearbeiten) -->
            <div v-if="shift.description" class="border-b border-zinc-200 pb-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 mb-1">
                    {{ $t('Description') }}
                </div>
                <p class="text-sm text-zinc-800 whitespace-pre-wrap">
                    {{ shift.description }}
                </p>
            </div>

            <!-- Notizen -->
            <div class="w-full text-xs">
                <ShiftNoteComponent
                    :shift="shift"
                    mode="pivot"
                    :user-to-edit-id="userToEditId"
                    :entity-type="type"
                />
            </div>
        </div>
    </div>

    <!-- Anfrage Arbeitszeitänderung -->
    <RequestWorkTimeChangeModal
        v-if="showRequestWorkTimeChangeModal"
        :user="(shift.workers || []).find(w => w.type === 'user' && w.id === userToEditId)"
        :shift="{
            ...shift,
            start: getDisplayTime().split(' – ')[0],
            end: getDisplayTime().split(' – ')[1],
            start_of_shift: shift.start_of_shift ?? (shift._day ? formatDateDMYForModal(shift._day) : null)
        }"
        @close="showRequestWorkTimeChangeModal = false"
    />
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { IconCalendarMonth, IconClockEdit, IconLock } from '@tabler/icons-vue'
import ShiftNoteComponent from '@/Layouts/Components/ShiftNoteComponent.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import RequestWorkTimeChangeModal from '@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue'
import { useColorHelper } from '@/Composeables/UseColorHelper.js'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { usePermission } from "@/Composeables/Permission.js";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";

const percentage = usePage().props.high_contrast_percent
const { backgroundColorWithOpacity, getTextColorBasedOnBackground } = useColorHelper()
const { can, hasAdminRole } = usePermission(usePage().props)

const props = defineProps({
    type: { type: String, required: true, default: null },
    shift: { type: Object, required: true },
    project: { type: [Object, null], required: false, default: null },
    eventType: { type: [Object, null], required: false, default: null },
    firstProjectShiftTabId: { type: Number, required: true },
    userToEditId: { type: Number, required: true }
})

const { resolveCraft } = useShiftPlanLookups();
const resolvedCraft = computed(() => props.shift?.craft ?? resolveCraft(props.shift?.craftId) ?? {});

const showRequestWorkTimeChangeModal = ref(false)
const hasIndivTime = ref(false)

const canAccessProject = computed(() => {
    if (hasAdminRole()) return true
    if (can('view projects')) return true
    // Check if user is part of project team
    const currentUserId = usePage().props.auth.user.id
    if (props.project?.users?.some(u => u.id === currentUserId)) return true
    if (props.project?.managers?.some(m => m.id === currentUserId)) return true
    return false
})

const formatDateDMYForModal = (dateStr) => {
    if (!dateStr) return null
    // dateStr ist YYYY-MM-DD
    const [y, m, d] = dateStr.split('-')
    if (!y || !m || !d) return dateStr
    return `${d}.${m}.${y}`
}

const getDisplayTime = () => {
    let startTime = props.shift.start
    let endTime = props.shift.end

    const workers = props.shift.workers || []
    let currentUser = workers.find(w => w.type === props.type && w.id === props.userToEditId) || null

    if (currentUser?.pivot) {
        let pivotStart = null
        let pivotEnd = null

        if (currentUser.pivot.start_time) {
            pivotStart = currentUser.pivot.start_time.slice(0, 5)
        }
        if (currentUser.pivot.end_time) {
            pivotEnd = currentUser.pivot.end_time.slice(0, 5)
        }

        if (pivotStart && pivotStart !== props.shift.start.slice(0, 5)) {
            startTime = pivotStart
            hasIndivTime.value = true
        }
        if (pivotEnd && pivotEnd !== props.shift.end.slice(0, 5)) {
            endTime = pivotEnd
            hasIndivTime.value = true
        }
    }

    return `${startTime} – ${endTime}`
}

const hasColleaguesOnShift = (shift) => {
    const workers = shift.workers || []
    return workers.length > 1 || workers.some(w => w.type !== props.type || w.id !== props.userToEditId)
}

const getCraftAndFunctionLabel = () => {
    // Try to get craft and function from the current user's pivot data
    let craftName = null
    let functionName = null

    // Abkürzung des Gewerks bevorzugen, damit der Projektname im Header Platz hat
    if (resolvedCraft.value?.abbreviation || resolvedCraft.value?.name) {
        craftName = resolvedCraft.value.abbreviation || resolvedCraft.value.name
    }

    // Find the current worker in the shift to get their function
    const currentWorker = (props.shift.workers || []).find(
        w => w.type === props.type && w.id === props.userToEditId
    )
    if (currentWorker?.pivot?.short_description) {
        functionName = currentWorker.pivot.short_description
    }

    // Build the label
    if (craftName && functionName) {
        return `${craftName} - ${functionName}`
    } else if (craftName) {
        return craftName
    } else if (functionName) {
        return functionName
    } else {
        // Fallback to Universal Shift if no craft or function is found
        return usePage().props?.translations?.universal_shift || 'Universal Shift'
    }
}

const toggleProjectTimePeriodAndRedirect = () => {
    if (props.project?.id) {
        router.patch(
            route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
            {
                use_project_time_period: true,
                project_id: props.project.id
            }
        )
    }
}
</script>
