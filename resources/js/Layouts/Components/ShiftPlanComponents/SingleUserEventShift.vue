<template>
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden transition hover:shadow-md">
        <!-- Farb-Akzent / Headerzeile -->
        <div
            class="flex items-center justify-between gap-2 px-3 py-2"
            :style="{
        backgroundColor: eventType ? backgroundColorWithOpacity(eventType?.hex_code, percentage) : '#e8e8e8',
        color: eventType ? getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType?.hex_code, percentage)) : '#000000'
      }"
        >
            <a
                v-if="project && eventType"
                :href="project?.id ? route('projects.tab', { project: project.id, projectTab: firstProjectShiftTabId }) : '#'"
                class="inline-flex items-center max-w-[70%] truncate text-sm font-semibold hover:opacity-90 transition"
            >
                {{ eventType?.abbreviation }}: {{ project?.name }}
            </a>

            <span v-else class="truncate text-sm font-semibold">
        {{ $t('Universal Shift') }}
      </span>

            <div class="ml-auto flex items-center gap-2">
                <IconLock v-if="shift.is_committed" stroke-width="1.5" class="h-5 w-5 opacity-90" />
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
                    <IconClockEdit class="h-5 w-5" stroke-width="1.5" />
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="px-3 py-3 space-y-3">
            <!-- Zeit & Raum -->
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-2">
        <span class="text-sm font-medium text-zinc-900">
          {{ shift.start }} – {{ shift.end }}
          <span class="text-zinc-500">·</span>
          <span class="text-zinc-700">{{ shift?.room?.name ?? shift?.event?.room?.name }}</span>
        </span>
            </div>

            <!-- Kolleg*innen -->
            <div class="border-b border-zinc-200 pb-2">
                <template v-if="hasColleaguesOnShift(shift)">
                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 mb-1">
                        {{ $t('Colleagues') }}
                    </div>
                    <ul class="flex flex-wrap gap-1.5">
                        <!-- Users -->
                        <template v-for="user in shift.users" :key="'u-' + user.id">
                            <li
                                v-if="(type === 'user' && user.id !== userToEditId) || type !== 'user'"
                                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-800"
                            >
                                <UserPopoverTooltip
                                    :user="user"
                                    height="5"
                                    width="5"
                                    :use-slot-instead-of-icon="true"
                                    :dont-translate-popover-position="true"
                                >
                                    {{ user.first_name }}, {{ user.last_name }}
                                </UserPopoverTooltip>
                            </li>
                        </template>

                        <!-- Freelancer -->
                        <template v-for="freelancer in shift.freelancer" :key="'f-' + freelancer.id">
                            <li
                                v-if="(type === 'freelancer' && freelancer.id !== userToEditId) || type !== 'freelancer'"
                                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-800"
                            >
                                <UserPopoverTooltip
                                    :user="freelancer"
                                    height="5"
                                    width="5"
                                    :use-slot-instead-of-icon="true"
                                    :dont-translate-popover-position="true"
                                >
                                    {{ freelancer.first_name }}, {{ freelancer.last_name }}
                                </UserPopoverTooltip>
                            </li>
                        </template>

                        <!-- Dienstleister -->
                        <template v-for="sp in shift.service_provider" :key="'sp-' + sp.id">
                            <li
                                v-if="(type === 'service_provider' && sp.id !== userToEditId) || type !== 'service_provider'"
                                class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-800"
                            >
                                <UserPopoverTooltip
                                    :user="sp"
                                    height="5"
                                    width="5"
                                    :use-slot-instead-of-icon="true"
                                    :dont-translate-popover-position="true"
                                >
                                    {{ sp.provider_name }}
                                </UserPopoverTooltip>
                            </li>
                        </template>
                    </ul>
                </template>

                <span v-else class="text-sm font-medium text-zinc-600">
          {{ $t('No colleagues') }}
        </span>
            </div>

            <!-- Notizen -->
            <div class="w-full text-xs">
                <ShiftNoteComponent :shift="shift" />
            </div>
        </div>
    </div>

    <!-- Anfrage Arbeitszeitänderung -->
    <RequestWorkTimeChangeModal
        v-if="showRequestWorkTimeChangeModal"
        :user="shift.users.find(u => u.id === userToEditId)"
        :shift="shift"
        @close="showRequestWorkTimeChangeModal = false"
    />
</template>

<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { IconCalendarMonth, IconClockEdit, IconLock } from '@tabler/icons-vue'
import ShiftNoteComponent from '@/Layouts/Components/ShiftNoteComponent.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import RequestWorkTimeChangeModal from '@/Pages/Shifts/Components/RequestWorkTimeChangeModal.vue'
import { useColorHelper } from '@/Composeables/UseColorHelper.js'

const percentage = usePage().props.high_contrast_percent
const { backgroundColorWithOpacity, getTextColorBasedOnBackground } = useColorHelper()

const props = defineProps({
    type: { type: String, required: true, default: null },
    event: { type: [Object, null], required: true, default: [] },
    shift: { type: Object, required: true },
    project: { type: [Object, null], required: false, default: [] },
    eventType: { type: [Object, null], required: true, default: [] },
    firstProjectShiftTabId: { type: Number, required: true },
    userToEditId: { type: Number, required: true }
})

const showRequestWorkTimeChangeModal = ref(false)

const hasColleaguesOnShift = (shift) => {
    // Eigene Schicht ist immer in users enthalten – Kollegen = weitere Personen
    return (shift.users?.length > 1) || (shift.freelancer?.length > 0) || (shift.service_provider?.length > 0)
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
