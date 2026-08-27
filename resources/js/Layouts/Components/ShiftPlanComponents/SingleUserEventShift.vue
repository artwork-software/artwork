<template>
    <div
        class="rounded-xl border bg-white shadow-sm overflow-hidden transition hover:shadow-md"
        :class="ownConfirmationInfo?.accepted
            ? 'border-success ring-1 ring-success'
            : (ownConfirmationInfo ? 'border-danger ring-1 ring-danger' : 'border-border-subtle')"
    >
        <!-- Farb-Akzent / Headerblock: Titel nutzt die volle Breite,
             die Icons sitzen darunter rechtsbündig am Blockende -->
        <div
            class="px-3 py-2"
            :style="{
        backgroundColor: eventType ? backgroundColorWithOpacity(eventType?.hex_code, percentage) : (resolvedCraft?.color ? `${resolvedCraft.color}40` : '#e8e8e8'),
        color: eventType ? getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType?.hex_code, percentage)) : getTextColorBasedOnBackground(resolvedCraft?.color ? `${resolvedCraft.color}40` : '#e8e8e8')
      }"
        >
            <a
                v-if="project && eventType && canAccessProject"
                :href="projectShiftTabHref"
                class="block break-words text-sm font-semibold hover:opacity-90 transition"
            >
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </a>
            <span v-else-if="project && eventType" class="block break-words text-sm font-semibold">
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </span>

            <span v-else class="block break-words text-sm font-semibold">
                {{ getCraftAndFunctionLabel() }} - <span>
                    <a
                        v-if="canAccessProject"
                        :href="projectShiftTabHref"
                        class="break-words text-sm font-semibold hover:opacity-90 transition"
                    >{{ project?.name }}</a>
                    <span v-else>{{ project?.name }}</span>
                </span>
            </span>

            <div v-if="hasHeaderIcons" class="mt-1 flex items-center justify-end gap-2">
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
            <div class="flex flex-col border-b border-border-subtle pb-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-text">
                      {{ getDisplayTime() }}
                      <span class="text-text-subtle">·</span>
                      <span class="text-text-muted">{{ shift?.room?.name ?? shift?.event?.room?.name }}</span>
                    </span>
                </div>
                <div v-if="hasIndivTime" class="text-[10px] text-text-subtle mt-0.5">
                    {{ $t('individual user time, original shift time: {start} - {end}', { start: shift.start, end: shift.end }) }}
                </div>
            </div>

            <!-- Zu-/Absage der Zuweisung (nur festgeschriebene Schichten); Status ist
                 auch für Planer:innen sichtbar, die einen fremden Plan ansehen -->
            <div v-if="showOwnConfirmationControls || ownConfirmationInfo" class="border-b border-border-subtle pb-2">
                <!-- flex-wrap + whitespace-nowrap: auf schmalen Karten rutscht der
                     Aktionslink in eine eigene Zeile statt abgeschnitten zu werden -->
                <div v-if="ownConfirmationInfo" class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-semibold"
                        :class="ownConfirmationInfo.accepted
                            ? 'bg-success-surface text-success border border-success-border'
                            : 'bg-danger-surface text-danger border border-danger-border'"
                    >
                        <PropertyIcon :name="ownConfirmationInfo.accepted ? 'IconCheck' : 'IconX'" class="h-3.5 w-3.5" stroke-width="2.5" />
                        {{ ownConfirmationInfo.accepted
                            ? $t('Accepted on {date}', { date: ownConfirmationInfo.date ?? '–' })
                            : $t('Declined on {date}', { date: ownConfirmationInfo.date ?? '–' }) }}
                    </span>
                    <button
                        v-if="showOwnConfirmationControls"
                        type="button"
                        class="ml-auto whitespace-nowrap text-xs text-text-subtle underline hover:text-text transition"
                        @click="responseModalMode = ownConfirmationInfo.accepted ? 'decline' : 'accept'"
                    >
                        {{ ownConfirmationInfo.accepted ? $t('Decline shift') : $t('Accept shift') }}
                    </button>
                </div>
                <div v-else class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
                        {{ $t('Reply to planner') }}
                    </span>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-lg border border-success-border bg-success-surface text-success hover:opacity-80 transition"
                            :aria-label="$t('Accept shift')"
                            v-tooltip.bottom="{ value: $t('Accept shift'), class: 'aw-tooltip' }"
                            @click="responseModalMode = 'accept'"
                        >
                            <PropertyIcon name="IconCheck" class="h-4 w-4" stroke-width="2.5" />
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center h-7 w-7 rounded-lg border border-danger-border bg-danger-surface text-danger hover:opacity-80 transition"
                            :aria-label="$t('Decline shift')"
                            v-tooltip.bottom="{ value: $t('Decline shift'), class: 'aw-tooltip' }"
                            @click="responseModalMode = 'decline'"
                        >
                            <PropertyIcon name="IconX" class="h-4 w-4" stroke-width="2.5" />
                        </button>
                    </div>
                </div>
                <div v-if="ownConfirmationInfo?.comment" class="mt-1 text-[11px] text-text-subtle">
                    {{ $t('Comment') }}: {{ ownConfirmationInfo.comment }}
                </div>
            </div>

            <!-- Kolleg*innen -->
            <div class="border-b border-border-subtle pb-2">
                <template v-if="hasColleaguesOnShift(shift)">
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle mb-1">
                        {{ $t('Colleagues') }}
                    </div>
                    <ul class="flex flex-wrap gap-1.5">
                        <template v-for="worker in (shift.workers || [])" :key="worker.type + '-' + worker.id">
                            <li
                                v-if="worker.type !== type || worker.id !== userToEditId"
                                class="inline-flex items-center gap-1 rounded-full bg-surface-sunken px-2 py-1 text-xs text-text"
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

                <span v-else class="text-sm font-medium text-text-muted">
          {{ $t('No colleagues') }}
        </span>
            </div>

            <!-- Schichtbeschreibung (nur anzeigen, nicht bearbeiten) -->
            <div v-if="shift.description" class="border-b border-border-subtle pb-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle mb-1">
                    {{ $t('Description') }}
                </div>
                <p class="text-sm text-text whitespace-pre-wrap">
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

    <!-- Zu-/Absage mit optionalem Kommentar -->
    <ShiftConfirmationResponseModal
        v-if="responseModalMode"
        :mode="responseModalMode"
        @close="responseModalMode = null"
        @submit="submitResponse"
    />

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
import ShiftConfirmationResponseModal from '@/Layouts/Components/ShiftPlanComponents/ShiftConfirmationResponseModal.vue'
import { useShiftWorkerConfirmation } from '@/Composeables/useShiftWorkerConfirmation.js'

