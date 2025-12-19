<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="modalDescription"
        modal-size="max-w-4xl"
        @close="closeModal"
    >
        <div class="space-y-4">

            <!-- Top Meta + Tip -->
            <div class="flex flex-col gap-2">
                <!-- Created by -->
                <div
                    v-if="(isRoomAdmin || hasAdminRole()) && event?.id"
                    class="flex items-center gap-2 text-[12px] text-zinc-500"
                >
                    {{ $t('Created by') }}
                    <UserPopoverTooltip
                        :user="event?.created_by"
                        :id="event?.created_by?.id ?? 'deletedUserTooltip'"
                        height="5"
                        width="5"
                    />
                </div>
            </div>
            <!-- EDIT MODE -->
            <div v-if="canEdit" class="space-y-4">

                <!-- Basics -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-indigo-400"></span>
                        <h3 class="ui-card-title">{{ $t('Basics') }}</h3>
                    </header>

                    <div class="ui-grid-2">
                        <ArtworkBaseListbox
                            v-model="selectedEventType"
                            :items="eventTypes"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event type"
                            :use-translations="false"
                            :show-color-indicator="true"
                            color-property="hex_code"
                        />
                        <ArtworkBaseListbox
                            v-if="statusModule"
                            v-model="selectedEventStatus"
                            :items="eventStatuses"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Event Status"
                            :use-translations="false"
                            :show-color-indicator="true"
                            color-property="color"
                        >
                        </ArtworkBaseListbox>
                        <div>
                            <BaseInput
                                v-model="eventName"
                                id="eventTitle"
                                :label="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                                class="ui-input"
                            />
                        </div>
                    </div>

                    <div class="ui-grid-2 mt-0.5">
                        <p class="ui-error" v-if="errorMsg('eventType')" v-html="errorMsg('eventType')" />
                        <span />
                        <p class="ui-error" v-if="selectedEventType?.individual_name && errorMsg('eventName')" v-html="errorMsg('eventName')" />
                    </div>
                </section>

                <!-- Date & Time -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-sky-400"></span>
                        <h3 class="ui-card-title">{{ $t('Date & Time') }}</h3>
                    </header>

                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="allDayEvent"
                            class="ui-checkbox"
                            @change="checkChanges"
                        />
                        <span class="text-[13px] text-zinc-700">{{ $t('Full day') }}</span>
                    </label>

                    <div class="ui-grid-2 mt-2">
                        <div class="flex gap-2 items-end">
                            <BaseInput type="date" id="startDate" v-model="startDate" :label="$t('Start')" @change="checkChanges" class="ui-input" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="startTime"
                                v-model="startTime"
                                :label="$t('Start time')"
                                @change="() => { endAutoFilled = !endTime; checkChanges() }"
                                class="ui-input"
                            />
                        </div>
                        <div class="flex gap-2 items-end">
                            <BaseInput type="date" id="endDate" v-model="endDate" :label="$t('End')" @change="() => { endAutoFilled = false; checkChanges() }" class="ui-input" />
                            <BaseInput
                                v-if="!allDayEvent"
                                type="time"
                                id="endTime"
                                v-model="endTime"
                                :label="$t('End time')"
                                @change="() => { endAutoFilled = false; checkChanges() }"
                                class="ui-input"
                            />
                        </div>
                    </div>

                    <!-- Quick duration buttons -->
                    <div class="mt-4">

                        <div class="flex flex-wrap items-center gap-2 mt-6" v-if="startDate && endDate && startTime">
                            <div class="ui-card-title">{{ $t('Duration Shortcuts:')}}</div>
                            <button
                                v-for="m in quickDurations"
                                :key="m"
                                type="button"
                                class="px-2.5 py-1.5 text-xs rounded-md border border-zinc-200 bg-white hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!startDate || allDayEvent || !startTime"
                                @click="applyQuickDuration(m)"
                                :aria-label="$t('Set end to {0} minutes after start', [m])"
                                :title="$t('Set end to {0} minutes after start', [m])"
                            >
                                {{ m }} {{ $t('min') }}
                            </button>

                            <!-- optional: Button mit Standarddauer aus PageProps -->
                            <button
                                v-if="defaultDurationMin > 0 && ![...quickDurations].includes(defaultDurationMin)"
                                type="button"
                                class="px-2.5 py-1.5 text-xs rounded-md border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!startDate || allDayEvent || !startTime"
                                @click="applyQuickDuration(defaultDurationMin)"
                                :aria-label="$t('Set end to {0} minutes after start', [defaultDurationMin])"
                                :title="$t('Set end to {0} minutes after start', [defaultDurationMin])"
                            >
                                Standard: {{ defaultDurationMin }} {{ $t('min') }}
                            </button>
                        </div>
                    </div>

                    <div class="ui-grid-2">
                        <p class="ui-error" v-if="errorMsg('start')" v-html="errorMsg('start')" />
                        <p class="ui-error" v-if="errorMsg('end')" v-html="errorMsg('end')" />
                    </div>
                    <p v-if="helpTextLengthRoom" class="ui-error mt-1">{{ helpTextLengthRoom }}</p>
                </section>

                <!-- Repeat -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-amber-400"></span>
                        <h3 class="ui-card-title">{{ $t('Repeat') }}</h3>
                    </header>

                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" v-model="series" class="ui-checkbox" />
                            <span class="text-[13px] text-zinc-700">{{ $t('Repeat event') }}</span>
                        </label>
                        <span class="ui-hint">{{ $t('Enable if this event should repeat automatically.') }}</span>
                    </div>

                    <div v-show="series" class="mt-2 ui-grid-2 items-end">
                        <ArtworkBaseListbox
                            v-model="selectedFrequency"
                            :items="frequencies"
                            by="id"
                            option-label="name"
                            option-key="id"
                            label="Frequency"
                            :use-translations="false"
                            :button-class="uiLbBtn"
                        />
                        <BaseInput id="seriesEndDate" type="date" v-model="seriesEndDate" :label="$t('End date Repeat event')" class="ui-input" />
                    </div>

                    <p v-if="event?.is_series" class="ui-hint mt-2">
                        {{ $t('Event is part of a repeat event') }} —
                        {{ $t('Cycle: {0} to {1}', [selectedFrequency?.name, convertDateFormat(seriesEndDate)]) }}
                    </p>
                </section>

                <!-- Room -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-rose-400"></span>
                        <h3 class="ui-card-title">{{ $t('Room') }}</h3>
                    </header>

                    <div class="mb-1 flex items-center justify-between">
                        <span class="ui-hint">{{ $t('Pick a room for this event.') }}</span>
                        <div v-if="selectedRoom && roomCollisionArray?.[selectedRoom.id] > 0" class="text-[12px] text-amber-600">
                            {{ $t('{0} potential conflicts detected', [roomCollisionArray[selectedRoom.id]]) }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-2 md:grid-cols-[1fr_auto]">
                        <RoomSearch v-if="!selectedRoom" :label="$t('Search for Rooms')" @room-selected="onRoomSelected" />
                        <div v-if="selectedRoom"
                             class="flex items-center gap-1.5 rounded-md border border-zinc-200 bg-zinc-50 px-2.5 py-4 xsDark"
                        >
                            <span class="truncate">{{ selectedRoom.name }}</span>
                            <button class="ml-0.5 text-zinc-400 transition hover:text-rose-600" @click="selectedRoom = null" type="button">
                                <IconCircleX class="size-4" />
                            </button>
                        </div>
                    </div>

                    <p class="ui-error mt-1" v-if="errorMsg('roomId')" v-html="errorMsg('roomId')" />
                </section>

                <!-- Project -->
                <!-- Project -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-emerald-400"></span>
                        <h3 class="ui-card-title">{{ $t('Project') }}</h3>
                    </header>

                    <!-- Fehlerhinweis immer oben beim Abschnittstitel anzeigen -->
                    <div class="mt-1">
                        <p class="ui-error" v-if="errorMsg('projectId')" v-html="errorMsg('projectId')" />
                    </div>

                    <!-- Aktivierung -->
                    <label for="showProjectInfo" class="flex items-center gap-2 mt-1 cursor-pointer">
                        <input id="showProjectInfo" type="checkbox" v-model="showProjectInfo" class="ui-checkbox" />
                        <span class="text-[13px] text-zinc-700">{{ $t('Enable project assignment') }}</span>
                    </label>

                    <div v-if="showProjectInfo" class="mt-2 space-y-2">

                        <!-- Chip-Ansicht: nur anzeigen, wenn ein bestehendes Projekt ausgewählt ist -->
                        <template v-if="!creatingProject && selectedProject?.id">
                            <div class="ui-project-chip">
                                <div class="min-w-0">
                                    <a
                                        v-if="canAccessProject()"
                                        :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                        class="ui-project-link"
                                        :title="selectedProject?.name"
                                    >
                                        {{ selectedProject?.name }}
                                    </a>
                                    <span v-else class="truncate xsDark text-zinc-800">{{ selectedProject?.name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" class="ui-icon-btn" @click="removeProject" :aria-label="$t('Remove project')">
                                        <IconCircleX class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Segment: Bestehend / Neu -->
                        <div class="ui-segment" role="tablist" aria-label="Project source">
                            <button
                                type="button"
                                class="ui-segment-btn"
                                :class="!creatingProject ? 'is-active' : ''"
                                role="tab"
                                :aria-selected="!creatingProject"
                                @click="switchToExisting()"
                            >
                                {{ $t('Existing project') }}
                            </button>
                            <button
                                type="button"
                                class="ui-segment-btn"
                                :class="creatingProject ? 'is-active' : ''"
                                role="tab"
                                :aria-selected="creatingProject"
                                @click="switchToNew()"
                            >
                                {{ $t('New project') }}
                            </button>
                        </div>

                        <!-- Inhalt: Bestehend = Suche / Neu = freies Input -->
                        <div>
                            <!-- Bestehendes Projekt: Suche. Hinweis: Chip oben blendet die Suche aus, bis "Ändern" -->
                            <ProjectSearch
                                v-if="!creatingProject && !selectedProject?.id"
                                ref="projectSearchRef"
                                :label="$t('Search for projects')"
                                @project-selected="chooseProjectFromPicker"
                            />

                            <!-- Neues Projekt: freies Feld, keine Bestätigung, NICHT auto-umschalten -->
                            <BaseInput
                                v-if="creatingProject"
                                id="projectName"
                                ref="projectNameRef"
                                :label="$t('New project name')"
                                v-model="projectName"
                                class="ui-input"
                                :placeholder="$t('e.g. Kitchen Miller – Renovation')"
                            />

                            <LastedProjects
                                v-if="!creatingProject && !selectedProject?.id"
                                :limit="10"
                                @select="chooseProjectFromPicker"
                            />
                        </div>

                        <!-- Hinweise/Fehler -->
                        <p class="ui-hint" v-if="creatingProject">
                            {{ $t('The project will be created when saving the event.') }}
                        </p>
                        <p class="ui-error" v-if="errorMsg('projectName')" v-html="errorMsg('projectName')" />
                    </div>
                </section>


                <!-- Notes / Booking -->
                <section class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-violet-400"></span>
                        <h3 class="ui-card-title">{{ $t('Notes & booking') }}</h3>
                    </header>

                    <div class="space-y-3">
                        <BaseTextarea
                            :label="$t('What do I need to bear in mind for the event?')"
                            id="description"
                            v-model="description"
                            rows="4"
                        />

                        <div v-if="event?.occupancy_option" class="space-y-2">
                            <BaseTextarea
                                :label="$t('Comment on the booking (inquirer will be notified)')"
                                id="adminComment"
                                v-model="adminComment"
                                rows="3"
                            />

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1.5">
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="accept"
                                        class="ui-checkbox ui-checkbox-emerald"
                                        @change="toggleAccept('accept')"
                                    />
                                    <span class="text-[13px]" :class="accept ? 'text-zinc-900' : 'text-zinc-600'">{{ $t('Commitments') }}</span>
                                </label>

                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="optionAccept"
                                        class="ui-checkbox ui-checkbox-amber"
                                        @change="toggleAccept('option')"
                                    />
                                    <span class="text-[13px]" :class="optionAccept ? 'text-zinc-900' : 'text-zinc-600'">{{ $t('Optional commitment') }}</span>
                                </label>
                            </div>

                            <div v-if="optionAccept" class="max-w-sm">
                                <ArtworkBaseListbox
                                    v-model="optionString"
                                    :items="bookingOptions"
                                    option-label="name"
                                    option-key="name"
                                    by="name"
                                    label="Option"
                                    :use-translations="false"
                                    :button-class="uiLbBtn"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Properties -->
                <section v-if="(event_properties?.length || 0) > 0" class="ui-card">
                    <header class="ui-card-header">
                        <span class="ui-dot bg-cyan-400"></span>
                        <h3 class="ui-card-title">{{ $t('Properties') }}</h3>
                    </header>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label
                            v-for="ep in event_properties"
                            :key="ep.id"
                            class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 transition hover:bg-white"
                        >
                            <input type="checkbox" v-model="ep.checked" class="ui-checkbox" />
                            <PropertyIcon :name="ep.icon" class="size-3.5" />
                            <span class="text-[13px]" :class="ep.checked ? 'font-medium text-zinc-900' : 'text-zinc-700'">{{ ep.name }}</span>
                        </label>
                    </div>

                    <div v-if="checkedEventProperties.length" class="mt-2 flex flex-wrap items-center gap-1.5">
                    <span
                        v-for="(ep, i) in checkedEventProperties"
                        :key="ep.id ?? i"
                        class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[12.5px] text-zinc-800"
                    >
                       <PropertyIcon :name="ep.icon" class="size-3.5" />
                      <span>{{ ep.name }}</span>
                    </span>
                    </div>
                </section>

                <!-- Sticky Action Bar -->
                <div class="ui-footer">
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" class="ui-btn-secondary" @click="closeModal">
                            {{ $t('Cancel') }}
                        </button>

                        <FormButton
                            v-if="canCreateDirect"
                            :disabled="isPrimaryDisabled"
                            @click="updateOrCreateEvent()"
                            :text="primaryButtonText"
                        />
                        <FormButton
                            v-else
                            :disabled="requestDisabled"
                            @click="updateOrCreateEvent(true)"
                            :text="$t('Request occupancy')"
                        />
                    </div>
                </div>
            </div>

            <!-- READONLY MODE -->
            <div v-else class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-indigo-400"></span>
                    <h3 class="ui-card-title">{{ $t('Event overview') }}</h3>
                </header>

                <div class="flex items-center gap-3">
                    <div class="size-9 rounded-full ring-1 ring-zinc-100" :style="{ backgroundColor: selectedEventType?.hex_code }" />
                    <div class="min-w-0">
                        <h2 class="truncate text-[15px] font-semibold text-zinc-900">{{ eventName }}</h2>
                        <div class="mt-0.5 flex items-center gap-3 text-[12.5px] text-zinc-600">
                            <span>{{ selectedEventType?.name }}</span>
                            <span v-if="selectedEventStatus" class="inline-flex items-center gap-1">
                            <span class="inline-block size-2 rounded-full" :style="{ backgroundColor: selectedEventStatus?.color }"></span>
                                {{ selectedEventStatus?.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 ui-grid-2">
                    <div class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Date & Time') }}</div>
                        <div class="text-[13px] text-zinc-800">
                            <span v-if="startDate === endDate">{{ startDate }} • {{ startTime }} – {{ endTime }}</span>
                            <span v-else>{{ startDate }} {{ startTime }} — {{ endDate }} {{ endTime }}</span>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Room') }}</div>
                        <div class="text-[13px] text-zinc-800">{{ selectedRoom?.name }}</div>
                    </div>
                    <div v-if="selectedProject?.id" class="space-y-1.5">
                        <div class="ui-hint">{{ $t('Project') }}</div>
                        <div class="text-[13px]">
                            <a
                                v-if="canAccessProject()"
                                :href="route('projects.tab', { project: selectedProject.id, projectTab: first_project_calendar_tab_id })"
                                class="text-indigo-600 hover:underline"
                            >
                                {{ selectedProject?.name }}
                            </a>
                            <span v-else class="text-zinc-800">{{ selectedProject?.name }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="description" class="mt-3 border-t border-zinc-100 pt-3 text-[13px] text-zinc-800">
                    {{ description }}
                </div>

                <div v-if="checkedEventProperties.length" class="mt-3 border-t border-zinc-100 pt-3">
                    <div class="mb-1 ui-hint">{{ $t('Properties') }}</div>
                    <div class="flex flex-wrap gap-1.5">
            <span
                v-for="(ep, i) in checkedEventProperties"
                :key="ep.id ?? i"
                class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[12.5px] text-zinc-800"
            >
              <component :is="ep.icon" class="size-3.5" />
              <span>{{ ep.name }}</span>
            </span>
                    </div>
                </div>

                <div class="mt-3 flex w-full justify-end">
                    <button type="button" class="ui-btn-secondary" @click="closeModal">{{ $t('Close') }}</button>
                </div>
            </div>
        </div>

        <!-- Confirmations / Series -->
        <ConfirmationComponent
            v-if="deleteComponentVisible"
            :confirm="$t('Delete')"
            :titel="$t('Delete event?')"
            :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [event?.title ?? ''])"
            @closed="afterConfirm"
        />
        <ChangeAllSubmitModal
            v-if="showSeriesEdit"
            @closed="closeSeriesEditModal"
            @all="saveAllSeriesEvents"
            @single="singleSaveEvent"
        />
    </ArtworkBaseModal>
</template>


<script setup>
import {computed, inject, nextTick, onMounted, ref, watch} from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import dayjs from 'dayjs'
import { can } from 'laravel-permission-to-vuejs'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'
import ChangeAllSubmitModal from '@/Layouts/Components/ChangeAllSubmitModal.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import ProjectSearch from '@/Components/SearchBars/ProjectSearch.vue'
import RoomSearch from '@/Components/SearchBars/RoomSearch.vue'

import { IconChevronUp, IconCircleX } from '@tabler/icons-vue'
import { useEvent } from '@/Composeables/Event.js'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import {useI18n} from "vue-i18n";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
const { t } = useI18n(), $t = t;
const props = defineProps({
    showHints: { type: Boolean, default: false },
    eventTypes: { type: Array, required: true },
    // Rooms can arrive a bit later depending on the caller. Provide a safe default.
    rooms: { type: Array, required: false, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    event: { type: [Object, Boolean], default: null, required: false },
    project: { type: Object, default: null },
    wantedRoomId: { type: [Number, String], default: null },
    roomCollisions: { type: Object, default: () => ({}) },
    showComments: { type: Boolean, default: false },
    first_project_calendar_tab_id: { type: [Number, String], required: true },
    usedInBulkComponent: { type: Boolean, default: false },
    requiresAxiosRequests: { type: Boolean, default: false },
    calendarProjectPeriod: { type: Boolean, default: false },
    eventStatuses: { type: Array, default: () => [] },
    isPlanning: { type: Boolean, default: false },
    wantedDate: { type: String, default: null },
})
const emit = defineEmits(['closed'])

const page = usePage()
const statusModule = computed(() => page.props.event_status_module)
const { getDaysOfEvent } = useEvent()
const event_properties = inject('event_properties', [])

// --- State
const submit = ref(true)
const startDate = ref(null)
const startTime = ref(null)
const endDate = ref(null)
const endTime = ref(null)
const oldStartDate = ref(null)
const oldStartTime = ref(null)
const oldEndDate = ref(null)
const oldEndTime = ref(null)

const showSeriesEdit = ref(false)
const allSeriesEvents = ref(false)
const series = ref(false)
const seriesEndDate = ref(null)
const selectedFrequency = ref({ id: 2, name: 'Wöchentlich' })
const frequencies = [
    { id: 1, name: 'Täglich' },
    { id: 2, name: 'Wöchentlich' },
    { id: 3, name: 'Alle 2 Wochen' },
    { id: 4, name: 'Monatlich' },
]

const projectName = ref(null)
const title = ref(null)
const isOption = ref(null)
const eventName = ref(null)
const selectedEventType = ref(props.eventTypes?.[0] ?? null)
const selectedEventStatus = ref(props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null)

const showProjectInfo = ref(Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id))
const allDayEvent = ref(false)
const selectedProject = ref(null)
const selectedRoom = ref(null)
const error = ref(null)
const creatingProject = ref(false)
const description = ref(null)
const canEdit = ref(false)
const declinedRoomId = ref(null)
const deleteComponentVisible = ref(false)
const adminComment = ref('')
const optionString = ref(null)
const accept = ref(true)
const optionAccept = ref(false)
const roomCollisionArray = ref(props.roomCollisions ?? {})
const helpTextLengthRoom = ref('')
const initialRoomId = ref(null)
const showRejections = ref(false)
const isLoading = ref(false)
const quickDurations = [30, 60, 90]

const bookingOptions = [{ name: 'Option 1' }, { name: 'Option 2' }, { name: 'Option 3' }, { name: 'Option 4' }]

const answerRequestForm = useForm({ accepted: false })
const endAutoFilled = ref(false) // darf anfänglich auto-füllen
const defaultDurationMin = computed(() => {
    const raw = Number(page.props.event_time_length_minutes)
    return Number.isFinite(raw) && raw > 0 ? raw : 60
})

function setEndFromDuration() {
    if (!startDate.value || !startTime.value || allDayEvent.value) return
    const startDT = dayjs(`${startDate.value}T${startTime.value}`)
    const endDT = startDT.add(defaultDurationMin.value, 'minute')
    endDate.value = endDT.format('YYYY-MM-DD')
    endTime.value  = endDT.format('HH:mm')
    endAutoFilled.value = true
}
// --- Computeds
// Manche Aufrufer liefern rooms als Objekt-Map statt als Array – hier vereinheitlichen
const roomsList = computed(() => Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {}))
const isRoomAdmin = computed(() => {
    return roomsList.value.find(r => r.id === props.event?.roomId)?.admins?.some(a => a.id === page.props.auth.user.id) || false
})
const isCreator = computed(() => (props.event ? props.event.created_by?.id === page.props.auth.user.id : false))
const hasAdminRole = () => props.isAdmin || page.props.auth.user?.roles?.some?.(r => r.name?.toLowerCase?.().includes('admin'))

const roomAdminIds = computed(() => selectedRoom.value?.room_admins?.map(a => a.id) ?? [])

const modalTitle = computed(() => {
    if (props.event?.id) {
        if (props.event?.occupancy_option) return $t('Change & confirm occupancy')
        if (props.event?.isPlanning) return $t('Planned Event')
        return $t('Event')
    }
    return props.isPlanning ? $t('Create planned Event') : $t('New room allocation')
})

const modalDescription = computed(() => {
    if (props.event?.id) {
        if (props.event?.occupancy_option) return $t('Please review the event details and confirm the booking by selecting "Commitments" or "Optional commitment". You can also send a message to the inquirer if needed.')
        if (props.event?.isPlanning) return $t('This event is marked as planned. You can edit the details, but it will not be visible to regular users until it is confirmed.')
        return $t('Here you can view and edit the details of the event. Make sure to save any changes you make.')
    }
    return props.isPlanning
        ? $t('You are about to create a planned event. Please fill in the necessary details and save it. The event will not be visible to regular users until it is confirmed.')
        : $t('Fill in the details for the new room allocation. Once you save, the event will be created and visible to users with access to the selected room.')
})

const checkedEventProperties = computed(() => (event_properties ?? []).filter(p => p.checked))

const canCreateDirect = computed(
    () => hasAdminRole() || selectedRoom.value?.everyone_can_book || roomAdminIds.value.includes(page.props.auth.user.id) || can('create events without request')
)

const isPrimaryDisabled = computed(() => {
    const invalidSeries = series.value && (!seriesEndDate.value || !selectedFrequency.value || (endDate.value && seriesEndDate.value && endDate.value > seriesEndDate.value))
    const missingRoom = !selectedRoom.value
    const missingSubmit = !submit.value
    const needDecision = props.event?.occupancy_option && accept.value === false && optionAccept.value === false && adminComment.value === ''
    return missingRoom || missingSubmit || invalidSeries || needDecision || isLoading.value
})
const primaryButtonText = computed(() => {
    if (!props.event?.occupancy_option) return $t('Save')
    if (accept.value) return $t('Commitments')
    if (optionAccept.value) return $t('Optional commitment')
    if (adminComment.value) return $t('Send message')
    return $t('Save')
})
const requestDisabled = computed(() => {
    const invalidSeries = series.value && (!seriesEndDate.value || !selectedFrequency.value || (endDate.value && seriesEndDate.value && endDate.value > seriesEndDate.value))
    if (!selectedRoom.value || !submit.value || invalidSeries || isLoading.value) return true
    if (!can('request room occupancy') && !props.isPlanning) return true
    if (!can('can see planning calendar') && props.isPlanning) return true
    return false
})

// --- Watches / Mount
watch(selectedRoom, () => checkChanges(), { deep: true })
watch(() => props.event, () => openModal(), { deep: true, immediate: true })
// Falls Räume asynchron/als Map eintreffen: gewünschten Raum nachtragen
watch(
    () => props.rooms,
    (roomsNow) => {
        if (selectedRoom.value || !props.wantedRoomId) return
        const list = Array.isArray(roomsNow) ? roomsNow : Object.values(roomsNow || {})
        const rid = Number(props.wantedRoomId)
        const found = list.find(r => Number(r?.id) === rid || Number(r?.roomId) === rid) || null
        if (found) selectedRoom.value = found
    },
    { deep: false }
)

onMounted(() => {
    console.log(props)
    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = '09:00'
        setEndFromDuration()
    }
    if (props.wantedRoomId) {
        const rid = Number(props.wantedRoomId)
        selectedRoom.value = findRoomById(rid)
    } else if (props.event) {
        const rid = Number(props.event.roomId)
        selectedRoom.value = findRoomById(rid)
    }
})

// --- Methods
function canAccessProject() {
    if (can('view projects') || can('write projects')) return true
    if (selectedProject.value?.creator_id === page.props.auth.user.id) return true
    if (selectedProject.value?.team_members?.some?.(m => m.id === page.props.auth.user.id)) return true
    return false
}
function convertDateFormat(dateString) {
    const parts = (dateString ?? '').split('-')
    if (parts.length !== 3) return dateString
    return `${parts[2]}.${parts[1]}.${parts[0]}`
}
// Hilfsfunktion: robustes Finden eines Raums anhand id/roomId, inkl. Typ-Coercion
function findRoomById(rawId) {
    const rid = Number(rawId)
    if (!Number.isFinite(rid)) return null
    const list = roomsList.value || []
    return list.find(r => Number(r?.id) === rid || Number(r?.roomId) === rid) || null
}
function openModal() {
    canEdit.value = (!props.event?.id) || isCreator.value || isRoomAdmin.value || hasAdminRole()

    if (!props.event) {
        selectedEventType.value = props.eventTypes?.[0] ?? null
        selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
        // Direkt vorbelegen: Datum/Zeit und Raum, falls gewünscht
        if (props.wantedDate) {
            startDate.value = props.wantedDate
            startTime.value = '09:00'
            setEndFromDuration()
        }
        if (props.wantedRoomId && !selectedRoom.value) {
            const rid = Number(props.wantedRoomId)
            selectedRoom.value = findRoomById(rid)
        }
        // NEU: Wenn ein Projekt-Prop gesetzt ist (z. B. im Projekt-Schichttab), Projekt vorauswählen
        if (props.project && !selectedProject.value) {
            selectedProject.value = { id: props.project.id, name: props.project.name }
            showProjectInfo.value = true
        } else if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
            selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
        }
        return
    }

    if (props.event?.project) {
        selectedProject.value = { id: props.event.project.id, name: props.event.project.name }
    } else if (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id) {
        selectedProject.value = { id: page.props.auth.user.calendar_settings.time_period_project_id, name: page.props.projectNameOfCalendarProject }
    }

    const start = dayjs(props.event.start)
    const end = dayjs(props.event.end)
    startDate.value = start.format('YYYY-MM-DD')
    startTime.value = start.format('HH:mm')
    endDate.value = end.format('YYYY-MM-DD')
    endTime.value = end.format('HH:mm')
    oldStartDate.value = startDate.value
    oldStartTime.value = startTime.value
    oldEndDate.value = endDate.value
    oldEndTime.value = endTime.value

    title.value = props.event.title
    eventName.value = props.event.eventName

    const eventStatusId = props.event?.eventStatus?.id ?? props.event?.eventStatusId ?? props.event?.event_status_id
    selectedEventStatus.value = props.eventStatuses?.find(s => s.id === eventStatusId) ?? selectedEventStatus.value
    allDayEvent.value = !!props.event.allDay

    selectedEventType.value = props.event.eventType?.id
        ? props.eventTypes?.find(t => t.id === props.event.eventType.id) ?? props.eventTypes?.[0]
        : props.eventTypes?.[0]
    if (props.event?.eventTypeId) {
        selectedEventType.value = props.eventTypes?.find(t => t.id === props.event.eventTypeId) ?? selectedEventType.value
    }

    series.value = !!props.event.is_series
    if (series.value) {
        seriesEndDate.value = props.event.series?.end_date ?? null
        const found = frequencies.find(f => f.id === props.event.series?.frequency_id)
        if (found) selectedFrequency.value = found
    }

    if (selectedProject.value?.id) showProjectInfo.value = true

    if (props.wantedRoomId) {
        const rid = Number(props.wantedRoomId)
        selectedRoom.value = findRoomById(rid)
    } else {
        const rid = Number(props.event.roomId)
        selectedRoom.value = findRoomById(rid)
    }

    if (props.wantedDate) {
        startDate.value = props.wantedDate
        startTime.value = '09:00'
        endDate.value = props.wantedDate
        endTime.value = '10:00'
    }

    initialRoomId.value = selectedRoom.value?.id ?? null
    description.value = props.event.description ?? ''

    ;(event_properties ?? []).forEach(ep => {
        ep.checked = props.event?.eventProperties?.some(eep => eep.id === ep.id) || false
    })

    checkCollisions()
}
function closeModal(closedOnPurpose = false) {
    if (closedOnPurpose) {
        emit(
            'closed',
            closedOnPurpose,
            Array.from(new Set([initialRoomId.value, selectedRoom.value?.id].filter(Boolean))),
            getDaysOfEvent(startDate.value, series.value ? seriesEndDate.value : endDate.value),
            getDaysOfEvent(oldStartDate.value, oldEndDate.value),
        )
    } else {
        emit('closed', closedOnPurpose)
    }

    // minimal reset
    startDate.value = startTime.value = endDate.value = endTime.value = null
    oldStartDate.value = oldStartTime.value = oldEndDate.value = oldEndTime.value = null
    eventName.value = description.value = null
    selectedProject.value = selectedRoom.value = null
    selectedEventType.value = props.eventTypes?.[0] ?? null
    selectedEventStatus.value = props.eventStatuses?.find(s => s.default) ?? props.eventStatuses?.[0] ?? null
    allDayEvent.value = false
    series.value = false
    seriesEndDate.value = null
    showProjectInfo.value = Boolean(props.project) || (props.calendarProjectPeriod && page.props.auth.user.calendar_settings.time_period_project_id)
    creatingProject.value = false
    projectName.value = null
    adminComment.value = ''
    accept.value = true
    optionAccept.value = false
    optionString.value = null
    error.value = null
    ;(event_properties ?? []).forEach(p => (p.checked = false))
    initialRoomId.value = null
}
function formatDate(date, time, toUTC = true) {
    // fehlende Werte abfangen
    if (!date || !time) return null

    // ISO-8601-kompatibel: "YYYY-MM-DDTHH:mm"
    const isoLocal = `${date}T${time}`

    // sicher parsen (alle Browser)
    const d = new Date(isoLocal)
    if (Number.isNaN(d.getTime())) return null

    // Wenn dein Backend UTC erwartet -> toUTC=true
    // Wenn lokales ISO ohne Z erwartet wird -> return isoLocal
    return toUTC ? d.toISOString() : isoLocal
}

function normalizeDateInput(raw) {
    if (!raw) return null
    // BaseInput (type=date) liefert normalerweise "YYYY-MM-DD".
    // Falls doch mal ein Date-Objekt oder ISO-String reinkommt: robust normalisieren.
    if (raw instanceof Date) {
        const d = dayjs(raw)
        return d.isValid() ? d.format('YYYY-MM-DD') : null
    }
    const s = String(raw).trim()
    // Wenn bereits korrekt: direkt zurück
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s

    const d = dayjs(s)
    return d.isValid() ? d.format('YYYY-MM-DD') : null
}
async function checkCollisions() {
    if ((startTime.value && startDate.value && endTime.value && endDate.value) || (allDayEvent.value && startDate.value && endDate.value)) {
        const startFull = formatDate(startDate.value, startTime.value ?? '00:00')
        const endFull = formatDate(endDate.value, endTime.value ?? '23:59')
        try {
            const { data } = await axios.post('/collision/room', { params: { start: startFull, end: endFull } })
            roomCollisionArray.value = data
        } catch { /* ignore */ }
    }
}
function checkYear(date) {
    return parseInt(String(date).split('-')[0]) > 1900
}
function getNextHourString(timeString) {
    const h = Number(timeString.slice(0, 2)) + 1
    const m = timeString.slice(3, 5)
    return `${h < 10 ? '0' + h : h}:${m}`
}
function validateStartBeforeEndTime() {
    error.value = null
    if (startDate.value && endDate.value && startTime.value && endTime.value) {
        // optional server-side validation hook
    }
}

// Liste der Schnell-Intervalle (anpassbar)


// Endzeit/Datum aus Start + Minuten setzen
function applyQuickDuration(minutes) {
    if (!startDate.value || allDayEvent.value) return
    if (!startTime.value) return

    const startDT = dayjs(`${startDate.value}T${startTime.value}`)
    const endDT = startDT.add(minutes, 'minute')

    endDate.value = endDT.format('YYYY-MM-DD')
    endTime.value  = endDT.format('HH:mm')

    // Kennzeichnen: automatisch gesetzt (manuelle Änderungen sollen danach Vorrang haben)
    endAutoFilled.value = true

    validateStartBeforeEndTime()
    checkCollisions()
}

function updateTimes() {
    // Start-/Enddatum immer in das gleiche Format bringen wie bei manueller Eingabe
    if (startDate.value) startDate.value = normalizeDateInput(startDate.value)
    if (endDate.value) endDate.value = normalizeDateInput(endDate.value)

    // Enddatum soll beim Anlegen automatisch dem Startdatum folgen,
    // solange das Ende nicht manuell geändert wurde.
    if (startDate.value && (!endDate.value || endAutoFilled.value)) {
        endDate.value = startDate.value
        endAutoFilled.value = true
    }

    if (startDate.value && startTime.value) {
        // Endzeit nur setzen, wenn leer ODER zuletzt auto-gefüllt
        if (!endTime.value || endAutoFilled.value) {
            setEndFromDuration()
        }
    }

    validateStartBeforeEndTime()
    checkCollisions()
}

function checkChanges() {
    if (selectedRoom.value?.temporary) {
        const startFull = formatDate(startDate.value, startTime.value)
        const endFull = formatDate(endDate.value, endTime.value)
        const start = dayjs(startFull)
        const end = dayjs(endFull)
        const roomStart = dayjs(selectedRoom.value.start_date)
        const roomEnd = dayjs(selectedRoom.value.end_date)

        if (start.isBefore(roomStart)) {
            helpTextLengthRoom.value = $t('The start time is before the availability of this temporary room.')
            submit.value = false
        } else if (end.isAfter(roomEnd)) {
            helpTextLengthRoom.value = $t('The end time is after the availability of this temporary room.')
            submit.value = false
        } else {
            helpTextLengthRoom.value = ''
            submit.value = true
        }
    }
    updateTimes()
}

const uiLbBtn =
    'menu-button bg-white focus:outline-none focus:ring-0 w-full text-left rounded-md border border-zinc-200 shadow-sm px-3 py-4 h-13 xsDark text-zinc-900';
const uiLbOpts =
    'mt-1 max-h-60 overflow-auto rounded-md bg-white py-1 text-zinc-900 ring-1 shadow-lg ring-black/5 focus:outline-none';

function toggleAccept(type) {
    if (type === 'option') {
        if (optionAccept.value) {
            accept.value = false
            optionString.value = bookingOptions[0].name
        }
    } else {
        if (accept.value) {
            optionAccept.value = false
            optionString.value = null
        }
    }
}
function chooseProject(project) {
    selectedProject.value = project
    projectName.value = ''
}
function onRoomSelected(room) {
    selectedRoom.value = room
    checkChanges()
}
function errorMsg(field) {
    const e = error.value
    if (!e) return ''
    const raw = Array.isArray(e?.[field]) ? e[field].join('.<br> ') : e?.[field]
    return raw || ''
}
function payload() {
    return {
        title: title.value,
        eventName: eventName.value,
        eventStatusId: selectedEventStatus.value?.id,
        start: formatDate(startDate.value, allDayEvent.value ? '00:00' : startTime.value),
        end: formatDate(endDate.value, allDayEvent.value ? '23:59' : endTime.value),
        roomId: selectedRoom.value?.id,
        description: description.value,
        isOption: isOption.value,
        eventNameMandatory: !!selectedEventType.value?.individual_name,
        projectId: showProjectInfo.value ? selectedProject.value?.id : null,
        projectName: showProjectInfo.value ? (creatingProject.value ? projectName.value : '') : '',
        eventTypeId: selectedEventType.value?.id,
        projectIdMandatory: !!(selectedEventType.value?.project_mandatory && !creatingProject.value),
        creatingProject: showProjectInfo.value ? creatingProject.value : false,
        declinedRoomId: declinedRoomId.value,
        is_series: !!series.value,
        seriesFrequency: selectedFrequency.value.id,
        seriesEndDate: seriesEndDate.value,
        allSeriesEvents: allSeriesEvents.value,
        adminComment: adminComment.value,
        optionString: optionAccept.value ? optionString.value : null,
        accept: accept.value,
        optionAccept: optionAccept.value,
        allDay: allDayEvent.value,
        usedInBulkComponent: props.usedInBulkComponent,
        showProjectPeriodInCalendar: props.calendarProjectPeriod,
        event_properties: (event_properties ?? []).filter(p => p.checked).map(p => p.id),
        isPlanning: props.event ? props.event.isPlanning : props.isPlanning,
    }
}
async function updateOrCreateEvent(isOptionParam = false) {
    isLoading.value = true
    isOption.value = isOptionParam

    if (allDayEvent.value) {
        startTime.value = '00:00'
        endTime.value = '23:59'
    }
    if (accept.value === false && optionAccept.value === false) {
        isOption.value = true
    }

    const data = payload()

    if (!props.requiresAxiosRequests &&
        (props.usedInBulkComponent ||
            (page.props.auth.user.calendar_settings.time_period_project_id === selectedProject.value?.id &&
                props.calendarProjectPeriod))) {
        if (!props.event?.id) {
            router.post(route('events.store'), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => {
                    isLoading.value = false
                    closeModal(true)
                },
                onError: (resp) => {
                    isLoading.value = false
                    error.value = resp
                },
            })
        } else {
            router.put(route('events.update', { event: props.event.id }), data, {
                preserveScroll: true,
                preserveState: (pg) => typeof pg?.component === 'undefined',
                onSuccess: () => {
                    isLoading.value = false
                    closeModal(true)
                },
                onError: (resp) => {
                    isLoading.value = false
                    error.value = resp
                },
            })
        }
        return
    }

    try {
        if (!props.event?.id) await axios.post('/events', data)
        else await axios.put(`/events/${props.event.id}`, data)
        isLoading.value = false
        closeModal(true)
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}
async function singleSaveEvent() {
    isLoading.value = true
    try {
        await axios.put(`/events/${props.event?.id}`, payload())
        isLoading.value = false
        closeModal(true)
        closeSeriesEditModal()
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}
async function saveAllSeriesEvents() {
    isLoading.value = true
    allSeriesEvents.value = true
    try {
        await axios.put(`/events/${props.event?.id}`, payload())
        isLoading.value = false
        closeModal(true)
        closeSeriesEditModal()
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}
function closeSeriesEditModal() {
    showSeriesEdit.value = false
}
async function afterConfirm(confirmed) {
    if (!confirmed) {
        deleteComponentVisible.value = false
        return
    }
    isLoading.value = true
    try {
        await axios.delete(`/events/${props.event.id}`)
        isLoading.value = false
        closeModal(true)
    } catch (e) {
        isLoading.value = false
        error.value = e?.response?.data?.errors ?? e
    }
}

// neue lokale UI-States/Methoden
const showProjectPicker = ref(false)

// beim Öffnen initial steuern (falls Projekt schon gesetzt, Picker ausblenden)
const _origOpenModal = openModal
openModal = function () {
    _origOpenModal()
    showProjectPicker.value = !selectedProject.value?.id
}

const projectSearchRef = ref(null)
const projectNameRef = ref(null)

// Umschalten: Bestehend
function switchToExisting() {
    creatingProject.value = false
    nextTick(() => projectSearchRef.value?.focus?.())
}

// Umschalten: Neu
function switchToNew() {
    // NICHTS automatisch bestätigen – einfach editierbares Feld lassen
    creatingProject.value = true
    selectedProject.value = null // wichtig: kein bestehendes Projekt aktiv
    nextTick(() => projectNameRef.value?.focus?.())
}

// Entfernen
function removeProject() {
    selectedProject.value = null
    // bleib in bestehendem Modus? Dann zeig Suche; oder wechsel optional zu "Neu":
    // creatingProject.value = true
}

watch([startDate, startTime, allDayEvent], () => {
    // Endzeit ggf. automatisch anpassen, wenn Start sich ändert
    if (!endTime.value || endAutoFilled.value) updateTimes()
})

// Auswahl aus Suche
function chooseProjectFromPicker(project) {
    chooseProject(project)      // deine vorhandene Methode (setzt selectedProject)
    // nach Auswahl → Chip anzeigen (Suche verschwindet automatisch)
}
</script>