const { backgroundColorWithOpacity, getHighContrastPercent, getTextColorBasedOnBackground } = useColorHelper()
const percentage = computed(() => getHighContrastPercent(
    usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings
))
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

// Icon-Zeile im Header nur rendern, wenn mindestens ein Icon sichtbar ist
// (Bedingungen spiegeln die v-ifs der einzelnen Icons/Buttons)
const hasHeaderIcons = computed(() =>
    props.shift.is_committed
    || props.shift.in_workflow
    || !!props.project
    || (props.userToEditId === usePage().props.auth.user.id && props.type === 'user')
)
const hasIndivTime = ref(false)
// 'accept' | 'decline' | null — steuert das Antwort-Modal (Kommentar optional)
const responseModalMode = ref(null)

const { isEnabled: confirmationEnabled, respond: respondToShift, getConfirmationInfo } = useShiftWorkerConfirmation()

const ownWorker = computed(() => (props.shift.workers || []).find(
    w => w.type === props.type && w.id === props.userToEditId
) ?? null)

// Buttons nur im EIGENEN Einsatzplan (die Komponente rendert auch fremde Pläne
// für Planer:innen) und nur an festgeschriebenen Schichten.
const showOwnConfirmationControls = computed(() =>
    confirmationEnabled()
    && props.shift.is_committed
    && props.type === 'user'
    && props.userToEditId === usePage().props.auth.user.id
    && !!ownWorker.value?.pivot?.id
)

// Auch read-only sichtbar (Planer:in schaut fremden Plan an); getConfirmationInfo
// prüft bereits Feature-Setting + vorhandenen Status.
const ownConfirmationInfo = computed(() =>
    props.shift.is_committed ? getConfirmationInfo(ownWorker.value) : null
)

const submitResponse = (comment) => {
    const status = responseModalMode.value === 'accept' ? 'accepted' : 'declined'
    responseModalMode.value = null
    respondToShift(ownWorker.value.pivot.id, status, comment)
}

const canAccessProject = computed(() => {
    if (hasAdminRole()) return true
    if (can('view projects')) return true
    // Backend-Flag (withExists auf project_user): Payloads schicken project.users
    // aus Gewichtsgründen nicht mit — ohne das Flag wäre der Link für
    // Teammitglieder ohne "view projects"-Permission nie sichtbar.
    if (props.project?.auth_user_in_team) return true
    // Check if user is part of project team
    const currentUserId = usePage().props.auth.user.id
    if (props.project?.users?.some(u => u.id === currentUserId)) return true
    if (props.project?.managers?.some(m => m.id === currentUserId)) return true
    return false
})

// Datum der Schicht (YYYY-MM-DD) für den Tagesanker im Schichten-Tab; die
// Payload-Formen unterscheiden sich je nach Quelle (Einsatzplan vs. Dashboard).
const shiftDayYMD = computed(() => {
    const shift = props.shift
    if (shift?._day) return shift._day
    if (shift?.formatted_dates?.frontend_start) return shift.formatted_dates.frontend_start
    if (typeof shift?.start_date === 'string') return shift.start_date.slice(0, 10)
    if (/^\d{2}\.\d{2}\.\d{4}$/.test(shift?.start_of_shift ?? '')) {
        const [d, m, y] = shift.start_of_shift.split('.')
        return `${y}-${m}-${d}`
    }
    return null
})

const projectShiftTabHref = computed(() => {
    if (!props.project?.id) return '#'
    return route('projects.tab', {
        project: props.project.id,
        projectTab: props.firstProjectShiftTabId,
        // Query-Param: ShiftPlanDailyView scrollt im Projekt-Schichten-Tab zum Tag
        ...(shiftDayYMD.value ? { goToDate: shiftDayYMD.value } : {}),
    })
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
